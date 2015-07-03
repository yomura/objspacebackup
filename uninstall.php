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

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

// Deregister
    delete_option( 'objspacebackup-backupsection' );
    delete_option( 'objspacebackup-bucket' );
    delete_option( 'objspacebackup-bucketup' );
    delete_option( 'objspacebackup-key' );
    delete_option( 'objspacebackup-schedule' );
    delete_option( 'objspacebackup-secretkey' );
    delete_option( 'objspacebackup-section' );
    delete_option( 'objspacebackup-uploader' );
    delete_option( 'objspacebackup-uploadview' );
    delete_option( 'objspacebackup-logging' );
    delete_option( 'objspacebackup-debugging' );
    delete_option( 'objspacebackup-boto' );

// Unschedule
    wp_clear_scheduled_hook( 'objspacebackup-backupnow');
    wp_clear_scheduled_hook( 'objspacebackup-backup');