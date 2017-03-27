<?php
/**
 * Plugin Name: BuddyPress Member Type Generator
 * Version: 1.0.4
 * Plugin URI: https://buddydev.com/plugins/bp-member-type-generator/
 * Author: BuddyDev
 * Author URI: https://BuddyDev.com
 * Description: Allows site admins to create/manage Member types from WordPress dashboard. Also, Includes functionality to bulk assign member type to users.
 * License: GPL2 or above
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

class BP_Member_Type_Generator {

	private static $instance = null;

	private $path;
	private $url;

	private function __construct() {

		$this->path = plugin_dir_path( __FILE__ );
		$this->url  = plugin_dir_url( __FILE__ );

		add_action( 'bp_loaded', array( $this, 'load' ), 0 );
	}

	/**
	 * Get singleton instance
	 *
	 * @return BP_Member_Type_Generator
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load required files
	 *
	 */
	public function load() {

		$files = array(
			'core/functions.php',
			'core/actions.php',
		);

		if ( is_admin() ) {

			$files[] = 'core/admin/edit-helper.php'; //edit screen helper
			$files[] = 'core/admin/list-helper.php';//member type list helper

			if ( version_compare( buddypress()->version, '2.7.0', '<' ) ) {
				$files[] = 'core/admin/user-helper.php'; //user list helper for bulk manage
			}
		}

		foreach ( $files as $file ) {
			require_once $this->path . $file;
		}
	}

	/**
	 * Get the post type we are using internally to store member type details
	 *
	 * @return string
	 */
	public function get_post_type() {
		return 'bp-member-type';
	}

	/**
	 * Save plural label name to post meta
	 *
	 * @param int $post_id
	 * @param string $name
	 */
	public function update_label( $post_id, $name ) {
		update_post_meta( $post_id, '_bp_member_type_label_name', $name );
	}

	/**
	 * Save singular label for member type to post meta
	 *
	 * @param int $post_id
	 * @param string $name
	 */
	public function update_singular_label( $post_id, $name ) {
		update_post_meta( $post_id, '_bp_member_type_label_singular_name', $name );
	}

	/**
	 * Save the directory preference for the member type
	 *
	 * @param int $post_id
	 * @param int $has_directory
	 */
	public function update_has_directory( $post_id, $has_directory ) {
		update_post_meta( $post_id, '_bp_member_type_has_directory', $has_directory );
	}

	/**
	 *    Save member type name in the post meta
	 *
	 * @param int $post_id
	 * @param string $key
	 */
	public function update_member_type( $post_id, $key ) {
		update_post_meta( $post_id, '_bp_member_type_name', $key );
	}

	/**
	 * Check if the member type already exists
	 *
	 * @global wpdb $wpdb
	 *
	 * @param int $post_id
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function key_exists( $post_id, $key ) {

		global $wpdb;

		$check_query = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s AND post_id != %d";

		$ids = $wpdb->get_col( $wpdb->prepare( $check_query, '_bp_member_type_name', $key, $post_id ) );

		if ( ! empty( $ids ) ) {
			return true;
		}

		return false;
	}
}

//instantiate

BP_Member_Type_Generator::get_instance();

/**
 * Helper method to access  BP_Member_Type_Generator instance
 *
 * @return BP_Member_Type_Generator
 */
function bp_member_type_generator() {
	return BP_Member_Type_Generator::get_instance();
}
