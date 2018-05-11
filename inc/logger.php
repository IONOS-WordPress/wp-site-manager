<?php

if ( ! defined('ABSPATH') ) {
	die();
}

class Site_Manager_Logger {

	/**
	 * Since WordPress doesn't return the version numbers we need to retrieve them before updating
	 *
	 * @var array
	 */
	private static $versions_cache = array();

	public function __construct() {
		add_filter( 'pre_update_option_auto_updater.lock', array( $this, 'cache_current_versions' ) );

		add_action( 'current_screen', array( $this, 'current_screen_cache_current_versions' ), 20 );

		add_action( 'automatic_updates_complete', array( $this, 'log_automatic_updates' ) );

		add_action( 'log_automatic_core_updates', array( $this, 'log_automatic_core_updates' ) );
		add_action( 'log_automatic_translation_updates', array( $this, 'log_automatic_translation_updates' ) );
		add_action( 'log_automatic_plugin_updates', array( $this, 'log_automatic_plugin_updates' ) );
		add_action( 'log_automatic_theme_updates', array( $this, 'log_automatic_theme_updates' ) );
	}

	public function cache_current_versions( $value = '' ) {
		global $wp_version;

		set_transient( 'sitemanager_version_core', $wp_version );
		set_transient( 'sitemanager_version_plugins', get_plugins() );

		$themes = array();
		// Different logic due reference.
		foreach ( wp_get_themes() as $stylesheet => $theme_data ) {
			$themes[ $theme_data->get( 'Name' ) ] = $theme_data->get( 'Version' );
		}

		set_transient( 'sitemanager_version_themes', $themes );

		return $value;
	}

	public function current_screen_cache_current_versions( $screen ) {
		if ( 'update' == $screen->id ) {
			$actions = array(
				'install-theme',
				'upgrade-theme',
				'install-plugin',
				'upgrade-plugin',
			);

			if ( in_array( $_GET['action'], $actions ) ) {
				$this->cache_current_versions( '' );
			}
		}
	}

	/**
	 * Logs the automatic updates
	 *
	 * @param array $update_result the update results
	 */
	public function log_automatic_updates( $update_result ) {
		foreach ( $update_result as $type => $items ) {
			foreach ( $items as $item ) {
				if ( $item->result && ! is_wp_error( $item->result ) ) {
					do_action( "log_automatic_{$type}_updates", $item );
				}
			}
		}
	}

	public function log_automatic_plugin_updates( $item ) {
		$readme_file = WP_PLUGIN_DIR . '/' . $item->item->slug . '/readme.txt';

		$plugins = get_transient( 'sitemanager_version_plugins' );

		if ( isset( $plugins[ $item->item->plugin ] ) ) {
			$previous_version = $plugins[ $item->item->plugin ]['Version'];
			$content = $this->get_changes_since_last_update( $readme_file, $plugins[ $item->item->plugin ]['Version'] );
		} else {
			$previous_version = '-';
			$content = $this->get_changes_since_last_update( $readme_file, '' );
		}

		$this->log_update( $item->name, $content, 'Plugin', $item->item->new_version, $previous_version, is_plugin_active( $item->item->slug ) );
	}

	public function log_automatic_theme_updates( $item ) {
		$themes = get_transient( 'sitemanager_version_themes' );

		if ( isset( $themes[ $item->name ] ) ) {
			$previous_version = $themes[ $item->name ];
		} else {
			$previous_version = '-';
		}

		$this->log_update( $item->name, '-', 'Theme', $item->item->new_version, $previous_version );
	}

	public function log_automatic_core_updates( $item ) {
		$core = get_transient( 'sitemanager_version_core' );

		$this->log_update( $item->name, '-', 'Core', $item->result, $core );
	}

	public function log_automatic_translation_updates( $item ) {
		$this->log_update( $item->name, '-', 'Translation', '-', '-' );
	}

	/**
	 * Log an Update
	 *
	 * @param string $name the name of the update
	 * @param string $changelog the changelog or the description
	 * @param string $type "Plugin", "Theme", "Core" or "Translation"
	 * @param string $version the new version
	 * @param string $previous_version the version before the upgrade
	 * @param boolean $active
	 * @param string $method "Update" or "Automatic update"
	 */
	public function log_update( $name, $changelog, $type, $version, $previous_version, $active = '-', $method = 'Automatic update' ) {
		$args = array(
			'post_title'  => $name,
			'post_status' => 'publish',
			'post_type'   => Site_Manager_Changelog::post_type,
			'post_content' => $changelog,
		);

		$post_id = wp_insert_post( $args );

		update_post_meta( $post_id, '_version', $version );
		update_post_meta( $post_id, '_is_active', $active );
		update_post_meta( $post_id, '_version_previous', $previous_version );

		wp_set_object_terms( $post_id, $method, 'update_log_method' );
		wp_set_object_terms( $post_id, ucfirst( $type ), 'update_log_category' );
	}

	public function get_changes_since_last_update( $file, $old_version ) {
		$changelog = $this->retrieve_changelog( $file );
		$updates   = '';
		$did_first = false;

		foreach ( $changelog as $version => $content ) {
			$has_first = false;

			if( $old_version && strpos( $version, $old_version ) === 0 ) {
				$did_first = $has_first = true;
			}
			else if ( $did_first ) {
				break;
			}

			$updates .= '<div class="update">';
			$updates .= '<strong>' . $version . '</strong><br/>';
			$updates .= wpautop( $content );
			$updates .= '</div>';

			if( $version == $old_version ) {
				break;
			}
		}

		return trim( $updates );
	}

	/**
	 * Get changelog from readme file
	 *
	 * @param string $file the path to the readme file
	 *
	 * @return array the changelog by version
	 */
	public function retrieve_changelog( $file ) {
		$readme = $this->get_readme( $file );

		if( isset( $readme['changelog'] ) ) {
			$_changelog = preg_split( '/^[\s]*=[\s]*(.+?)[\s]*=/m', $readme['changelog'], -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY );
			$changelog  = array();

			if( count( $_changelog ) > 1 ) {
				for ( $i = 1; $i <= count( $_changelog ); $i += 2 ) {
					$title = $this->sanitize_text( $_changelog[ $i - 1 ] );
					$title = explode(' ',trim( $title ) );

					$changelog[ $title[0] ] = $_changelog[ $i ];
				}

				return $changelog;
			}
		}

		return array();
	}

	public function get_readme( $file ) {
		if ( ! is_file( $file ) ) {
			return false;
		}

		$file_contents = implode( '', file( $file ) );

		$file_contents = str_replace( array( "\r\n", "\r" ), "\n", $file_contents );
		$file_contents = trim($file_contents);

		if ( 0 === strpos( $file_contents, "\xEF\xBB\xBF" ) ) {
			$file_contents = substr( $file_contents, 3 );
		}

		$_sections = preg_split( '/^[\s]*===?[\s]*(.+?)[\s]*===?/m', $file_contents, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY );

		$sections = array();
		for ( $i = 1; $i <= count( $_sections ); $i += 2 ) {
			$title = $this->sanitize_text( $_sections[ $i - 1 ] );
			$sections[ str_replace(' ', '_', strtolower( $title ) ) ] = $_sections[ $i ];
		}

		return $sections;
	}

	public function sanitize_text( $text ) {
		$text = strip_tags( $text );
		$text = trim( $text );

		return $text;
	}
}
