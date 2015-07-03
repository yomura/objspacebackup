<?php

/*
Plugin Name: ObjSpace Backups
Plugin URI: https://github.com/yomura/objspacebackup
Description: Connect your WordPress install to your ObjSpace buckets.
Version: 0.0.1
Author: Yomura
Author URI: http://obj.space/
Network: false
Text Domain: objspacebackup
Domain Path: /i18n

Copyright 2012 Mika Epstein (email: ipstenu@ipstenu.org)
Modified by Yomura to support ObjSpace

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

/**
 * @package objspacebackup-backups
 */
 
function objspacebackup_incompatibile( $msg ) {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
	deactivate_plugins( __FILE__ );
    wp_die( $msg );
}

if ( is_admin() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) {

	require_once ABSPATH . '/wp-admin/includes/plugin.php';
		
	if ( version_compare( PHP_VERSION, '5.3.3', '<' ) ) {
		objspacebackup_incompatibile( __( 'The official Amazon Web Services SDK, which ObjSpace Backups relies on, requires PHP 5.3 or higher. The plugin has now disabled itself.', 'objspacebackup' ) );
	}
	elseif ( !function_exists( 'curl_version' ) 
		|| !( $curl = curl_version() ) || empty( $curl['version'] ) || empty( $curl['features'] )
		|| version_compare( $curl['version'], '7.16.2', '<' ) )
	{
		objspacebackup_incompatibile( __( 'The official Amazon Web Services SDK, which ObjSpace Backups relies on, requires cURL 7.16.2+. The plugin has now disabled itself.', 'objspacebackup' ) );
	}
	elseif ( !( $curl['features'] & CURL_VERSION_SSL ) ) {
		objspacebackup_incompatibile( __( 'The official Amazon Web Services SDK, which ObjSpace Backups relies on, requires that cURL is compiled with OpenSSL. The plugin has now disabled itself.', 'objspacebackup' ) );
	}
	elseif ( !( $curl['features'] & CURL_VERSION_LIBZ ) ) {
		objspacebackup_incompatibile( __( 'The official Amazon Web Services SDK, which ObjSpace Backups relies on, requires that cURL is compiled with zlib. The plugin has now disabled itself.', 'objspacebackup' ) );
	} elseif ( is_multisite() ) {
		objspacebackup_incompatibile( __( 'Sorry, but ObjSpace Backups is not currently compatible with WordPress Multisite, and should not be used. The plugin has now disabled itself.', 'objspacebackup' ) );
	} elseif (is_plugin_active( 'amazon-web-services/amazon-web-services.php' )) {
	objspacebackup_incompatibile( __( 'Running both ObjSpace Backups AND BackupBuddy at once will cause a rift in the space/time continuum, because we use different versions of the AWS SDK. Please deactivate BackupBuddy if you wish to use ObjSpace.', 'objspacebackup' ) );
	} elseif (is_plugin_active( 'backupbuddy/backupbuddy.php' )) {
	objspacebackup_incompatibile( __( 'Running both ObjSpace Backups AND Amazon Web Services at once will cause a rift in the space/time continuum, because we use different versions of the AWS SDK. Please deactivate Amazon Web Services if you wish to use ObjSpace.', 'objspacebackup' ) );
	}
}
 
require_once 'lib/defines.php';
require_once 'lib/objspace.php';
require_once 'lib/messages.php';
require_once 'lib/settings.php';

if (false === class_exists('Symfony\Component\ClassLoader\UniversalClassLoader', false)) {
	require_once 'aws/aws-autoloader.php';
}

// WP-CLI
if ( defined('WP_CLI') && WP_CLI ) {
	include( 'lib/wp-cli.php' );
}