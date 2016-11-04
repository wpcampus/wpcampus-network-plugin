<?php

/**
 * Plugin Name:       WPCampus Network
 * Plugin URI:        https://wpcampus.org
 * Description:       Holds network-wide functionality for the WPCampus network of sites.
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

/**
 * Class WPCampus_Network
 * @since   1.0.0
 */
class WPCampus_Network {

	/**
	 * Holds the class instance.
	 *
	 * @access	private
	 * @since   1.0.0
	 * @var		WPCampus_Network
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return	WPCampus_Network
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	/**
	 * Warming up the engine.
	 *
	 * @since   1.0.0
	 */
	protected function __construct() {

		// Load our text domain.
		add_action( 'init', array( $this, 'textdomain' ) );

		// Runs on install.
		register_activation_hook( __FILE__, array( $this, 'install' ) );

		// Runs when the plugin is upgraded.
		add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_complete' ), 1, 2 );

		// Change the login logo URL.
		add_filter( 'login_headerurl', array( $this, 'change_login_header_url' ) );

		// Add login stylesheet.
		add_action( 'login_head', array( $this, 'enqueue_login_styles' ) );

		// Hide Query Monitor if admin bar isn't showing.
		add_filter( 'qm/process', array( $this, 'hide_query_monitor' ), 10, 2 );

	}

	/**
	 * Method to keep our instance from being cloned.
	 *
	 * @access	private
	 * @since   1.0.0
	 * @return	void
	 */
	private function __clone() {}

	/**
	 * Method to keep our instance from being unserialized.
	 *
	 * @access	private
	 * @since   1.0.0
	 * @return	void
	 */
	private function __wakeup() {}

	/**
	 * Runs when the plugin is installed.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function install() {}

	/**
	 * Runs when the plugin is upgraded.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function upgrader_process_complete( $upgrader, $upgrade_info ) {}

	/**
	 * Internationalization FTW.
	 * Load our text domain.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpcampus-network', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Change the login logo URL to point
	 * to the site's home page.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function change_login_header_url( $login_header_url ) {
		return get_bloginfo( 'url' );
	}

	/**
	 * Add login stylesheet.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function enqueue_login_styles() {

		// Add our login stylesheet
		wp_enqueue_style( 'wpc-network-login', trailingslashit( plugin_dir_url( __FILE__ ) . 'assets/css' ) . 'login.css' );

	}

	/**
	 * Hide Query Monitor if admin bar isn't showing.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function hide_query_monitor( $show_qm, $is_admin_bar_showing ) {
		return $is_admin_bar_showing;
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

// Let's get this show on the road
wpcampus_network();