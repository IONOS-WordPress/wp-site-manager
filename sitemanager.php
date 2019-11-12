<?php
/**
 * Plugin Name:  Auto Updater
 * Plugin URI:   https://github.com/1and1/wp-site-manager
 * Description:  Manage and track automatic updates for your WordPress installation.
 * Version:      1.1.4
 * License:      GPLv2 or later
 * Author:       IONOS
 * Author URI:   https://www.ionos.com
 * Text Domain:  sitemanager
 * Domain Path:  /languages
 */

/*
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Online: http://www.gnu.org/licenses/gpl.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Site_Manager {
	public static $version = '1.1.2';

	private $objects = array();

	/* Only have the needed code here */
	public function __construct() {
		if ( is_admin() ) {
			$this->load_admin();
		}

		if ( is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			$this->load_logger();
		}

		add_action( 'init', array( $this, 'maybe_load_auto_update_all' ), 1 );

		register_activation_hook( __FILE__, array( $this, 'on_activation' ) );
	}

	private function load_admin() {
		include dirname( __FILE__ ) . '/inc/admin.php';

		$this->objects['admin'] = new Site_Manager_Admin;

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	private function load_logger() {
		include dirname( __FILE__ ) . '/inc/changelog.php';
		include dirname( __FILE__ ) . '/inc/logger.php';

		$this->objects['changelog'] = new Site_Manager_Changelog();
		$this->objects['logger']    = new Site_Manager_Logger();
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'sitemanager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	public function maybe_load_auto_update_all() {
		if (
			( current_user_can( 'update_core' ) && ! defined( 'WP_CLI' ) ) ||
			( defined( 'DOING_CRON' ) && DOING_CRON )
		) {
			include_once dirname( __FILE__ ) . '/inc/update.php';

			new Site_Manager_Update();
		}
	}

	public function on_activation() {
		if ( ! get_option( 'site-manager', false ) ) {
			update_option( 'site-manager', Site_Manager_Admin::default_option_values() );
		}

		include_once dirname( __FILE__ ) . '/inc/update.php';

		$this->maybe_load_auto_update_all();
		Site_Manager_Update::can_auto_update();
	}
}

$site_manager = new Site_Manager;
