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

use Aws\S3\S3Client as AwsS3OBJSPACEBACKUPBACK;

?>
<script type="text/javascript">
    var ajaxTarget = "<?php echo OBJSPACEBACKUP::getURL() ?>backup.ajax.php";
    var nonce = "<?php echo wp_create_nonce('objspacebackup'); ?>";
</script>

<div class="wrap">
    <div id="icon-objspacebackup" class="icon32"></div>
    <h2><?php echo __("Backups", objspacebackup); ?></h2>

    <div id="dho-primary">
    	<div id="dho-content">
    		<div id="dho-leftcol">
                    <form method="post" action="options.php">
                        <?php
                            settings_fields( 'objspacebackup-backuper-settings' );
                            do_settings_sections( 'objspacebackup-backuper_page' );
                            submit_button(__('Update Options','objspacebackup'), 'primary');
                        ?>
                    </form>
    			</div>
    			<div id="dho-rightcol">
                    <?php if ( get_option('objspacebackup-bucket') && ( !get_option('objspacebackup-bucket') || (get_option('objspacebackup-bucket') != "XXXX") ) ) { ?>
                    <?php 
                        $num_backups = get_option('objspacebackup-retain');
                        if ( $num_backups == 'all') { $num_backups = 'WP';}
                        $show_backup_header = sprintf(__('Latest %s Backups', objspacebackup),$num_backups ); 
                    ?>
                    
                    <h3><?php echo $show_backup_header; ?></h3>
                
                    <div id="backups">
                        <ul><?php 
                            if ( (get_option('objspacebackup-bucket') != "XXXX") && !is_null(get_option('objspacebackup-bucket')) ) {

								?><p><?php echo __('All backups can be downloaded from this page without logging in to ObjSpace.', objspacebackup); ?></p><?php
									$timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', (time()+600) ), get_option('time_format') );
									$string = sprintf( __('Links are valid until %s (aka 10 minutes from page load). After that time, you need to reload this page.', objspacebackup), $timestamp );									
								?><p><?php echo $string; ?></p><?php

								$s3 = AwsS3OBJSPACEBACKUPBACK::factory(array(
									'key'    => get_option('objspacebackup-key'),
									'secret' => get_option('objspacebackup-secretkey'),
									'base_url' => 'http://obj.space',
								));
                    
                                $bucket = get_option('objspacebackup-bucket');
                                $prefix = next(explode('//', home_url()));
                                
                                try {
                                	$objects = $s3->getIterator('ListObjects', array('Bucket' => $bucket, 'Prefix' => $prefix));
									$objects = $objects->toArray();
									krsort($objects);
                                
	                                echo '<ol>';
									foreach ($objects as $object) {
									    echo '<li><a href="'.$s3->getObjectUrl($bucket, $object['Key'], '+10 minutes').'">'.$object['Key'] .'</a> - '.size_format($object['Size']).'</li>';								    
									}
									echo '</ol>';
								} catch (S3Exception $e) {
									echo __('There are no backups currently stored. Why not run a backup now?');
								}
                                
                    		} // if you picked a bucket
                    					?>
                         </ul>
                     </div>
                
                     <form method="post" action="admin.php?page=objspacebackup-menu-backup&backup-now=true">
                         <input type="hidden" name="action" value="backup" />
                         <?php wp_nonce_field('dhdo-backupnow'); ?>
                         <h3><?php echo __('Backup ASAP!', objspacebackup); ?></h3>
                         <p><?php echo __('Oh you really want to do a backup right now? Schedule your backup to start in a minute. Be careful! This may take a while, and slow your site down, if you have a big site. Also if you made any changes to your settings, go back and click "Update Options" before running this.', objspacebackup); ?></p>
                
                         <?php
                             $timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'objspacebackup-backup' ) ), get_option('date_format').' '.get_option('time_format') );
                             $nextbackup = sprintf(__('Keep in mind, your next scheduled backup is at %s', objspacebackup), $timestamp ); 
                         
                        if ( get_option('objspacebackup-schedule') != "disabled" && wp_next_scheduled('objspacebackup-backup') ) {?>
                         <p><?php echo $nextbackup; ?></p>
                         <?php } 
                         
                         submit_button( __('Backup ASAP','objspacebackup'), 'secondary'); ?>
                    </form>
                    <?php } ?>
    		</div>
    	</div>
    </div>
</div>