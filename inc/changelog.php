<?php

if ( ! defined('ABSPATH') ) {
	die();
}

class Site_Manager_Changelog {

	/**
	 * Custom post type.
	 *
	 * @var string
	 */
	const post_type = 'update_log';

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );

		add_action( 'current_screen', array( $this, 'enqueue_scripts' ), 20 );

		add_action( 'manage_' . self::post_type . '_posts_custom_column', array( $this, 'action_manage_posts_custom_column' ), 10, 2 );
		add_filter( 'manage_' . self::post_type . '_posts_columns', array( $this, 'filter_manage_post_type_posts_columns' ) );
		add_filter( 'default_hidden_columns', array( $this, 'set_default_hidden_columns' ), 10, 2 );

		add_action( 'bulk_actions-edit-' . self::post_type, array( $this, 'filter_bulk_actions' ) );
		add_action( 'post_row_actions', array( $this, 'filter_row_actions' ), 10, 2 );

		foreach ( array( 'edit.php', 'post.php', 'post-new.php' ) as $item ) {
			add_action( "load-{$item}", array( $this, 'action_load_edit_php' ) );
		}

		add_filter( 'views_edit-update_log', '__return_empty_array' );

		add_filter( 'parse_query', array( $this, 'convert_id_to_term_in_query' ) );
		add_action( 'restrict_manage_posts', array( $this, 'filter_post_type_by_taxonomy' ) );
	}

	function set_default_hidden_columns( $hidden ) {
		$hidden[] = 'update_log_method';
		return $hidden;
	}

	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Updater Changelog', 'sitemanager' ),
			'singular_name'      => __( 'Auto Updater', 'sitemanager' ),
			'menu_name'          => __( 'Auto Updater', 'sitemanager' ),
			'all_items'          => __( 'Changelog', 'sitemanager' ),
			'search_items'       => __( 'Search', 'sitemanager' ),
			'not_found'          => __( 'No changelog available. Please wait for the first update to complete.', 'sitemanager' ),
		);

		$args = array(
			'labels'            => $labels,
			'show_ui'           => true,
			'public'            => false,
			'show_in_admin_bar' => false,
			'capabilities' => array(
				'edit_post'          => 'do_not_allow',
				'edit_posts'         => 'activate_plugins',
				'edit_others_posts'  => 'activate_plugins',
				'publish_posts'      => 'do_not_allow',
				'read_post'          => 'activate_plugins',
				'read_private_posts' => 'do_not_allow',
				'delete_post'        => 'activate_plugins',
				'delete_posts'       => 'do_not_allow',
				'create_posts'       => 'do_not_allow',
			),
			'rewrite'       => false,
			'query_var'     => false,
			'menu_icon'     => 'dashicons-update',
			'menu_position' => 82
		);
		register_post_type( self::post_type, $args );
	}

	public function register_taxonomy() {
		$args = array(
			'public'            => false,
			'hierarchical'      => false,
			'label'             => 'Category',
			'rewrite'           => false
		);
		register_taxonomy( 'update_log_category', array( self::post_type ), $args );

		$args = array(
			'public'            => false,
			'hierarchical'      => false,
			'label'             => 'Method',
			'rewrite'           => false
		);
		register_taxonomy( 'update_log_method', array( self::post_type ), $args );
	}

	public function enqueue_scripts( $screen ) {
		if ( 'edit-' . self::post_type != $screen->id ) {
			return;
		}

		wp_enqueue_style( 'sitemanager_settings' );
		wp_enqueue_script( 'sitemanager_admin' );
	}

	/**
	 * Attached to manage_posts_custom_column action.
	 *
	 */
	public function action_manage_posts_custom_column( $column_name, $post_id ) {
		global $mode;

		switch ( $column_name ) {
			case 'update_log_version' :
				echo get_post_meta( $post_id, '_version', true );
				break;
			case 'update_log_changelog' :
				$post    = get_post( $post_id );
				$content = nl2br( preg_replace( '/<!--more(.*?)?-->(.*)/s', '', $post->post_content ) );

				if ( 'excerpt' == $mode ) {
					echo $content;
				} else {
					echo '<div class="changelog-show-first">' . $content . '</div>';
				}
				break;
			case 'update_log_version_previous' :
				$previous = get_post_meta( $post_id, '_version_previous', true );

				if ( $previous ) {
					echo $previous;
				} else {
					echo '&#8722;';
				}
				break;
			case 'update_log_category' :
				echo $this->list_terms( $post_id, 'update_log_category' );
				break;
			case 'update_log_method' :
				echo $this->list_terms( $post_id, 'update_log_method' );
				break;
			case 'update_log_date':
				echo get_the_time( __( 'Y/m/d', 'sitemanager' ), $post_id);
				break;
		}
	}

	/**
	 * Changes the columns for the table
	 *
	 * Attached to manage_{post_type}_posts_columns filter.
	 */
	public function filter_manage_post_type_posts_columns( $columns ) {
		$columns = array(
			'title'                       => __( 'Title', 'sitemanager' ),
			'update_log_changelog'        => __( 'Description', 'sitemanager' ),
			'update_log_version'          => __( 'Version', 'sitemanager' ),
			'update_log_version_previous' => __( 'Previous version', 'sitemanager' ),
			'update_log_category'         => __( 'Category', 'sitemanager' ),
			'update_log_method'           => __( 'Method', 'sitemanager' ),
			'update_log_date'             => __( 'Updated on', 'sitemanager' ),
		);

		return $columns;
	}

	/**
	 * Modifies bulk actions.
	 */
	public function filter_bulk_actions( $actions ) {
		unset( $actions['edit'] );

		return array();
	}

	/**
	 * Modifies row actions.
	 */
	public function filter_row_actions( $actions, $post ) {
		if ( self::post_type == $post->post_type ) {
			$actions = array();
		}

		return $actions;
	}

	public function action_load_edit_php() {
		$screen = get_current_screen();

		if ( self::post_type == $screen->id && ( $screen->action == 'add' || $_GET['action'] == 'edit' ) ) {
			wp_die( __( 'Invalid post type.', 'sitemanager' ) );
		}

		if ( self::post_type != $screen->post_type ) {
			return;
		}

		if ( ( empty( $_GET['post_status'] ) || 'all' == $_GET['post_status'] ) && ( isset( $_GET['delete_all'] ) || isset( $_GET['delete_all2'] ) ) ) {
			$_GET['post_status'] = $_REQUEST['post_status'] = 'publish';
		}

		global $wp_post_statuses;
		// You didn't see this.
		$wp_post_statuses['publish']->show_in_admin_status_list = false;
	}

	private function list_terms( $post_id, $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy );

		$translations = array(
			'Update'           => __( 'Manual update', 'sitemanager' ),
			'Automatic update' => __( 'Automatic update', 'sitemanager' ),
			'Plugin'           => __( 'Plugin', 'sitemanager' ),
			'Theme'            => __( 'Theme', 'sitemanager' ),
		);

		if ( $terms && ! is_wp_error( $terms ) ) {
			$names = wp_list_pluck( $terms, 'name' );

			foreach ( $names as &$name ) {
				if ( isset( $translations[ $name ] ) ) {
					$name = $translations[ $name ];
				}
			}

			return join( ", ", $names );
		}
	}

	public function filter_post_type_by_taxonomy( $post_type ) {
		if ( self::post_type == $post_type ) {
			$selected = isset( $_GET['update_log_category'] ) ? $_GET['update_log_category'] : '';

			wp_dropdown_categories( array(
				'show_option_all' => __( 'Show all categories', 'sitemanager' ),
				'taxonomy'        => 'update_log_category',
				'name'            => 'update_log_category',
				'orderby'         => 'name',
				'selected'        => $selected,
				'show_count'      => true,
				'hide_empty'      => true,
				'hide_if_empty'   => true,
			) );
		};
	}

	public function convert_id_to_term_in_query( $query ) {
		global $pagenow;

		if (
			$pagenow == 'edit.php' &&
			isset( $query->query_vars['post_type'] ) &&
			self::post_type == $query->query_vars['post_type']  &&
			isset( $query->query_vars['update_log_category'] ) &&
			is_numeric( $query->query_vars['update_log_category'] ) &&
			0 != $query->query_vars['update_log_category']
		) {
			$term = get_term_by( 'id', $query->query_vars['update_log_category'], 'update_log_category' );
			$query->query_vars['update_log_category'] = $term->slug;
		}
	}
}
