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

// Set up defaults
define( 'OBJSPACEBACKUP', true);
defined( 'OBJSPACEBACKUP_PLUGIN_DIR') || define('OBJSPACEBACKUP_PLUGIN_DIR', realpath(dirname(__FILE__) . '/..'));

// Standard content folder defines.
if ( ! defined( 'WP_CONTENT_DIR' ) )  define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );

// Setting Options
if ( !defined('objspacebackup')) {define('objspacebackup','objspacebackup');} // Translation
if ( !get_option('objspacebackup-key')) {update_option( 'objspacebackup-key', '' );}
if ( !get_option('objspacebackup-secretkey')) {update_option( 'objspacebackup-secretkey', '' );}
if ( !get_option('objspacebackup-bucket')) {update_option( 'objspacebackup-bucket', 'XXXX' );}
if ( !get_option('objspacebackup-schedule')) {update_option( 'objspacebackup-schedule', 'disabled' );}
if ( !get_option('objspacebackup-backupsection')) {update_option( 'objspacebackup-backupsection', '' );}
if ( !get_option('objspacebackup-retain')) {update_option( 'objspacebackup-retain', '5' );}
if ( !get_option('objspacebackup-logging')) {update_option( 'objspacebackup-logging', 'off' );}
//if ( !get_option('objspacebackup-boto')) {update_option( 'objspacebackup-boto', 'no' );}

// For removed features
if ( get_option('objspacebackup-debugging')) { delete_option( 'objspacebackup-debugging'); }
if ( get_option('objspacebackup-bucketup')) { delete_option( 'objspacebackup-bucketup' ); }
if ( get_option('objspacebackup-uploader')) { delete_option( 'objspacebackup-uploader' ); }
if ( get_option('objspacebackup-uploadview')) { delete_option( 'objspacebackup-uploadview' ); }

// The Help Screen
function objspacebackup_plugin_help() {
	include_once( OBJSPACEBACKUP_PLUGIN_DIR . '/admin/help.php' );
}
add_action('contextual_help', 'objspacebackup_plugin_help', 10, 3);

// Filter Cron
add_filter('cron_schedules', array('OBJSPACEBACKUP', 'cron_schedules'));

// Etc
add_action('admin_menu', array('OBJSPACEBACKUPSET', 'add_settings_page'));
add_action('objspacebackup-backup', array('OBJSPACEBACKUP', 'backup'));
add_action('objspacebackup-backupnow', array('OBJSPACEBACKUP', 'backup'));
add_action('init', array('OBJSPACEBACKUP', 'init'));

if ( isset($_GET['page']) && ( $_GET['page'] == 'objspacebackup-backup' || $_GET['page'] == 'objspacebackup-backupnow' ) ) {
	wp_enqueue_script('jquery');
}