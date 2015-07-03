<?php
/*
    This file is part of ObjSpace, a plugin for WordPress.

    ObjSpace is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    ObjSpace is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with WordPress.  If not, see <http://www.gnu.org/licenses/>.

*/

if (!defined('ABSPATH')) {
    die();
}

use Aws\S3\S3Client as AwsS3OBJSPACEBACKUP;

use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;
use Guzzle\Plugin\Log\LogPlugin; // DEBUGGING ONLY


class OBJSPACEBACKUP {

	const DIRECTORY_SEPARATORS = '/\\';

    // INIT - hooking into this lets us run things when a page is hit.

    public static function init() {

        // SCHEDULER
        if ( isset($_POST['objspacebackup-schedule']) && current_user_can('manage_options') ) {
            wp_clear_scheduled_hook('objspacebackup-backup');
            if ( $_POST['objspacebackup-schedule'] != 'disabled' ) {
                wp_schedule_event(current_time('timestamp',true)+86400, $_POST['objspacebackup-schedule'], 'objspacebackup-backup');
                $timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'objspacebackup-schedule' ) ), get_option('time_format') );
                $nextbackup = sprintf(__('Next backup: %s', objspacebackup), $timestamp );
                OBJSPACEBACKUP::logger('Scheduled '.$_POST['objspacebackup-schedule'].' backup. ' .$nextbackup);
            }
        }

        // RESET
        if ( current_user_can('manage_options') && isset($_POST['dhdo-reset']) && $_POST['dhdo-reset'] == 'Y'  ) {
            delete_option( 'objspacebackup-backupsection' );
            delete_option( 'objspacebackup-boto' );
            delete_option( 'objspacebackup-bucket' );
            delete_option( 'objspacebackup-key' );
            delete_option( 'objspacebackup-schedule' );
            delete_option( 'objspacebackup-secretkey' );
            delete_option( 'objspacebackup-section' );
            delete_option( 'objspacebackup-logging' );
            OBJSPACEBACKUP::logger('reset');
           }

        // LOGGER: Wipe logger if blank
        if ( current_user_can('manage_options') && isset($_POST['dhdo-logchange']) && $_POST['dhdo-logchange'] == 'Y' ) {
            if ( !isset($_POST['objspacebackup-logging'])) {
                OBJSPACEBACKUP::logger('reset');
            }
        }       
        
        // UPDATE OPTIONS
        if ( isset($_GET['settings-updated']) && isset($_GET['page']) && ( $_GET['page'] == 'objspacebackup-menu' || $_GET['page'] == 'objspacebackup-menu-backup' ) ) add_action('admin_notices', array('OBJSPACEBACKUPMESS','updateMessage'));

        // BACKUP ASAP
        if ( current_user_can('manage_options') &&  isset($_GET['backup-now']) && $_GET['page'] == 'objspacebackup-menu-backup' ) {
            wp_schedule_single_event( current_time('timestamp', true)+60, 'objspacebackup-backupnow');
            add_action('admin_notices', array('OBJSPACEBACKUPMESS','backupMessage'));
            OBJSPACEBACKUP::logger('Scheduled ASAP backup in 60 seconds.' );
        }
        
        // BACKUP
        if ( wp_next_scheduled( 'objspacebackup-backupnow' ) && ( $_GET['page'] == 'objspacebackup-menu' || $_GET['page'] == 'objspacebackup-menu-backup' ) ) {
            add_action('admin_notices', array('OBJSPACEBACKUPMESS','backupMessage'));
        }
    }

    // Returns the URL of the plugin's folder.
    function getURL() {
        return plugins_url() . '/';
    }
   

    /**
     * Logging function
     *
     */

    // Acutal logging function
    public static function logger($msg) {
    
    if ( get_option('objspacebackup-logging') == 'on' ) {
           $file = OBJSPACEBACKUP_PLUGIN_DIR."/debug.txt"; 
           if ($msg == "reset") {
               $fd = fopen($file, "w+");
               $str = "";
           }
           elseif ( get_option('objspacebackup-logging') == 'on') {    
               $fd = fopen($file, "a");
               $str = "[" . date("Y/m/d h:i:s", current_time('timestamp')) . "] " . $msg . "\n";
           }
              fwrite($fd, $str);
              fclose($fd);
          }
    }

    /**
     * Generate Backups and the functions needed for that to run
     *
     */
        
    // Scan folders to collect all the filenames
    function rscandir($base='') {
        $data = array_diff(scandir($base), array('.', '..'));
        $omit = array('\/cache');
    
        $subs = array();
        foreach($data as $key => $value) :
            if ( is_dir($base . '/' . $value) ) :
                unset($data[$key]);
                $subs[] = OBJSPACEBACKUP::rscandir($base . '/' . $value);
            elseif ( is_file($base . '/' . $value) ) :
                $data[$key] = $base . '/' . $value;
            endif;
        endforeach;
    
        foreach ( $subs as $sub ) {
            $data = array_merge($data, $sub);
        }
        return $data;
        OBJSPACEBACKUP::logger('Scanned folders and files to generate list for backup.');
    
        foreach( $omit as $omitter ) {
        	$data = preg_grep( $omitter , $data, PREG_GREP_INVERT);
        }
        
        OBJSPACEBACKUP::logger( print_r($data) );

        return $data;
        OBJSPACEBACKUP::logger('Scanned folders and files to generate list for backup.');
    }
    
    // The actual backup
    function backup() {
        OBJSPACEBACKUP::logger('Begining Backup.');
        global $wpdb;

		if (!is_dir( content_url() . '/upgrade/' )) {
			OBJSPACEBACKUP::logger('Upgrade folder missing. This will cause serious issues with WP in general, so we will create it for you.');
		    mkdir( content_url() . '/upgrade/' );       
		}
        
        // Pull in data for what to backup
        $sections = get_option('objspacebackup-backupsection');
        if ( !$sections ) {
            $sections = array();
        }
        
        $file = WP_CONTENT_DIR . '/upgrade/objspace-backups.zip';
        $fileurl = content_url() . '/upgrade/objspace-backups.zip';

        // Pre-Cleanup
        if(file_exists($file)) { 
            @unlink($file);
            OBJSPACEBACKUP::logger('Leftover zip file found, deleting '.$file.' ...');
        }

		try {
				$zip = new ZipArchive( $file );
				$zaresult = true;
				OBJSPACEBACKUP::logger('ZipArchive found and will be used for backups.');
		} catch ( Exception $e ) {
				$error_string = $e->getMessage();
				$zip = new PclZip($file);
				OBJSPACEBACKUP::logger('ZipArchive not found. Error: '. $error_string );
				OBJSPACEBACKUP::logger('PclZip will be used for backups.');
				require_once(ABSPATH . '/wp-admin/includes/class-pclzip.php');
				$zaresult = false;
		}

        $backups = array();

        // All me files!
        if ( in_array('files', $sections) ) {

			OBJSPACEBACKUP::logger( 'Calculating backup size...');

			$trimdisk = WP_CONTENT_DIR ;
			$diskcmd = sprintf("du -s %s", WP_CONTENT_DIR );
			$diskusage = exec( $diskcmd );
			$diskusage = trim(str_replace($trimdisk, '', $diskusage));
			
			OBJSPACEBACKUP::logger(size_format( $diskusage * 1024 ).' of diskspace will be processed.');
			
			if ($diskusage < ( 2000 * 1024 ) ) {
				$backups = array_merge($backups, OBJSPACEBACKUP::rscandir(WP_CONTENT_DIR));
				OBJSPACEBACKUP::logger( count($backups) .' files added to backup list.');
			} else {
				OBJSPACEBACKUP::logger( 'ERROR! PHP is unable to backup your wp-content folder. Please consider cleaning out unused files (like plugins and themes).');
			}

			if ( file_exists(ABSPATH .'wp-config.php') ) {
		        $backups[] = ABSPATH .'wp-config.php' ;
				OBJSPACEBACKUP::logger( 'wp-config.php added to backup list.');
		    }

        } 
        
        // And me DB!
        if ( in_array('database', $sections) ) {
            set_time_limit(300);
            
            $sqlhash = wp_hash( wp_rand() );
			$sqlfile = WP_CONTENT_DIR . '/upgrade/'.$sqlhash.'.sql';
            $tables = $wpdb->get_col("SHOW TABLES LIKE '" . $wpdb->prefix . "%'");
            $tables_string = implode( ' ', $tables );

			// Pre cleanup
	        if(file_exists($sqlfile)) { 
	            @unlink($sqlfile);
	            OBJSPACEBACKUP::logger('Leftover sql file found, deleting '.$sqlfile.' ...');
	        }
            
            $dbcmd = sprintf( "mysqldump -h'%s' -u'%s' -p'%s' %s %s --single-transaction 2>&1 >> %s",
            DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, $tables_string, $sqlfile );
            
            exec( $dbcmd );
            
            $sqlsize = size_format( @filesize($sqlfile) );
            OBJSPACEBACKUP::logger('SQL file created: '. $sqlfile .' ('. $sqlsize .').');
            $backups[] = $sqlfile;
            OBJSPACEBACKUP::logger('SQL added to backup list.');

        }
        
        if ( !empty($backups) ) {
            set_time_limit(300);  // Increased timeout to 5 minutes. If the zip takes longer than that, I have a problem.
            if ( $zaresult != 'true' ) {
            	OBJSPACEBACKUP::logger('Creating zip file using PclZip.');
            	OBJSPACEBACKUP::logger('NOTICE: If the log stops here, PHP failed to create a zip of your wp-content folder. Please consider increasing the server\'s PHP memory, RAM or CPU.');
            	$zip->create($backups);

            } else {
            	OBJSPACEBACKUP::logger('Creating zip file using ZipArchive.');
            	OBJSPACEBACKUP::logger('NOTICE: If the log stops here, PHP failed to create a zip of your wp-content folder. Please consider cleaning out unused files (like plugins and themes), or increasing the server\'s PHP memory, RAM or CPU.');
            	try {
	            	$zip->open( $file, ZipArchive::CREATE );
	            	$trimpath =  ABSPATH ;

		            foreach($backups as $backupfiles) {
		            	if (strpos( $backupfiles , DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR ) === false) {
			            	$zip->addFile($backupfiles, 'objspacebackup-backup'.str_replace($trimpath, '/', $backupfiles) );
			            	//OBJSPACEBACKUP::logger( $backupfiles );
			            }
					}
					
					$zip->close();
            	} catch ( Exception $e ) {
            		$error_string = $e->getMessage();
            		OBJSPACEBACKUP::logger('ZipArchive failed to complete: '. $error_string );
            	}

            }

			if ( @file_exists( $file ) ) { 
            	OBJSPACEBACKUP::logger('Calculating zip file size ...');
				$zipsize = size_format( @filesize($file) );
				OBJSPACEBACKUP::logger('Zip file generated: '. $file .' ('. $zipsize .').');
			} else {
				@unlink($file);
				OBJSPACEBACKUP::logger('Zip file failed to generate. Nothing will be backed up.');
			}
			
			// Delete SQL
            if(file_exists($sqlfile)) { 
                @unlink($sqlfile);
                OBJSPACEBACKUP::logger('Deleting SQL file: '.$sqlfile.' ...');
            }			
            
            // Upload

			if ( @file_exists( $file ) ) {
	
			  	$s3 = AwsS3OBJSPACEBACKUP::factory(array(
					'key'      => get_option('objspacebackup-key'),
				    'secret'   => get_option('objspacebackup-secretkey'),
				    'base_url' => 'http://obj.space',
				));
	
/*
				// https://dreamxtream.wordpress.com/2013/10/29/aws-php-sdk-logging-using-guzzle/
				$logPlugin = LogPlugin::getDebugPlugin(TRUE,
				//Don't provide this parameter to show the log in PHP output
					fopen(OBJSPACEBACKUP_PLUGIN_DIR.'/debug2.txt', 'a')
				);
				$s3->addSubscriber($logPlugin);
*/
	
	            $bucket = get_option('objspacebackup-bucket');
	            $parseUrl = parse_url(trim(home_url()));
	            $url = $parseUrl['host'];
	            if( isset($parseUrl['path']) ) 
	                { $url .= $parseUrl['path']; }
	            
	            // Rename file
	            $newname = $url.'/'.date_i18n('Y-m-d-His', current_time('timestamp')) . '.zip';
	            OBJSPACEBACKUP::logger('New filename '. $newname .'.');
	
				// Uploading
	            set_time_limit(180);
	
				OBJSPACEBACKUP::logger('Beginning upload to ObjSpace servers.');
	
				// Check the size of the file before we upload, in order to compensate for large files
				if ( @filesize($file) >= (100 * 1024 * 1024) ) {
	
					// Files larger than 100megs go through Multipart
					OBJSPACEBACKUP::logger('Filesize is over 100megs, using Multipart uploader.');
					
					// High Level
					OBJSPACEBACKUP::logger('Prepare the upload parameters and upload parts in 25M chunks.');
					
					$uploader = UploadBuilder::newInstance()
					    ->setClient($s3)
					    ->setSource($file)
					    ->setBucket($bucket)
					    ->setKey($newname)
					    ->setMinPartSize(25 * 1024 * 1024)
					    ->setOption('Metadata', array(
					        'UploadedBy' => 'ObjSpaceBackupPlugin',
					        'UploadedDate' => date_i18n('Y-m-d-His', current_time('timestamp'))
					    ))
					    ->setOption('ACL', 'private')
					    ->setConcurrency(3)
					    ->build();
					
					// This will be called in the following try
					$uploader->getEventDispatcher()->addListener(
					    'multipart_upload.after_part_upload', 
					    function($event) {
					        OBJSPACEBACKUP::logger( 'Part '. $event["state"]->count() . ' uploaded ...');
					    }
					);
					
					try {
						OBJSPACEBACKUP::logger('Begin upload. This may take a while (5min for every 75 megs or so).');
						set_time_limit(180);
					    $uploader->upload();
					    OBJSPACEBACKUP::logger('Upload complete');
					} catch (MultipartUploadException $e) {
					    $uploader->abort();
					    OBJSPACEBACKUP::logger('Upload failed: '.$e->getMessage() );
					}
	
				} else {
					// If it's under 100megs, do it the old way
					OBJSPACEBACKUP::logger('Filesize is under 100megs. This will be less spammy.');
					
					set_time_limit(180); // 3 min 
					try {
						$result = $s3->putObject(array(
						    'Bucket'       => $bucket,
						    'Key'          => $newname,
						    'SourceFile'   => $file,
						    'ContentType'  => 'application/zip',
						    'ACL'          => 'private',
						    'Metadata'     => array(
						        'UploadedBy'   => 'ObjSpaceBackupPlugin',
						        'UploadedDate' => date_i18n('Y-m-d-His', current_time('timestamp')),
						    )
						));
						OBJSPACEBACKUP::logger('Upload complete');
					} catch (S3Exception $e) {
					    OBJSPACEBACKUP::logger('Upload failed: '. $e->getMessage() );
					}
				}
				
/*
				// https://dreamxtream.wordpress.com/2013/10/29/aws-php-sdk-logging-using-guzzle/
				$s3->getEventDispatcher()->removeSubscriber($logPlugin);
*/
			} else {
				OBJSPACEBACKUP::logger('Nothing to upload.');
			}

            // Cleanup
            if(file_exists($file)) { 
                @unlink($file);
                OBJSPACEBACKUP::logger('Deleting zip file: '.$file.' ...');
            }
            if(file_exists($sqlfile)) { 
                @unlink($sqlfile);
                OBJSPACEBACKUP::logger('Deleting SQL file: '.$sqlfile.' ...');
            }
        }
        
        // Cleanup Old Backups
        OBJSPACEBACKUP::logger('Checking for backups to be deleted.');
        if ( $backup_result = 'Yes' && get_option('objspacebackup-retain') && get_option('objspacebackup-retain') != 'all' ) {
            $num_backups = get_option('objspacebackup-retain');

		  	$s3 = AwsS3OBJSPACEBACKUP::factory(array(
				'key'      => get_option('objspacebackup-key'),
			    'secret'   => get_option('objspacebackup-secretkey'),
			    'base_url' => 'http://obj.space',
			));

            $bucket = get_option('objspacebackup-bucket');
            
            $parseUrl = parse_url(trim(home_url()));
            $prefixurl = $parseUrl['host'];
            if( isset($parseUrl['path']) ) 
                { $prefixurl .= $parseUrl['path']; }
            
            $backups = $s3->getIterator('ListObjects', array('Bucket' => $bucket, "Prefix" => $prefixurl ) );
            
            if ($backups !== false) {
            	$backups = $backups->toArray();
                krsort($backups);
                $count = 0;
                foreach ($backups as $object) {
                    if ( ++$count > $num_backups ) {
                        $s3->deleteObject( array(
                        	'Bucket' => $bucket,
                        	'Key'    => $object['Key'],
                        ));
                        OBJSPACEBACKUP::logger('Removed backup '. $object['Key'] .' from ObjSpace, per user retention choice.');
                    }    
                }
            }
        } else {
	        OBJSPACEBACKUP::logger('Per user retention choice, not deleteing a single old backup.');
        }
        OBJSPACEBACKUP::logger('Backup Complete.');
        OBJSPACEBACKUP::logger('');
    }
    function cron_schedules($schedules) {
        $schedules['daily'] = array('interval'=>86400, 'display' => 'Once Daily');
        $schedules['weekly'] = array('interval'=>604800, 'display' => 'Once Weekly');
        $schedules['monthly'] = array('interval'=>2592000, 'display' => 'Once Monthly');
        return $schedules;
    }
}