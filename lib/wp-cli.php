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


if( class_exists( 'OBJSPACEBACKUP' ) ) {
	WP_CLI::add_command( 'objspacebackup', 'ObjSpace_Command' );
}

/**
 * Manage the ObjSpace plugin
 *
 * @package wp-cli
 * @subpackage commands/community
 * @maintainer Mika A. Epstein
 */
class ObjSpace_Command extends WP_CLI_Command {

	/**
	 * Run a backup right now
	 *
	 * @param array $args
	 * @param array $vars
	 */
	function backup( $args = array(), $vars = array() ) {
		do_action('objspacebackup-backup', array('OBJSPACEBACKUP', 'backup'));
		WP_CLI::success( 'Backup Complete' );
	}
	
	function resetlog( $args = array(), $vars = array() ) {
		OBJSPACEBACKUP::logger('reset');
		WP_CLI::success( 'Debug log wiped' );
	}

	/**
	 * Help function for this command
	 */
	public static function help() {
		WP_CLI::line( <<<EOB
usage: wp objspacebackup [backup]

	backup    run a backup now
	resetlog  wipe the debug log

EOB
	);
	}
}