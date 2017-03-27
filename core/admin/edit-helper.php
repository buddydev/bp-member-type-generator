<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Helper class for Edit Member Type screen
 *
 */
class BP_Member_Type_Generator_Admin_Edit_Screen_Helper {

	private static $instance = null;

	private $post_type = '';

	private function __construct() {

		$this->post_type = bp_member_type_generator()->get_post_type();

		$this->init();
	}

	/**
	 *
	 * @return BP_Member_Type_Generator_Admin_Edit_Screen_Helper
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function init() {
		//save post
		add_action( 'save_post', array( $this, 'save_post' ) );

		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_filter( 'post_updated_messages', array( $this, 'filter_update_messages' ) );
	}

	/**
	 * Register meta boxes
	 */
	public function register_metabox() {

		add_meta_box( 'bp-member-type-box', __( 'Member type Info', 'bp-member-type-generator' ), array(
			$this,
			'member_type_info_metabox',
		), $this->post_type );
		add_meta_box( 'bp-member-type-box-status', __( 'Member Type Status', 'bp-member-type-generator' ), array(
			$this,
			'status_metabox',
		), $this->post_type, 'side', 'high' );
	}

	/**
	 * Collect member type details
	 *
	 * @param WP_Post $post
	 */
	public function member_type_info_metabox( $post ) {

		$meta = get_post_custom( $post->ID );

		$name = isset( $meta['_bp_member_type_name'] ) ? $meta['_bp_member_type_name'][0] : '';

		$label_name          = isset( $meta['_bp_member_type_label_name'] ) ? $meta['_bp_member_type_label_name'][0] : '';
		$label_singular_name = isset( $meta['_bp_member_type_label_singular_name'] ) ? $meta['_bp_member_type_label_singular_name'][0] : '';

		$enable_directory = isset( $meta['_bp_member_type_enable_directory'] ) ? $meta['_bp_member_type_enable_directory'][0] : 1;//enabled by default

		$directory_slug = isset( $meta['_bp_member_type_directory_slug'] ) ? $meta['_bp_member_type_directory_slug'][0] : '';

		?>
        <div id="bp-memebr-type-generator-form">
            <label>
                <span><?php _e( '<span>Member Type Name:</span>(unique slug, used to identify the member type, It is also called name, e.g student, staff, teacher etc):', 'bp-member-type-generator' ); ?></span>
                <input type="text" name="bp-member-type[name]" placeholder="Unique key to identify this member type"
                       value="<?php echo esc_attr( $name ); ?>"/>
            </label>

            <p class='bp-member-type-generator-help'> <?php _e( 'Plugins will use this <strong>member type name </strong> to identify the member type. Please avoid changing it. If you change the unique name, you will loose the information about members having this member type.', 'bp-member-type-generator' ); ?></p>

            <label>
                <span> <span><?php _e( 'Plural Label:', 'bp-member-type-generator' ); ?></span></span>
                <input type="text" name="bp-member-type[label_name]"
                       placeholder="<?php _e( 'Plural name e.g. Students', 'bp-member-type-generator' ); ?>"
                       value="<?php echo esc_attr( $label_name ); ?>"/>
            </label>

            <label>
                <span> <span><?php _e( 'Singular Label:', 'bp-member-type-generator' ); ?></span></span>
                <input type="text" name="bp-member-type[label_singular_name]"
                       placeholder="<?php _e( 'Singular name, e.g. Student', 'bp-member-type-generator' ); ?>"
                       value="<?php echo esc_attr( $label_singular_name ); ?>"/>
            </label>

            <p>
                <label>
                    <input type='checkbox' name='bp-member-type[enable_directory]'
                           value='1' <?php checked( $enable_directory, 1 ); ?> />
                    <strong><?php _e( 'Enable Directory?', 'bp-member-type-generator' ); ?></strong>
                </label>
            </p>
            <p class='bp-member-type-generator-help'><?php _e( 'By enablisng directory, you can see a list of all members having this member type by appending member type name or directory slug(if specified).Only applies to BuddyPress 2.3+)', 'bp-member-type-generator' ); ?></p>

            <p>
				<span> 
					<strong><?php _e( 'Directory Slug:', 'bp-member-type-generator' ); ?></strong>
				</span>
                <input type='text' name='bp-member-type[directory_slug]' value='<?php echo $directory_slug; ?>'/>
            </p>
            <p class='bp-member-type-generator-help'><?php _e( 'If you have enabled directory, It will be used to append to your memeber directory url to list all members having this member type( Only applies to BuddyPress 2.3+)', 'bp-member-type-generator' ); ?></p>

        </div>
		<?php wp_nonce_field( 'bp-member-type-generator-edit-member-type', '_bp-member-type-generator-nonce' ); ?>
		<?php //adding css below as we only need little code and loading a separate css file does not seem a good fit here ?>
        <style type="text/css">
            #bp-memebr-type-generator-form {

            }

            #bp-memebr-type-generator-form label {
                display: block;
                margin-bottom: 15px;
            }

            #bp-memebr-type-generator-form span {
                display: block;
            }

            #bp-memebr-type-generator-form span span, #bp-memebr-type-generator-form strong {
                font-weight: bold;

            }

            #bp-memebr-type-generator-form input[type='text'] {
                display: block;
                min-width: 420px;
                padding: 10px;
                font-weight: bold;
            }

            p.bp-member-type-generator-help {
                /*display: block;
				background: #D64937;
				color: #eee;
				padding: 5px;*/
                margin-top: -15px;
                color: #339C8A;
            }

        </style>
		<?php
	}

	/**
	 * Generate Member Type status Meta box
	 *
	 * @param WP_Post $post
	 */
	public function status_metabox( $post ) {

		$meta      = get_post_custom( $post->ID );
		$is_active = isset( $meta['_bp_member_type_is_active'] ) ? $meta['_bp_member_type_is_active'][0] : 1;
		?>
        <p><label><input type='checkbox' name='bp-member-type[is_active]'
                         value='1' <?php checked( $is_active, 1 ); ?> ><?php _e( 'Is active?', 'bp-member-type-generator' ); ?>
            </label></p>
        <p class='bp-member-type-generator-help'> <?php _e( 'Only active member types will be registered. You can set a member type to inactive to disable it.', 'bp-member-type-generator' ); ?></p>
		<?php

	}

	/**
	 * Save all data as post meta
	 *
	 * @param int $post_id
	 *
	 * @return null
	 */
	public function save_post( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$post = get_post( $post_id );

		if ( $post->post_type != $this->post_type ) {
			return;
		}

		if ( ! isset( $_POST['_bp-member-type-generator-nonce'] ) ) {
			return;//most probably the new member type screen
		}

		//verify nonce
		if ( ! wp_verify_nonce( $_POST['_bp-member-type-generator-nonce'], 'bp-member-type-generator-edit-member-type' ) ) {
			return;
		}

		//save data

		$data = isset( $_POST['bp-member-type'] ) ? $_POST['bp-member-type'] : array();

		if ( empty( $data ) ) {
			return;
		}

		$post_title = wp_kses( $_POST['post_title'], wp_kses_allowed_html( 'strip' ) );
		//for unique id
		$name = isset( $data['name'] ) ? sanitize_key( $data['name'] ) : sanitize_key( $post_title );
		//for label
		$label_name    = isset( $data['label_name'] ) ? wp_kses( $data['label_name'], wp_kses_allowed_html( 'strip' ) ) : $post_title;
		$singular_name = isset( $data['label_singular_name'] ) ? wp_kses( $data['label_singular_name'], wp_kses_allowed_html( 'strip' ) ) : $post_title;

		$is_active = isset( $data['is_active'] ) ? absint( $data['is_active'] ) : 0;//default inactive

		$enable_directory = isset( $data['enable_directory'] ) ? absint( $data['enable_directory'] ) : 0;//default inactive
		$directory_slug   = isset( $data['directory_slug'] ) ? sanitize_key( $data['directory_slug'] ) : '';//default inactive

		update_post_meta( $post_id, '_bp_member_type_is_active', $is_active );

		update_post_meta( $post_id, '_bp_member_type_name', $name );
		update_post_meta( $post_id, '_bp_member_type_label_name', $label_name );
		update_post_meta( $post_id, '_bp_member_type_label_singular_name', $singular_name );

		update_post_meta( $post_id, '_bp_member_type_enable_directory', $enable_directory );

		//for directory slug

		if ( $directory_slug ) {
			update_post_meta( $post_id, '_bp_member_type_directory_slug', $directory_slug );
		} else {
			delete_post_meta( $post_id, '_bp_member_type_directory_slug' );
		}
	}

	public function filter_update_messages( $messages ) {

		global $post, $post_ID;

		$update_message = $messages['post'];//make a copy of the post update message

		$update_message[1] = sprintf( __( 'Member type updated.', 'bp-member-type-generator' ) );

		$update_message[4] = __( 'Member type updated.', 'bp-member-type-generator' );

		$update_message[6] = sprintf( __( 'Member type published. ', 'bp-member-type-generator' ) );

		$update_message[7] = __( 'Member type  saved.', 'bp-member-type-generator' );

		$messages[ $this->post_type ] = $update_message;

		return $messages;
	}

}

BP_Member_Type_Generator_Admin_Edit_Screen_Helper::get_instance();
