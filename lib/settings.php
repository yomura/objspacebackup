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

use Aws\S3\S3Client as AwsS3OBJSPACEBACKUPSET;

class OBJSPACEBACKUPSET {
    /**
     * Generates the settings page
     *
    */

    // Add Settings Pages
    public static function add_settings_page() {
        load_plugin_textdomain(objspacebackup, OBJSPACEBACKUP_PLUGIN_DIR . 'i18n', 'i18n');
        add_action('admin_init', array('OBJSPACEBACKUPSET', 'add_register_settings'));
        add_menu_page(__('ObjSpace Settings', 'objspacebackup'), __('ObjSpace', 'objspacebackup'), 'manage_options', 'objspacebackup-menu', array('OBJSPACEBACKUPSET', 'settings_page'), 'dashicons-backup' );
        
        if ( get_option('objspacebackup-key') && get_option('objspacebackup-secretkey') ) {
            add_submenu_page('objspacebackup-menu', __('Backups', 'objspacebackup'), __('Backups', 'objspacebackup'), 'manage_options', 'objspacebackup-menu-backup', array('OBJSPACEBACKUPSET', 'backup_page'));  
        }
    }

    // Define Settings Pages    
    public static function  settings_page() {
        include_once( OBJSPACEBACKUP_PLUGIN_DIR . '/admin/settings.php');// Main Settings
    }
    
    // This isn't used yet
    public static function  backup_page() {
        include_once( OBJSPACEBACKUP_PLUGIN_DIR . '/admin/backups.php'); // Backup Settings
    }

    // Register Settings (for forms etc)
    public static function add_register_settings() {

     // Keypair settings
        add_settings_section( 'keypair_id', __('ObjSpace Backups Access Settings', 'objspacebackup'), 'keypair_callback', 'objspacebackup-keypair_page' );
        
        register_setting( 'objspacebackup-keypair-settings','objspacebackup-key');
        add_settings_field( 'key_id', __('Access Key', 'objspacebackup'), 'key_callback', 'objspacebackup-keypair_page', 'keypair_id' );
        
        register_setting( 'objspacebackup-keypair-settings','objspacebackup-secretkey');
        add_settings_field( 'secretkey_id', __('Secret Key', 'objspacebackup'), 'secretkey_callback', 'objspacebackup-keypair_page', 'keypair_id' );

        function keypair_callback() { 
            echo '<p>'. __("Once you've configured your keypair here, you'll be able to use the features of this plugin.", objspacebackup).'</p>';
            echo '<p><div class="dashicons dashicons-shield"></div>'.__( "Once saved, your keys will not display again for your own security.", objspacebackup ).'</p>';
        }
    	function key_callback() {
        	echo '<input type="text" name="objspacebackup-key" value="'. get_option('objspacebackup-key') .'" class="regular-text" autocomplete="off"/>';
    	}
    	function secretkey_callback() {
        	echo '<input type="text" name="objspacebackup-secretkey" value="'. get_option('objspacebackup-secretkey') .'" class="regular-text" autocomplete="off" />';
    	}

     // Backup Settings
        add_settings_section( 'backuper_id', __('Settings', 'objspacebackup'), 'backuper_callback', 'objspacebackup-backuper_page' );
        
        register_setting( 'objspacebackup-backuper-settings','objspacebackup-bucket');
        add_settings_field( 'objspacebackup-bucket_id',  __('Bucket Name', 'objspacebackup'), 'backup_bucket_callback', 'objspacebackup-backuper_page', 'backuper_id' );

        if ( get_option('objspacebackup-bucket') && ( !get_option('objspacebackup-bucket') || (get_option('objspacebackup-bucket') != "XXXX") ) ) {
            register_setting( 'objspacebackup-backuper-settings','objspacebackup-backupsection');
            add_settings_field( 'objspacebackup-backupsection_id',  __('What to Backup', 'objspacebackup'), 'backup_what_callback', 'objspacebackup-backuper_page', 'backuper_id' );
            register_setting( 'objspacebackup-backuper-settings','objspacebackup-schedule');
            add_settings_field( 'objspacebackup-schedule_id',  __('Schedule', 'objspacebackup'), 'backup_sched_callback', 'objspacebackup-backuper_page', 'backuper_id' );
            register_setting( 'objspacebackup-backuper-settings','objspacebackup-retain');
            add_settings_field( 'objspacebackup-backupretain_id',  __('Backup Retention', 'objspacebackup'), 'backup_retain_callback', 'objspacebackup-backuper_page', 'backuper_id' );
        }
        
        function backuper_callback() { 
            echo 'Configure your site for backups by selecting your bucket, what you want to backup, and when.';
        }
        function backup_bucket_callback() {
        	$s3 = AwsS3OBJSPACEBACKUPSET::factory(array(
				'key'    => get_option('objspacebackup-key'),
			    'secret' => get_option('objspacebackup-secretkey'),
			    'base_url' => 'http://obj.space',
			));
 
            $buckets = $s3->listBuckets();
            
            ?> <select name="objspacebackup-bucket">
                    <option value="XXXX">(select a bucket)</option>
                    <?php foreach ( $buckets['Buckets'] as $bucket ) : ?>
                    <option <?php if ( $bucket['Name'] == get_option('objspacebackup-bucket') ) echo 'selected="selected"' ?> ><?php echo $bucket['Name'] ?></option>
                    <?php endforeach; ?>
                </select>
				<p class="description"><?php echo __('Select from pre-existing buckets.', objspacebackup); ?></p>
				<?php if ( get_option('objspacebackup-bucketup') && ( !get_option('objspacebackup-bucketup') || (get_option('objspacebackup-bucketup') != "XXXX") ) ) { 
    				$alreadyusing = sprintf(__('You are currently using the bucket "%s" for Uploads. While you can reuse this bucket, it would be best not to.', objspacebackup), get_option('objspacebackup-bucketup')  );
    				echo '<p class="description">' . $alreadyusing . '</p>';
                }
    	}

    	function backup_what_callback() {
        	$sections = get_option('objspacebackup-backupsection');
    		if ( !$sections ) {
    			$sections = array();
    		}
        	?><p><label for="objspacebackup-backupsection-files">
				<input <?php if ( in_array('files', $sections) ) echo 'checked="checked"' ?> type="checkbox" name="objspacebackup-backupsection[]" value="files" id="objspacebackup-backupsection-files" />
				<?php echo __('All Files', objspacebackup); ?>
				</label><br />
				<label for="objspacebackup-backupsection-database">
				<input <?php if ( in_array('database', $sections) ) echo 'checked="checked"' ?> type="checkbox" name="objspacebackup-backupsection[]" value="database" id="objspacebackup-backupsection-database" />
				<?php echo __('Database', objspacebackup); ?>
				</label><br />
				</p>
				<p class="description"><?php echo __('You can select portions of your site to backup.', objspacebackup); ?></p><?php
        }


    	function backup_sched_callback() {
    	
            ?><select name="objspacebackup-schedule">
				<?php foreach ( array('Disabled','Daily','Weekly','Monthly') as $s ) : ?>
				<option value="<?php echo strtolower($s) ?>" <?php if ( strtolower($s) == get_option('objspacebackup-schedule') ) echo 'selected="selected"' ?>><?php echo $s ?></option>
				<?php endforeach; ?>
				</select>
				<?php
                  $timestamp = get_date_from_gmt( date( 'Y-m-d H:i:s', wp_next_scheduled( 'objspacebackup-backup' ) ), get_option('date_format').' '.get_option('time_format') );
                  $nextbackup = sprintf(__('Next scheduled backup is at %s', objspacebackup), $timestamp );
            ?>
            <p class="description"><?php echo __('How often do you want to backup your files? Daily is recommended.', objspacebackup); ?></p>
            <?php if ( get_option('objspacebackup-schedule') != "disabled" && wp_next_scheduled('objspacebackup-backup') ) { ?>
            <p class="description"><?php echo $nextbackup; ?></p>
            <?php }
    	}
    	

    	function backup_retain_callback() {
            ?><select name="objspacebackup-retain">
				    <?php foreach ( array('1','2','5','10','15','30','60','90','all') as $s ) : ?>
				        <option value="<?php echo strtolower($s) ?>" <?php if ( strtolower($s) == get_option('objspacebackup-retain') ) echo 'selected="selected"' ?>><?php echo $s ?></option>
				    <?php endforeach; ?>
				</select>
				<p class="description"><?php echo __('How many many backups do you want to keep? 15 is recommended.', objspacebackup); ?></p>
				<p class="description"><div class="dashicons dashicons-info"></div> <?php echo __('ObjSpace charges you based on diskspace used. Setting to \'All\' will retain your backups forwever, however this can cost you a large sum of money over time. Please use cautiously!', objspacebackup); ?></p>
		<?php
    	}

    // Reset Settings
        register_setting( 'objspacebackup-reset-settings', 'objspacebackup-reset');
    // Logging Settings
        register_setting( 'objspacebackup-logging-settings', 'objspacebackup-logging');
    // Backup Bucket Settings
        register_setting( 'do-do-new-bucket-settings', 'objspacebackup-new-bucket');
    }
}