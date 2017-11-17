<?php

/**
 * Holds all of our global form functionality.
 */
class WPCampus_Forms {

	/**
	 * Holds the class instance.
	 *
	 * @access	private
	 * @var		WPCampus_Forms
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @return	WPCampus_Forms
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name = __CLASS__;
			self::$instance = new $class_name;
		}
		return self::$instance;
	}

	/**
	 * Warming up the engine.
	 */
	protected function __construct() {

		// Always use the form anchor in the <form> action.
		add_filter( 'gform_confirmation_anchor', array( $this, 'filter_use_confirmation_anchor' ), 100, 2 );

		// Filter field values.
		add_filter( 'gform_field_value', array( $this, 'filter_field_value' ), 10, 3 );

	}

	/**
	 * Method to keep our instance
	 * from being cloned or unserialized.
	 *
	 * @access	private
	 * @return	void
	 */
	private function __clone() {}
	private function __wakeup() {}

	/**
	 *
	 */
	public function filter_use_confirmation_anchor( $default_anchor, $form ) {
		return true;
	}

	/**
	 * Get post created from entry.
	 */
	public function get_entry_post( $entry_id, $post_type = '' ) {
		global $wpdb;

		// Build query.
		$query = $wpdb->prepare( "SELECT posts.*, meta.meta_value AS gf_entry_id FROM {$wpdb->posts} posts INNER JOIN {$wpdb->postmeta} meta ON meta.post_id = posts.ID AND meta.meta_key = 'gf_entry_id' AND meta.meta_value = %s", $entry_id );

		// Add post type.
		if ( ! empty( $post_type ) ) {
			$query .= $wpdb->prepare( 'WHERE posts.post_type = %s', $post_type );
		}

		return $wpdb->get_row( $query );
	}

	/**
	 * Filter field values.
	 */
	public function filter_field_value( $value, $field, $name ) {

		switch ( $name ) {

			// Get user information.
			case 'user_firstname':
			case 'user_lastname':
			case 'user_email':
			case 'user_url':

				// Get the current user.
				$current_user = wp_get_current_user();
				if ( ! empty( $current_user->{$name} ) ) {
					return $current_user->{$name};
				}

				break;

			// Populate the current user ID.
			case 'userid':
			case 'user_id':
				return get_current_user_id();

			// Get user Twitter.
			case 'user_twitter':

				// Get the Twitter info.
				$twitter = get_user_meta( get_current_user_id(), 'twitter', true );

				// Make sure there's no "@".
				return preg_replace( '/[\@]/i', '', $twitter );

		}

		return $value;
	}
}

/**
 * Returns the instance of our main WPCampus_Forms class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin.
 *
 * @access	public
 * @return	WPCampus_Forms
 */
function wpcampus_forms() {
	return WPCampus_Forms::instance();
}

// Let's get this show on the road
wpcampus_forms();
