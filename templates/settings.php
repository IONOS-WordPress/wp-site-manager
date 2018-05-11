<?php global $wp_settings_fields; ?>

<div class="wrap">
	<h2><?php _e( 'Manage your update preferences', 'sitemanager' ); ?></h2>
	
	<div class="settings-description">
		<?php echo Site_Manager_Admin::get_setting_page_description(); ?>
	</div>

	<form action="options.php" method="post">
		<?php settings_fields( 'site-manager-group' ); ?>
		<?php do_settings_sections( 'site-manager' ); ?>
	
		<?php
			$plugin_slug = 'background-update-tester';
			$plugin_file = 'background-update-tester/background-update-tester.php';
		?>
	
		<?php if ( ! isset( $wp_settings_fields['site-manager']['site-manager-update'] ) ): ?>
			<p>
				<strong><?php Site_Manager_Admin::render_noupdate_text(); ?></strong>
			</p>
		<?php endif; ?>
	
		<p class="submit">
			<?php submit_button( __( 'Save Changes', 'sitemanager' ), 'primary', 'submit', false ); ?>
			<?php submit_button( __( 'Use Default Settings', 'sitemanager' ), 'secondary', 'reset', false ); ?>
		</p>
	</form>
</div>