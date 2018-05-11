<?php

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class Site_Manager_Update {
	public static $major_update_days = 7;

	private static $can_auto_update;
	private static $is_auto_updating = false;

	public function __construct() {
		$this->auto_update_all();

		add_filter( 'request_filesystem_credentials', array( $this, 'fine_update_all_except_core' ), 10, 5 );
		add_filter( 'filesystem_method', array( $this, 'filesystem_method_direct' ), 10, 5 );

		// Know when we are auto updating
		add_filter( 'pre_update_option_auto_updater.lock', array( $this, 'show_updates' ) );
		add_action( 'delete_option_auto_updater.lock', array( $this, 'hide_updates' ) );
	}

	public static function is_auto_updating() {
		return self::$is_auto_updating;
	}

	public static function can_auto_update( $type = 'core' ) {
		global $wp_version, $required_php_version, $required_mysql_version;

		if ( isset( self::$can_auto_update[ $type ] ) ) {
			return self::$can_auto_update[ $type ];
		}

		switch ( $type ) {
			case 'core':
				$context = ABSPATH;

				if ( ! current_user_can( 'update_core' ) ) {
					self::$can_auto_update[ $type ] = false;
					return false;
				}

				break;
			case 'plugin':
				$context = WP_PLUGIN_DIR; // We don't support custom Plugin directories, or updates for WPMU_PLUGIN_DIR
				break;
			case 'theme':
				$context = get_theme_root();
				break;
			case 'translation':
				$context = WP_CONTENT_DIR; // WP_LANG_DIR;
				break;
			default:
				return false;
				break;
		}

		$future_minor_update = (object) array(
			'current'       => $wp_version . '.1.next.minor',
			'version'       => $wp_version . '.1.next.minor',
			'php_version'   => $required_php_version,
			'mysql_version' => $required_mysql_version,
		);

		// Return true so that the updater thinks it is possble
		add_filter( 'auto_update_' . $type, '__return_true' );

		// Check if we can update
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		$updater = new WP_Automatic_Updater;

		self::$can_auto_update[ $type ] = wp_http_supports( array( 'ssl' ) ) && $updater->should_update( $type, $future_minor_update, $context );

		// Remove filter again so we don't do a real update when something is false
		remove_filter( 'auto_update_' . $type, '__return_true' );

		return self::$can_auto_update[ $type ];
	}



	public function auto_update_all() {
		$updates = get_option( 'site-manager', array() );

		// Automattic update plugins
		if ( 'on' == $updates['plugins'] ) {
			add_filter( 'auto_update_plugin', '__return_true' );
		}

		// Automattic update themes
		if ( 'on' == $updates['themes'] ) {
			add_filter( 'auto_update_theme', '__return_true' );
		}

		// Automattic update translations
		if ( 'on' == $updates['translations'] ) {
			add_filter( 'auto_update_translation', '__return_true' );
		}

		// Automattic update core for major versions
		if ( 'on' == $updates['major'] ) {
			add_filter( 'allow_major_auto_core_updates', '__return_true' );
		}
	}

	public function fine_update_all_except_core( $value, $url, $type, $error, $context ) {
		if ( WP_PLUGIN_DIR == $context || get_theme_root() == $context || WP_CONTENT_DIR == $context ) {
			$value = true;
		}

		return $value;
	}

	public function filesystem_method_direct( $method, $args ) {
		if ( self::$is_auto_updating ) {
			return 'direct';
		}

		return $method;
	}

	public function show_updates( $return_value ) {
		self::$is_auto_updating = true;

		return $return_value;
	}

	public function hide_updates() {
		self::$is_auto_updating = false;
	}

}
