<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Helper class to register the internal member type post type and the actual Member type
 *
 */
class BP_Member_Type_Generator_Actions {

	private static $instance = null;

	private function __construct() {
		//register internal post type used to handle the member type
		add_action( 'bp_init', array( $this, 'register_post_type' ) );
		//register member type
		add_action( 'bp_register_member_types', array( $this, 'register_member_type' ) );
	}

	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register internal post type
	 */
	public function register_post_type() {

		//only register on the main bp site
		if ( is_multisite() && ! bp_is_root_blog() ) {
			return;
		}

		$is_admin = is_super_admin();

		register_post_type( bp_member_type_generator()->get_post_type(), array(
			'label'  => __( 'BuddyPress Member Types', 'bp-member-type-generator' ),
			'labels' => array(
				'name'               => __( 'BP Member Types', 'bp-member-type-generator' ),
				'singular_name'      => __( 'BP Member Type', 'bp-member-type-generator' ),
				'add_new_item'       => __( 'New Member Type', 'bp-member-type-generator' ),
				'new_item'           => __( 'New Member Type', 'bp-member-type-generator' ),
				'edit_item'          => __( 'Edit Member Type', 'bp-member-type-generator' ),
				'search_items'       => __( 'Search Member Types', 'bp-member-type-generator' ),
				'not_found_in_trash' => __( 'No Member Types found in trash', 'bp-member-type-generator' ),
				'not_found'          => __( 'No Member Type found', 'bp-member-type-generator' ),
			),

			'public'       => false,//this is a private post type, not accesible from front end
			'show_ui'      => $is_admin,
			'show_in_menu' => 'users.php',
			//	'menu_position'			=> 60,
			'menu_icon'    => 'dashicons-groups',
			'supports'     => array( 'title' ),
			//'register_meta_box_cb'	=> array( $this, 'register_metabox'),
		) );
	}

	/**
	 * Register all active member types
	 *
	 */
	public function register_member_type() {

		//$this->register_post_type();
		$is_root_blog = bp_is_root_blog();
		//if we are not on the main bp site, switch to it before registering member type

		if ( ! $is_root_blog ) {
			switch_to_blog( bp_get_root_blog_id() );
		}
		// get all posts in member type post type.
		$post_ids = $this->get_active_member_types();// get_posts( array( 'post_type'=> bp_member_type_generator()->get_post_type(), 'posts_per_page'=> -1, 'post_status'=> 'publish' ) );
		// update meta cache to avoid multiple db calls.
		update_meta_cache( 'post', $post_ids );
		// build to register the member type.
		$member_types = array();

		foreach ( $post_ids as $post_id ) {

			$is_active = get_post_meta( $post_id, '_bp_member_type_is_active', true );
			$name      = get_post_meta( $post_id, '_bp_member_type_name', true );

			if ( ! $is_active || ! $name ) {
				continue;//if not active or no unique key, do not register
			}

			$enable_directory = get_post_meta( $post_id, '_bp_member_type_enable_directory', true );
			$directory_slug   = get_post_meta( $post_id, '_bp_member_type_directory_slug', true );

			$has_dir = false;

			if ( $enable_directory ) {

				if ( $directory_slug ) {
					$has_dir = $directory_slug;
				} else {
					$has_dir = true;
				}
			}

			$member_types[ $name ] = array(
				'labels'        => array(
					'name'          => get_post_meta( $post_id, '_bp_member_type_label_name', true ),
					'singular_name' => get_post_meta( $post_id, '_bp_member_type_label_singular_name', true ),
				),
				'has_directory' => $has_dir, //only applies to bp 2.3+
			);

		}

		foreach ( $member_types as $member_type => $args ) {
			bp_register_member_type( $member_type, $args );
		}

		if ( ! $is_root_blog ) {
			restore_current_blog();
		}
	}

	private function get_active_member_types() {

		global $wpdb;

		$query = "SELECT DISTINCT ID FROM {$wpdb->posts} WHERE post_type = %s AND ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value = %d ) ";

		return $wpdb->get_col( $wpdb->prepare( $query, bp_member_type_generator()->get_post_type(), '_bp_member_type_is_active', 1 ) );
	}
}

BP_Member_Type_Generator_Actions::get_instance();