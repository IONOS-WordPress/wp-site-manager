<?php

if ( ! defined('ABSPATH') ) {
	die();
}

class Site_Manager_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'current_screen', array( $this, 'register_scripts' ), 1 );
		add_action( 'current_screen', array( $this, 'register_settings' ), 1 );
	}

	public function register_menu() {
		add_submenu_page(
			'edit.php?post_type=update_log',
			__( 'Settings', 'sitemanager' ),
			__( 'Settings', 'sitemanager' ),
			'manage_options',
			'site-manager',
			array( $this, 'settings_page' )
		);
	}

	public function register_scripts() {
		wp_register_style( 'components_switches', plugins_url( 'css/components/switches.css', dirname( __FILE__ ) ), array(), Site_Manager::$version );
		wp_register_style( 'sitemanager_settings', plugins_url( 'css/settings.css', dirname( __FILE__ ) ), array( 'components_switches' ), Site_Manager::$version );
		wp_add_inline_style( 'sitemanager_settings', $this->get_admin_color_styles() );

		wp_register_script( 'sitemanager_admin', plugins_url( 'js/admin.js', dirname( __FILE__ ) ), array( 'jquery' ), Site_Manager::$version );
	}

	public function register_settings( $screen ) {
		if ( 'update_log_page_site-manager' != $screen->base && 'options' != $screen->base ) {
			return;
		}

		register_setting( 'site-manager-group', 'site-manager', array( $this, 'sanitize_checkboxes' ) );

		if ( Site_Manager_Update::can_auto_update() || Site_Manager_Update::can_auto_update('theme') || Site_Manager_Update::can_auto_update('plugin') || Site_Manager_Update::can_auto_update('translation') ) {
			add_settings_section( 'site-manager-update', '', array( 'Site_Manager_Admin', 'render_update_settings' ), 'site-manager' );
		}
	}

	public static function render_update_settings() {
		$updates = get_option( 'site-manager', array() );

		if ( Site_Manager_Update::can_auto_update() ) {
			add_settings_field( 'site-manager-update-major', __( 'Run WordPress updates', 'sitemanager' ), array( 'Site_Manager_Admin', 'switch_on_off' ), 'site-manager', 'site-manager-update', array( 'type' => 'major', 'value' => $updates['major'] ) );
		}

		if ( Site_Manager_Update::can_auto_update('translation') ) {
			add_settings_field( 'site-manager-update-translation', __( 'Run translation updates', 'sitemanager' ), array( 'Site_Manager_Admin', 'switch_on_off' ), 'site-manager', 'site-manager-update', array( 'type' => 'translations', 'value' => $updates['translations'] ) );
		}

		if ( Site_Manager_Update::can_auto_update('theme') ) {
			add_settings_field( 'site-manager-update-theme', __( 'Run theme updates', 'sitemanager' ), array( 'Site_Manager_Admin', 'switch_on_off' ), 'site-manager', 'site-manager-update', array( 'type' => 'themes', 'value' => $updates['themes'], 'description' => __( 'Note: automatic theme updates will <strong>overwrite the theme customizations</strong> you did outside of the WordPress Administration.', 'sitemanager' ) ) );
		}

		if ( Site_Manager_Update::can_auto_update('plugin') ) {
			add_settings_field( 'site-manager-update-plugin', __( 'Run plugin updates', 'sitemanager' ), array( 'Site_Manager_Admin', 'switch_on_off' ), 'site-manager', 'site-manager-update', array( 'type' => 'plugins', 'value' => $updates['plugins'] ) );
		}
	}

	public static function default_option_values() {
		return array(
			'minor'        => 'on',
			'major'        => 'off',
			'themes'       => 'on',
			'plugins'      => 'on',
			'translations' => 'on',
		);
	}

	public static function render_noupdate_text() {

		$vcs_dir = self::is_vcs_checkout();

		$plugin_slug = 'background-update-tester';
		$plugin_file = 'background-update-tester/background-update-tester.php';

		if ( $vcs_dir ) {
			return printf(
				__( 'Your installation appears to be under version control (%s). This prevents the plugin from managing your updates.' , 'sitemanager' ),
				'<code>' . ABSPATH . '</code>',
				'<code>' . $vcs_dir . '</code>'
			);
		}
		else if( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
			$url = wp_nonce_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug, 'install-plugin_' . $plugin_slug );

			return printf(
				__( 'Your installation is currently blocking automatic updates. Install %s to find the cause.', 'sitemanager' ),
				'<a href="' . $url . '">Automatic Update Tester</a>'
			);
		}
		else if( ! is_plugin_active( $plugin_file ) ) {
			$url = wp_nonce_url( 'plugins.php?action=activate&plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file );

			return printf(
				__( "Your installation is currently blocking automatic updates. Activate %s and go to 'Update Tester' under 'Dashboard' to find the cause.", 'sitemanager' ),
				'<a href="' . $url . '">Automatic Update Tester</a>'
			);
		}
		else {
			return printf(
				__( 'Your installation is currently blocking automatic updates. Check %s for more details.', 'sitemanager' ),
				'<a href="' . admin_url( 'index.php?page=background-updates-debugger' ) . '">Automatic Update Tester</a>'
			);
		}
	}

	public function settings_page() {
		load_template( dirname( __FILE__ ) . '/../templates/settings.php' );

		wp_enqueue_style( 'sitemanager_settings' );
		wp_enqueue_script( 'sitemanager_admin' );
	}

	private function get_admin_color_styles() {
		global $_wp_admin_css_colors;

		// Retrieve WP admin color scheme
		$color = get_user_option( 'admin_color' );

		if ( empty( $color ) || ! isset($_wp_admin_css_colors[ $color ] ) ) {
			$color = 'fresh';
		}

		// Convert to RGBA for styling purposes
		if ( isset( $_wp_admin_css_colors[ $color ]->colors ) ) {

			$wp_admin_colors = $this->get_rgb_from_hex(
				$_wp_admin_css_colors[ $color ]->colors
			);

			return
				'.switch label input[type="checkbox"]:checked + .lever { background-color: rgba(' . $wp_admin_colors[ 3 ] . ', 0.6); }' .
				'.switch label input[type="checkbox"]:checked + .lever:after { background-color: rgb(' . $wp_admin_colors[ 2 ] . '); }' .
				'.switch label .lever:before { background-color: rgba(' . $wp_admin_colors[ 2 ] . ', 0.85); }';
		}

		return '';
	}

	public function get_rgb_from_hex( $hex_colors ) {
		$rgb_colors = array();

		if ( is_array( $hex_colors ) ) {

			foreach ( $hex_colors as $key => $hex ) {
				$hex = str_replace( '#', '', $hex );

				if ( strlen( $hex ) == 6 ) {
					list( $r, $g, $b ) = array( $hex[0] . $hex[1], $hex[2] . $hex[3], $hex[4] . $hex[5] );
				} elseif ( strlen( $hex ) == 3 ) {
					list( $r, $g, $b ) = array( $hex[0] . $hex[0], $hex[1] . $hex[1], $hex[2] . $hex[2] );
				}
				if ( isset( $r, $g, $b ) ) {
					$rgb_colors[ $key ] = hexdec( $r ) . ", " . hexdec( $g ) . ", " . hexdec( $b );
				}
			}
		}
		return $rgb_colors;
	}

	public static function get_setting_page_description() {
		$html =
			'<p>' . __(
				'By default the WordPress Administration does not allow you to activate the automatic updates of its components.',
				'sitemanager'
			) .
			'</p>' .
			'<p>' . __(
				'Because it\'s crucial to keep your WordPress core, translations, plugins and themes up-to-date to prevent' .
				' security and performance lacks, this tool is here to help you configure these updates.',
				'sitemanager'
			) .
			'</p>';

		return apply_filters( 'site_manager_settings_desc', $html );
	}

	public function sanitize_checkboxes( $values ) {
		$new_values = $this->default_option_values();

		if ( isset( $_POST['reset'] ) ) {
			return $new_values;
		}

		foreach ( $new_values as $key => $value ) {
			if ( ! isset( $values[ $key ] ) ) {
				$new_values[ $key ] = 'off';
			}
			else {
				$new_values[ $key ] = 'on';
			}
		}

		if ( 'on' == $new_values['major'] ) {
			$new_values['minor'] = 'on';
		}

		return $new_values;
	}

	public static function switch_on_off( $args ) {
		if ( ! isset( $args['type'] ) ) {
			return;
		}

		if ( ! isset( $args['value'] ) || 'off' != $args['value'] ) {
			$args['value'] = 'on';
		}

		if ( ! isset( $args['name'] ) ) {
			$args['name'] = 'site-manager';
		}

		?>

		<div class="switch">
			<label>
				<input type="checkbox" name="<?php echo $args['name']; ?>[<?php echo $args['type']; ?>]" value="on" class="onoffswitch-checkbox" id="switch-<?php echo $args['type']; ?>" <?php checked( 'on', $args['value'] ); ?>>
				<span class="lever"></span>
			</label>
		</div>

		<?php

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	private static function is_vcs_checkout() {
		$context_dirs = array( ABSPATH );
		$vcs_dirs     = array( '.svn', '.git', '.hg', '.bzr' );
		$check_dirs   = array();

		foreach ( $context_dirs as $context_dir ) {
			// Walk up from $context_dir to the root.
			do {
				$check_dirs[] = $context_dir;

				// Once we've hit '/' or 'C:\', we need to stop. dirname will keep returning the input here.
				if ( $context_dir == dirname( $context_dir ) ) {
					break;
				}

			// Continue one level at a time.
			} while ( $context_dir = dirname( $context_dir ) );
		}

		$check_dirs = array_unique( $check_dirs );

		// Search all directories we've found for evidence of version control.
		foreach ( $vcs_dirs as $vcs_dir ) {
			foreach ( $check_dirs as $check_dir ) {
				if ( $checkout = @is_dir( rtrim( $check_dir, '\\/' ) . "/$vcs_dir" ) ) {
					break 2;
				}
			}
		}

		if ( $checkout && apply_filters( 'automatic_updates_is_vcs_checkout', true, ABSPATH ) ) {
			return $vcs_dir;
		}

		return false;
	}
}
