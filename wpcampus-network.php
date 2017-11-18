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
 * Text Domain:       wpcampus
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
 */
class WPCampus_Network {

	/**
	 * Holds the directory path
	 * to the main plugin directory.
	 *
	 * @access  public
	 * @var     string
	 */
	public $plugin_dir;

	/**
	 * Holds the absolute URL to
	 * the main plugin directory.
	 *
	 * @access  public
	 * @var     string
	 */
	public $plugin_url;

	/**
	 * Whether or not we want
	 * to print the network banner.
	 *
	 * @access  private
	 * @var     string
	 */
	private $enable_network_banner;

	/**
	 * Whether or not we want
	 * to print the network notifications.
	 *
	 * @access  private
	 * @var     string
	 */
	private $enable_network_notifications;

	/**
	 * Holds the class instance.
	 *
	 * @access  private
	 * @var     WPCampus_Network
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @return  WPCampus_Network
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

		// Add custom headers for the REST API.
		add_filter( 'rest_pre_serve_request', array( $this, 'add_rest_headers' ) );

		// Enqueue front-end scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Customize the arguments for the multi author post author dropdown.
		add_filter( 'my_multi_author_post_author_dropdown_args', array( $this, 'filter_multi_author_primary_dropdown_args' ), 10, 2 );

	}

	/**
	 * Method to keep our instance from
	 * being cloned or unserialized.
	 *
	 * @access	private
	 * @return	void
	 */
	private function __clone() {}
	private function __wakeup() {}

	/**
	 * Internationalization FTW.
	 * Load our text domain.
	 *
	 * @access  public
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpcampus', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Change the login logo URL to point
	 * to the site's home page.
	 *
	 * @access  public
	 */
	public function change_login_header_url( $login_header_url ) {
		return get_bloginfo( 'url' );
	}

	/**
	 * Add login stylesheet.
	 *
	 * @access  public
	 */
	public function enqueue_login_styles() {

		// Add our login stylesheet
		wp_enqueue_style( 'wpc-network-login', trailingslashit( plugin_dir_url( __FILE__ ) . 'assets/css' ) . 'login.min.css', array(), null );

	}

	/**
	 * Hide Query Monitor if admin bar isn't showing.
	 *
	 * @access  public
	 */
	public function hide_query_monitor( $show_qm, $is_admin_bar_showing ) {
		return $is_admin_bar_showing;
	}

	/**
	 * Fires when preparing to serve an API request.
	 *
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
	 * We use this hook to add custom CORS headers
	 * and to disable the cache.
	 *
	 * @access  public
	 * @param   $value - bool - Whether the request has already been served. Default false.
	 * @return  bool - the filtered value
	 */
	public function add_rest_headers( $value ) {

		// Only allow from WPCampus domains.
		$origin = get_http_origin();
		if ( $origin ) {

			// Only allow from production or Pantheon domains.
			if ( preg_match( '/([^\.]\.)?wpcampus\.org/i', $origin )
				|| preg_match( '/([^\-\.]+\-)wpcampus\.pantheonsite\.io/i', $origin ) ) {
				header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
			}
		}

		header( 'Access-Control-Allow-Methods: GET' );
		header( 'Access-Control-Allow-Credentials: true' );

		// Disable the cache.
		header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );

		return $value;
	}

	/**
	 * Enqueue our front-end scripts.
	 *
	 * @access  public
	 * @return  void
	 */
	public function enqueue_scripts() {

		// Define the JS directory.
		$css_dir = trailingslashit( $this->plugin_url . 'assets/css' );
		$js_dir = trailingslashit( $this->plugin_url . 'assets/js' );

		// Register mustache - goes in footer.
		wp_register_script( 'mustache', $js_dir . 'mustache.min.js', array(), null, true );

		// Enqueue the notifications script - goes in footer.
		wp_enqueue_script( 'wpcampus-notifications', $js_dir . 'wpcampus-notifications.min.js', array( 'jquery', 'mustache' ), null, true );

		// Enqueue the network banner styles.
		if ( $this->enable_network_banner ) {
			wp_enqueue_style( 'wpc-network-banner', $css_dir . 'wpc-network-banner.min.css', array(), null );
		}

		// Enqueue the network notification styles.
		if ( $this->enable_network_notifications ) {
			wp_enqueue_style( 'wpcampus-network-notification', $css_dir . 'wpcampus-network-notifications.min.css', array(), null );
		}
	}

	/**
	 * Customize the dropdown args for the multi author
	 * post author dropdown so we can get all members.
	 *
	 * @access  public
	 * @param   $args - array - the default arguments.
	 * @param   $post - object - the post object.
	 * @return  array - the filtered arguments.
	 */
	public function filter_multi_author_primary_dropdown_args( $args, $post ) {

		// Remove the "who" so any user can be assigned as a post author.
		if ( isset( $args['who'] ) ) {
			unset( $args['who'] );
		}

		return $args;
	}

	/**
	 * Enable and disable the network banner.
	 *
	 * We need this to know whether or not to enqueue styles.
	 *
	 * @access  public
	 * @return  void
	 */
	public function enable_network_banner() {
		$this->enable_network_banner = true;
	}
	public function disable_network_banner() {
		$this->enable_network_banner = false;
	}

	/**
	 * Enable and disable the network notifications.
	 *
	 * We need this to know whether or not to enqueue styles.
	 *
	 * @access  public
	 * @return  void
	 */
	public function enable_network_notifications() {
		$this->enable_network_notifications = true;
	}
	public function disable_network_notifications() {
		$this->enable_network_notifications = false;
	}

	/**
	 * Get the network banner markup.
	 *
	 * @access  public
	 * @return  string|HTML - the markup.
	 */
	public function get_network_banner() {

		// Make sure it's enabled.
		if ( ! $this->enable_network_banner ) {
			return;
		}

		// Build the banner.
		$banner = '<div id="wpc-network-banner" role="navigation">
			<div class="container">
				<p>' . sprintf( __( '%1$s: Where %2$s Meets Higher Education' ), 'WPCampus', 'WordPress' ) . '</p>
			</div>
		</div>';

		return $banner;
	}

	/**
	 * Print the network banner markup.
	 *
	 * @access  public
	 * @return  void
	 */
	public function print_network_banner() {
		echo $this->get_network_banner();
	}

	/**
	 * Get the network notifications markup.
	 *
	 * @access  public
	 * @return  string|HTML - the markup.
	 */
	public function get_network_notifications() {

		// Make sure it's enabled.
		if ( ! $this->enable_network_notifications ) {
			return;
		}

		// Build the notifications.
		$notifications = '<div id="wpc-notifications"></div>
		<script id="wpc-notification-template" type="x-tmpl-mustache">
			{{#.}}
				<div class="wpc-notification">
					<div class="wpc-notification-message">
						{{{content.rendered}}}
					</div>
				</div>
			{{/.}}
		</script>';

		return $notifications;
	}

	/**
	 * Print the network notifications markup.
	 *
	 * @access  public
	 * @return  void
	 */
	public function print_network_notifications() {
		echo $this->get_network_notifications();
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

/**
 * Interact with the banner.
 */
function wpcampus_enable_network_banner() {
	return wpcampus_network()->enable_network_banner();
}
function wpcampus_disable_network_banner() {
	return wpcampus_network()->disable_network_banner();
}
function wpcampus_get_network_banner() {
	return wpcampus_network()->get_network_banner();
}
function wpcampus_print_network_banner() {
	wpcampus_network()->print_network_banner();
}

/**
 * Interact with the notifications.
 */
function wpcampus_enable_network_notifications() {
	return wpcampus_network()->enable_network_notifications();
}
function wpcampus_disable_network_notifications() {
	return wpcampus_network()->disable_network_notifications();
}
function wpcampus_get_network_notifications() {
	return wpcampus_network()->get_network_notifications();
}
function wpcampus_print_network_notifications() {
	wpcampus_network()->print_network_notifications();
}
