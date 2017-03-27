<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Admin/Network admin Users list helper
 *
 */
class BP_Member_Type_Generator_Admin_User_List_Helper {
	/**
	 *
	 * @var BP_Member_Type_Generator_Admin_User_List_Helper
	 */
	private static $instance = null;

	private $post_type = '';

	private $message = '';

	private function __construct() {

		$this->post_type = bp_member_type_generator()->get_post_type();

		$this->init();

	}

	/**
	 *
	 * @return BP_Member_Type_Generator_Admin_User_List_Helper
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	private function init() {
		//add bulk change button for WordPress Admin users screen
		add_action( 'restrict_manage_users', array( $this, 'add_change_member_type_selectbox' ) );
		///save the member type association
		add_action( 'load-users.php', array( $this, 'update_member_type' ) );
		//show notices
		add_action( 'admin_notices', array( $this, 'notices' ) );
		add_action( 'network_admin_notices', array( $this, 'notices' ) );
		//for the network admin, we need to inject the bulk action in dom and add it via js
		add_action( 'in_admin_footer', array( $this, 'network_manage_users_footer' ) );


		if ( ! empty( $_GET['bp-member-type-message'] ) ) {
			$this->message = esc_html( urldecode( $_GET['bp-member-type-message'] ) );
		}

		//add_filter( 'manage_users_columns', array( $this, 'add_column' ) );
		//add_filter( 'manage_users-network_columns', array( $this, 'add_column' ) );
		//add_filter( 'wpmu_users_columns', array( $this, 'add_column' ) );

		//add_filter( 'manage_users_custom_column', array( $this, 'show_data' ), 10, 3 );

	}


	public function add_change_member_type_selectbox( $where = '' ) {

		//Since wp will not pass whether it is for top/bottom, let us use static var to deremine position
		static $position;

		if ( ! isset( $position ) ) {
			$position = 'top';//assume it will be called from top
		} else {
			$position = 'bottom';
		}

		//only admin/super admin
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}


		$member_types = bp_get_member_types( array(), 'objects' );

		if ( empty( $member_types ) ) {
			return;
		}

		$output = '<div class="alignright" id="bp-member-type-change-action_' . $position . '">'
		          . '<label for="new_member_type_' . $position . '" class="screen-reader-text">' . __( 'Change member type to…', 'bp-member-type-generator' ) . '</label>
					<select id="new_member_type_' . $position . '" name="new_member_type_' . $position . '">
					<option value="">' . __( 'Change member type to…', 'bp-member-type-generator' ) . '</option>';

		foreach ( $member_types as $key => $type ) {

			$output .= sprintf( '<option value="%s">%s</option>', esc_attr( $key ), $type->labels['singular_name'] );
		}

		$output .= '</select>';
		$output .= get_submit_button( __( 'Change', 'bp-member-type-generator' ), 'secondary', 'change-member-type', false );
		$output .= '</div>';

		echo $output;

	}

	/**
	 * Update User member type association
	 */
	public function update_member_type() {
		//none of our actions are set
		if ( empty( $_REQUEST['new_member_type_top'] ) && empty( $_REQUEST['new_member_type_bottom'] ) ) {
			return;
		}


		//only admin/super admin
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}

		$member_type = '';
		if ( ! empty( $_REQUEST['new_member_type_top'] ) ) {
			$member_type = trim( $_REQUEST['new_member_type_top'] );
		} else {
			$member_type = trim( $_REQUEST['new_member_type_bottom'] );
		}

		$input_name = 'users';

		if ( is_multisite() && is_network_admin() ) {
			$input_name = 'allusers';
		}

		$users = isset( $_REQUEST[ $input_name ] ) ? $_REQUEST[ $input_name ] : array();

		if ( empty( $users ) ) {
			return;//no user selected
		}
		//

		$users = wp_parse_id_list( $users );

		$member_type = sanitize_key( $member_type );

		$member_type_object = bp_get_member_type_object( $member_type );

		if ( empty( $member_type_object ) ) {
			return;//the member type does not seem to be registered
		}

		$updated = 0;

		foreach ( $users as $user_id ) {

			bp_set_member_type( $user_id, $member_type );
		}

		$updated = 1;

		$this->message = sprintf( __( 'Updated member type for %d user(s) to %s. ' ), count( $users ), $member_type_object->labels['singular_name'] );

		if ( is_network_admin() ) {
			$url = network_admin_url( 'users.php' );
		} else {
			$url = admin_url( 'users.php' );
		}

		$redirect = add_query_arg( array(
			'updated'                => $updated,
			'bp-member-type-message' => urlencode( $this->message ),
		), $url );

		wp_safe_redirect( $redirect );

		exit( 0 );

	}

	/**
	 * Render notices
	 *
	 * @return type
	 */
	public function notices() {

		if ( ! $this->message ) {
			return;
		}
		?>

        <div id="message" class="updated notice is-dismissible"><p><?php echo $this->message; ?></p></div>

		<?php

	}

	/**
	 * Work around to add bulk action to WordPress multisite users list screen in network
	 *
	 * @return type
	 */
	public function network_manage_users_footer() {

		//wpmu does not provide an action to add the dd box
		if ( get_current_screen()->id != 'users-network' ) {
			return;
		}

		//now let us add the snippet
		?>
        <div id='bp-member-type-box-wrapper' style='display: none;'>
			<?php $this->add_change_member_type_selectbox( 'bottom' ); ?>
        </div>
        <script type='text/javascript'>
            jQuery(document).ready(function () {
                jQuery('#doaction').after(jQuery('#bp-member-type-change-action'));

            });
        </script>
		<?php
	}
}


BP_Member_Type_Generator_Admin_User_List_Helper::get_instance();
