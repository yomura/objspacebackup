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

$screen = get_current_screen();

// For the ObjSpace Page
if ($screen->id == 'toplevel_page_objspacebackup-menu') {

    // Introduction
    $screen->add_help_tab( array(
		'id'      => 'objspacebackup-menu-base',
		'title'   => __('Overview', 'objspacebackup'),
		'content' => 
		'<h3>' . __('Welcome to ObjSpace Backups', 'objspacebackup') .'</h3>' .
		'<p>' . __( 'ObjSpace&#153; is an inexpensive, scalable object storage service that was developed from the ground up to provide a reliable, flexible cloud storage solution for entrepreneurs and developers. It provides a perfect, scalable storage solution for your WordPress site.', 'objspacebackup' ) . '</p>' .
		'<p>' . __( 'If you haven\'t already signed up for ObjSpace, you won\'t find this plugin of any use at all.', 'objspacebackup' ) . '</p>'
		));
    $screen->set_help_sidebar(
        '<h4>' . __('For more information:', 'objspacebackup') .'</h4>' .
        
        '<p><a href="http://help.obj.space/">' . __('ObjSpace Wiki', 'objspacebackup' ) . '</a></p>'
        );

    // Setup
    $screen->add_help_tab( array(
		'id'      => 'objspacebackup-menu-signup',
		'title'   => __('Setup', 'objspacebackup'),
		'content' =>
		'<h3>' . __('Setup', 'objspacebackup') .'</h3>' .
		'<ol>' .
		  '<li>' . __( 'Sign up for <a href="http://www.obj.space">ObjSpace</a>', objspacebackup ) . '</li>' .
		  '<li>' . __( 'Install and Activate the plugin', objspacebackup ) . '</li>' .
		  '<li>' . __( 'Fill in your Key and Secret Key', objspacebackup ) . '</li>' .
        '</ol>'
	  ));
    
    // Terminology
    $screen->add_help_tab( array(
		'id'      => 'objspacebackup-menu-terms',
		'title'   => __('Terminology', 'objspacebackup'),
		'content' =>
		'<h3>' . __('Terminology', 'objspacebackup') .'</h3>' .
		'<p><strong>' . __( 'Object: ', 'objspacebackup') .'</strong>' . __( 'Files uploaded to ObjSpace.', 'objspacebackup') . '</p>' .
		'<p><strong>' . __( 'Bucket: ', 'objspacebackup') .'</strong>' . __( 'A mechanism for grouping objects, similar to a folder. One key distinction is that bucket names must be unique, like a domain name, since they are used to create public URLs to stored objects.', 'objspacebackup') . '</p>' .
		'<p><strong>' . __( 'Access Key: ', 'objspacebackup') .'</strong>' . __( 'A similar concept to a username for ObjSpace users. One or more can be created for each user if desired. Each access key will allow access to all of the buckets and their contents for a user. You will need this key to connect.', 'objspacebackup') . '</p>' .
		'<p><strong>' . __( 'Secret Key: ', 'objspacebackup') .'</strong>' . __( 'A similar concept to a password for ObjSpace users. A secret key is automatically generated for each access key and cannot be changed. Never give anyone your secret key.', 'objspacebackup') . '</p>' .
		'<p><strong>' . __( 'Key Pair: ', 'objspacebackup') .'</strong>' . __( 'A singular term used to describe both an access key and its secret key.', 'objspacebackup') . '</p>'
	  ));
	   }

// Backup Page
if ($screen->id == 'objspacebackup_page_objspacebackup-menu-backup') {
    
    // Base Help
    $screen->add_help_tab( array(
		'id'      => 'objspacebackup-menu-backup-base',
		'title'   => __('Overview', 'objspacebackup'),
		'content' => 
		'<h3>' . __('ObjSpace Backups', 'objspacebackup') .'</h3>' .
		'<p>' . __( 'Backing up your WordPress site to ObjSpace will allow you to have a safe and secure backup of your site. This is useful to run before you upgrade WordPress, or make big changes.', 'objspacebackup' ) . '</p>' .
		'<p>' . __( 'Backups can be scheduled to run daily, weekly or monthly. You also have the option to run a backup right now.', 'objspacebackup' ) . '</p>' .
		'<p>' . __( 'The default backup retention is 15 backups, however you can change this t0 30, 60, 90, or all backups (where \'all\' is all backups, forever and ever). Keep in mind you will be charged for the space you use, so chose wisely.', 'objspacebackup' ) . '</p>'
      ));
	}
else
    return;