<?php
/**
 * Plugin Name:       WPCampus Network
 * Plugin URI:        https://wpcampus.org
 * Description:       Handles network-wide functionality for the WPCampus network of sites.
 * Version:           1.0.0
 * Author:            WPCampus
 * Author URI:        https://wpcampus.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpcampus-network
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Load the files
require_once wpcampus_network()->plugin_dir . 'inc/wpcampus-forms.php';

/**
 * Class WPCampus_Network
 * @since   1.0.0
 */
class WPCampus_Network {

	/**
	 * Holds the directory path
	 * to the main plugin directory.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @var     string
	 */
	public $plugin_dir;

	/**
	 * Holds the absolute URL to
	 * the main plugin directory.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @var     string
	 */
	public $plugin_url;

	/**
	 * Holds the class instance.
	 *
	 * @since   1.0.0
	 * @access	private
	 * @var		WPCampus_Network
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @return	WPCampus_Network
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
	 *
	 * @since   1.0.0
	 */
	protected function __construct() {

		// Store the plugin DIR and URL.
		$this->plugin_dir = plugin_dir_path( __FILE__ );
		$this->plugin_url = plugin_dir_url( __FILE__ );

		// Load our text domain.
		add_action( 'init', array( $this, 'textdomain' ) );

		// Change the login logo URL.
		add_filter( 'login_headerurl', array( $this, 'change_login_header_url' ) );

		// Add login stylesheet.
		add_action( 'login_head', array( $this, 'enqueue_login_styles' ) );

		// Hide Query Monitor if admin bar isn't showing.
		add_filter( 'qm/process', array( $this, 'hide_query_monitor' ), 10, 2 );

		// Removes default REST API functionality.
		add_action( 'rest_api_init', array( $this, 'init_rest_api' ) );

		// Add custom CORS headers for the REST API.
		add_filter( 'rest_pre_serve_request', array( $this, 'add_rest_cors_headers' ) );

		// Enqueue front-end scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

	/**
	 * Method to keep our instance from
	 * being cloned or unserialized.
	 *
	 * @access	private
	 * @since	1.0.0
	 * @return	void
	 */
	private function __clone() {}
	private function __wakeup() {}

	/**
	 * Internationalization FTW.
	 * Load our text domain.
	 *
	 * @since   1.0.0
	 * @access  public
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpcampus-network', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Change the login logo URL to point
	 * to the site's home page.
	 *
	 * @since   1.0.0
	 * @access  public
	 */
	public function change_login_header_url( $login_header_url ) {
		return get_bloginfo( 'url' );
	}

	/**
	 * Add login stylesheet.
	 *
	 * @since   1.0.0
	 * @access  public
	 */
	public function enqueue_login_styles() {

		// Add our login stylesheet
		wp_enqueue_style( 'wpc-network-login', trailingslashit( plugin_dir_url( __FILE__ ) . 'assets/css' ) . 'login.css' );

	}

	/**
	 * Hide Query Monitor if admin bar isn't showing.
	 *
	 * @since   1.0.0
	 * @access  public
	 */
	public function hide_query_monitor( $show_qm, $is_admin_bar_showing ) {
		return $is_admin_bar_showing;
	}

	/**
	 * Fires when preparing to serve an API request.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @param   $wp_rest_server - WP_REST_Server - Server object.
	 */
	public function init_rest_api( $wp_rest_server ) {

		// Remove the default headers so we can add our own.
		remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );

	}

	/**
	 * Filters whether the request has already been served.
	 *
	 * We use this hook to add custom CORS headers.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @param   $value - bool - Whether the request has already been served. Default false.
	 * @return  bool - the filtered value
	 */
	public function add_rest_cors_headers( $value ) {

		// Only allow from WPCampus domains.
		$origin = get_http_origin();
		if ( $origin ) {

			// Only allow from production or Pantheon domains.
			if ( preg_match( '/([^\.]\.)?wpcampus\.org/i', $origin )
				|| preg_match( '/[^\-\.]+\-wpcampus\.pantheonsite\.io/i', $origin ) ) {
				header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
			}
		}

		header( 'Access-Control-Allow-Methods: GET' );
		header( 'Access-Control-Allow-Credentials: true' );

		return $value;
	}

	/**
	 * Enqueue our front-end scripts.
	 *
	 * @since   1.0.0
	 * @access  public
	 * @return  void
	 */
	public function enqueue_scripts() {

		// Define the JS directory.
		$js_dir = trailingslashit( $this->plugin_url . 'assets/js' );

		// Register mustache - goes in footer.
		wp_register_script( 'mustache', $js_dir . 'mustache.min.js', array(), null, true );

		// Enqueue the notifications script - goes in footer.
		wp_enqueue_script( 'wpcampus-notifications', $js_dir . 'wpcampus-notifications.min.js', array( 'jquery', 'mustache' ), null, true );

	}

}

/**
 * Returns the instance of our main WPCampus_Network class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin.
 *
 * @access	public
 * @return	WPCampus_Network
 */
function wpcampus_network() {
	return WPCampus_Network::instance();
}

// Let's get this show on the road.
wpcampus_network();
