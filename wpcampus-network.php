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
	 * to print the network banner,
	 * notifications, or footer.
	 *
	 * @access  private
	 * @var     string
	 */
	private $enable_network_banner;
	private $enable_network_subscribe;
	private $enable_network_notifications;
	private $enable_network_footer;

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

		// Register the network footer menu.
		add_action( 'after_setup_theme', array( $this, 'register_network_footer_menu' ), 20 );

		// Enqueue front-end scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );

		// Customize the arguments for the multi author post author dropdown.
		add_filter( 'my_multi_author_post_author_dropdown_args', array( $this, 'filter_multi_author_primary_dropdown_args' ), 10, 2 );

		// Adding titles to iframes for accessibility.
		add_filter( 'oembed_dataparse', array( $this, 'filter_oembed_dataparse' ), 10, 3 );

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
		wp_enqueue_style( 'wpc-network-login', trailingslashit( $this->plugin_url . 'assets/css' ) . 'wpc-network-login.min.css', array(), null );

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
	public function enqueue_scripts_styles() {

		// Define the directories.
		$css_dir = trailingslashit( $this->plugin_url . 'assets/css' );
		$js_dir = trailingslashit( $this->plugin_url . 'assets/js' );

		// Register assets needed below (and possibly in other libraries).
		wp_register_script( 'launchy', $js_dir . 'launchy.js', array(), null, true );
		wp_register_script( 'mustache', $js_dir . 'mustache.min.js', array(), null, true );

		// Keep this one outside logic so I can register as a dependency in scripts outside the plugin.
		wp_register_script( 'wpc-network-toggle-menu', $js_dir . 'wpc-network-toggle-menu.min.js', array( 'jquery', 'jquery-ui-core' ), null );

		// Enqueue the network banner assets.
		if ( $this->enable_network_banner ) {
			wp_enqueue_style( 'wpc-network-banner', $css_dir . 'wpc-network-banner.min.css', array(), null );
			wp_enqueue_script( 'wpc-network-toggle-menu' );
		}

		// Enqueue the network subscribe assets.
		if ( $this->enable_network_subscribe ) {
			wp_enqueue_style( 'wpc-network-subscribe', $css_dir . 'wpc-network-subscribe.min.css', array(), null );
			wp_enqueue_script( 'launchy' );
		}

		// Enqueue the network notification assets.
		if ( $this->enable_network_notifications ) {
			wp_enqueue_style( 'wpc-network-notifications', $css_dir . 'wpc-network-notifications.min.css', array(), null );
			wp_enqueue_script( 'wpc-network-notifications', $js_dir . 'wpc-network-notifications.min.js', array( 'jquery', 'mustache' ), null, true );
		}

		// Enqueue the network footer assets.
		if ( $this->enable_network_footer ) {
			wp_enqueue_style( 'wpc-network-footer', $css_dir . 'wpc-network-footer.min.css', array(), null );
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
	 * Filters the returned oEmbed HTML.
	 *
	 * @param   string - $return - The returned oEmbed HTML.
	 * @param   object - $data - A data object result from an oEmbed provider.
	 * @param   string - $url - The URL of the content to be embedded.
	 * @return  string - the HTML.
	 */
	public function filter_oembed_dataparse( $return, $data, $url ) {

		// Get title from embed data to start.
		$title = ! empty( $data->title ) ? $data->title : '';

		// If no embed title, search the return markup for a title attribute.
		$preg_match = '/title\=[\"|\\\']{1}([^\"\\\']*)[\"|\\\']{1}/i';
		$has_title_attr = preg_match( $preg_match, $return, $matches );
		if ( $has_title_attr && ! empty( $matches[1] ) ) {
			$title = $matches[1];
		}

		// Add embed type as title prefix.
		if ( $title && ! empty( $data->type ) ) {
			switch ( $data->type ) {

				// Capitalize first word.
				case 'video':
					$title = sprintf( __( '%s:', 'wpcampus' ), ucfirst( $data->type ) ) . ' ' . $title;
					break;
			}
		}

		$title = apply_filters( 'wpcampus_oembed_title', $title, $return, $data, $url );

		/*
		 * If the title attribute already
		 * exists, replace with new value.
		 *
		 * Otherwise, add the title attribute.
		 */
		if ( $has_title_attr ) {
			$return = preg_replace( $preg_match, 'title="' . $title . '"', $return );
		} else {
			$return = preg_replace( '/^\<iframe/i', '<iframe title="' . $title . '"', $return );
		}

		return $return;
	}

	/**
	 * Gets markup for list of social media icons.
	 *
	 * @access  public
	 * @return  string|HTML - the markup.
	 */
	public function get_social_media_icons() {

		$images_dir = $this->plugin_dir . 'assets/images/';
		$social = array(
			'slack' => array(
				'title' => sprintf( __( 'Join %1$s on %2$s', 'wpcampus' ), 'WPCampus', 'Slack' ),
				'href'  => 'https://wpcampus.org/get-involved/',
			),
			'twitter' => array(
				'title' => sprintf( __( 'Follow %1$s on %2$s', 'wpcampus' ), 'WPCampus', 'Twitter' ),
				'href'  => 'https://twitter.com/wpcampusorg',
			),
			'facebook' => array(
				'title' => sprintf( __( 'Follow %1$s on %2$s', 'wpcampus' ), 'WPCampus', 'Facebook' ),
				'href'  => 'https://www.facebook.com/wpcampus',
			),
			'youtube' => array(
				'title' => sprintf( __( 'Follow %1$s on %2$s', 'wpcampus' ), 'WPCampus', 'YouTube' ),
				'href'  => 'https://www.youtube.com/wpcampusorg',
			),
			'github' => array(
				'title' => sprintf( __( 'Follow %1$s on %2$s', 'wpcampus' ), 'WPCampus', 'GitHub' ),
				'href'  => 'https://github.com/wpcampus/',
			),
		);

		$icons = '<ul class="social-media-icons" role="navigation">';

		foreach ( $social as $key => $info ) {
			$filename = "{$images_dir}{$key}.php";
			if ( file_exists( $filename ) ) {
				$icons .= sprintf( '<li class="%1$s"><a href="%2$s" title="%3$s">%4$s</a></li>',
					$key,
					$info['href'],
					$info['title'],
					file_get_contents( $filename )
				);
			}
		}

		$icons .= '</ul>';

		return $icons;
	}

	/**
	 * Prints markup for list of social media icons.
	 *
	 * @access  public
	 * @return  void
	 */
	public function print_social_media_icons() {
		echo $this->get_social_media_icons();
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
	 * Enable and disable the network subscribe popup.
	 *
	 * We need this to know whether or not to enqueue styles.
	 *
	 * @access  public
	 * @return  void
	 */
	public function enable_network_subscribe() {
		$this->enable_network_subscribe = true;
	}
	public function disable_network_subscribe() {
		$this->disable_network_subscribe = false;
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
	 * Enable and disable the network footer.
	 *
	 * We need this to know whether or not to enqueue styles.
	 *
	 * @access  public
	 * @return  void
	 */
	public function enable_network_footer() {
		$this->enable_network_footer = true;
	}
	public function disable_network_footer() {
		$this->enable_network_footer = false;
	}

	/**
	 * Get the network banner markup.
	 *
	 * @access  public
	 * @return  string|HTML - the markup.
	 */
	public function get_network_banner( $args = array() ) {

		// Make sure it's enabled.
		if ( ! $this->enable_network_banner ) {
			return;
		}

		// Parse incoming $args with defaults.
		$args = wp_parse_args( $args, array(
			'skip_nav_id'       => '',
			'skip_nav_label'    => __( 'Skip to Content', 'wpcampus' ),
		));

		// Build the banner.
		$banner = '';

		// Add skip navigation.
		if ( ! empty( $args['skip_nav_id'] ) ) {

			// Make sure we have a valid ID.
			$skip_nav_id = preg_replace( '/[^a-z0-9\-]/i', '', $args['skip_nav_id'] );
			if ( ! empty( $skip_nav_id ) ) {
				$banner .= sprintf( '<a href="#%s" id="wpc-skip-to-content">%s</a>',
					$skip_nav_id,
					$args['skip_nav_label']
				);
			}
		}

		// Add the banner.
		$banner .= '<div id="wpc-network-banner" role="navigation">
			<div class="wpc-container">
				<div class="wpc-logo">
					<a href="https://wpcampus.org">
						<?xml version="1.0" encoding="utf-8"?>
						<svg version="1.1" id="WPCampusOrgLogo" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 1275 100" style="enable-background:new 0 0 1275 100;" xml:space="preserve">
							<title>' . sprintf( __( '%1$s: Where %2$s Meets Higher Education', 'wpcampus' ), 'WPCampus', 'WordPress' ) . '</title>
							<style type="text/css">.st0{opacity:0.7;enable-background:new;}</style>
							<path class="st0" d="M113.5,1.5l-23.4,97H77.9L56.8,23.2L37.1,98.5H24.6L0,1.5h12.6L32,80.1L52.6,1.5h9.9l22.2,78.6l18.1-78.6H113.5 z"/>
							<path class="st0" d="M152.6,98.5h-12.2v-97h34.4c10.8,0,18.7,2.9,23.8,8.8s7.7,12.5,7.7,20c0,8.3-2.8,15.2-8.4,20.7 c-5.6,5.4-12.9,8.2-21.9,8.2h-23.5L152.6,98.5L152.6,98.5z M152.6,49h22.3c5.7,0,10.4-1.7,13.9-5.2c3.5-3.4,5.3-8,5.3-13.6 c0-4.8-1.6-9.1-4.7-12.9c-3.1-3.8-7.7-5.7-13.6-5.7h-23.2V49z"/>
							<path d="M288.1,61.5l27.2,1.6c-1.3,11.9-5.7,21-13.2,27.4c-7.5,6.3-16.7,9.5-27.7,9.5c-13.2,0-23.8-4.4-31.9-13.1 c-8.1-8.7-12.2-20.8-12.2-36.1c0-15.2,3.8-27.5,11.5-36.8s18.4-14,32.1-14c12.8,0,22.7,3.6,29.6,10.7s10.8,16.5,11.7,28.3l-27.8,1.5 c0-6.5-1.2-11.2-3.7-14.1s-5.4-4.3-8.8-4.3c-9.4,0-14.1,9.4-14.1,28.3c0,10.6,1.2,17.7,3.7,21.5c2.4,3.8,5.9,5.7,10.3,5.7 C282.7,77.5,287.1,72.2,288.1,61.5z"/>
							<path d="M397.3,98.5l-5.5-19.1h-26L360,98.5h-24.2l29.9-97h31.5l30.4,97H397.3z M370.9,58.2h15.7l-7.8-28.1L370.9,58.2z"/>
							<path d="M558.6,1.5v97H531V29.1l-18,69.4h-18.9l-18.7-69.4v69.4h-22.3v-97H492L506.1,53l13.4-51.5H558.6z"/>
							<path d="M619.8,63.3v35.3h-30.2v-97H631c10.3,0,18.2,1.2,23.6,3.6c5.4,2.4,9.6,6,12.8,10.9s4.7,10.4,4.7,16.4 c0,9.2-3.2,16.7-9.7,22.4c-6.4,5.7-15,8.5-25.8,8.5h-16.8V63.3z M619.4,42.4h10c8.8,0,13.1-3.2,13.1-9.7c0-6.1-4.1-9.1-12.2-9.1 h-10.9L619.4,42.4L619.4,42.4z"/>
							<path d="M777.8,1.5v64.3c0,12.2-3.6,20.9-10.8,26.3c-7.2,5.3-16.6,8-28.3,8c-12.2,0-22-2.6-29.5-7.7c-7.4-5.1-11.1-13.5-11.1-25V1.5 h30.3v62.3c0,4.6,1,8,2.9,10.2c2,2.1,5.1,3.2,9.4,3.2c3.6,0,6.5-0.8,8.8-2.3s3.7-3.3,4.1-5.2c0.4-1.9,0.7-5.7,0.7-11.4V1.5H777.8z" />
							<path d="M805.9,70.4l27.6-5c2.3,7.8,8.3,11.7,17.9,11.7c7.5,0,11.2-2,11.2-6c0-2.1-0.9-3.7-2.6-4.9c-1.7-1.2-4.8-2.2-9.3-3.1 c-17-3.3-27.9-7.5-32.8-12.8c-4.8-5.3-7.2-11.4-7.2-18.6c0-9.1,3.5-16.8,10.4-22.8c6.9-6.1,16.9-9.1,30-9.1 C871,0,884,7.9,890.4,23.8l-24.7,7.5c-2.6-6.5-7.7-9.7-15.6-9.7c-6.5,0-9.7,2-9.7,6c0,1.8,0.7,3.2,2.2,4.2s4.3,1.9,8.5,2.8 c11.6,2.5,19.9,4.6,24.7,6.5c4.9,1.9,9,5.1,12.2,9.7c3.3,4.6,4.9,10,4.9,16.2c0,9.8-4,17.8-11.9,23.9c-8,6.1-18.4,9.1-31.3,9.1 C826.1,100,811.5,90.1,805.9,70.4z"/>
							<path d="M939.8,72.6v25.9h-27V72.6H939.8z"/>
							<path d="M1003.3,100c-13.6,0-24.8-4.5-33.4-13.6c-8.6-9-12.9-21.2-12.9-36.3c0-14.5,4.1-26.5,12.3-35.9C977.6,4.7,989,0,1003.6,0 c13.5,0,24.5,4.5,33,13.4s12.8,20.8,12.8,35.7c0,15.4-4.3,27.7-12.9,37S1016.8,100,1003.3,100z M1003.1,78c5,0,8.6-2.2,10.8-6.6 s3.3-12.4,3.3-24.1c0-16.9-4.5-25.3-13.6-25.3c-9.8,0-14.6,9.6-14.6,28.9C989.1,68.9,993.7,78,1003.1,78z"/>
							<path d="M1162.2,98.5h-33L1115,61.4h-9.4v37.1h-29.8v-97h50.7c11.2,0,19.9,2.6,26,7.9c6.2,5.2,9.3,12.1,9.3,20.7 c0,5.6-1.1,10.5-3.4,14.8s-6.9,8.1-13.8,11.3L1162.2,98.5z M1105.7,40.7h12.7c3.7,0,6.8-0.8,9-2.3c2.3-1.6,3.4-3.9,3.4-6.9 c0-6.2-3.8-9.3-11.4-9.3h-13.7V40.7z"/>
							<path d="M1275,44.1v54.4h-14.6c-1.2-4-2.5-7.4-3.9-10.2c-6,7.8-14.9,11.7-26.7,11.7c-12.5,0-22.8-4.4-30.8-13.1 c-8.1-8.7-12.1-20.6-12.1-35.5c0-14.5,3.9-26.7,11.7-36.6C1206.4,4.9,1218,0,1233.4,0c11.6,0,20.9,2.9,27.9,8.8 c7.1,5.9,11.6,14.2,13.7,25l-28.9,2.8c-1.4-10.1-5.9-15.1-13.3-15.1c-9.8,0-14.6,9.3-14.6,27.9c0,11.2,1.6,18.6,4.7,22.2 s6.9,5.4,11.4,5.4c3.6,0,6.7-1.1,9.2-3.3c2.5-2.2,3.8-5.2,3.9-9h-16.1V44.1H1275z"/>
						</svg>
					</a>
				</div>
				<div class="wpc-menu-container" role="navigation">
					<button class="wpc-toggle-menu" data-toggle="wpc-network-banner" aria-label="' . __( 'Toggle menu', 'wpcampus' ) . '">
						<div class="wpc-toggle-bar"></div>
					</button>
					<ul class="wpc-menu">
						<li><a href="https://wpcampus.org/about/">' . sprintf( __( 'What is %s?', 'wpcampus' ), 'WPCampus' ) . '</a></li>
						<li><a href="https://wpcampus.org/conferences/">' . __( 'Conferences', 'wpcampus' ) . '</a></li>
						<li><a href="https://wpcampus.org/contact/">' . __( 'Contact', 'wpcampus' ) . '</a></li>
						<li class="highlight"><a href="https://wpcampus.org/get-involved/">' . __( 'Get Involved', 'wpcampus' ) . '</a></li>
					</ul>' . $this->get_social_media_icons() .
		        '</div>
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
	public function print_network_banner( $args = array() ) {
		echo $this->get_network_banner( $args );
	}

	/**
	 * Get the network subscribe popup markup.
	 *
	 * @access  public
	 * @return  string|HTML - the markup.
	 */
	public function get_network_subscribe( $args = array() ) {

		// Make sure it's enabled.
		if ( ! $this->enable_network_subscribe ) {
			return;
		}

		// Build the subscribe popup.
		$subscribe = '<div id="wpc-network-subscribe">
			<div class="modal-button-label">' . sprintf( __( 'Want to stay informed on all things %s?', 'wpcampus' ), 'WPCampus' ) . '</div>
			<div class="wpc-network-subscribe-modal" data-launchy data-launchy-button data-launchy-text="' . esc_attr__( 'Subscribe', 'wpcampus' ) . '" data-launchy-title="' . sprintf( esc_attr__( 'Subscribe to the %s mailing list', 'wpcampus' ), 'WPCampus' ) . '">
				<!-- Begin MailChimp Signup Form -->
				<link href="//cdn-images.mailchimp.com/embedcode/classic-10_7.css" rel="stylesheet" type="text/css">
				<div class="wpc-network-signup">
					<form action="https://wpcampus.us11.list-manage.com/subscribe/post?u=6d71860d429d3461309568b92&amp;id=05f39a2a20" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
						<div class="indicates-required"><span class="asterisk">*</span> indicates required</div>
						<div class="mc-field-group">
							<label for="mce-FNAME">First Name</label>
							<input type="text" value="" name="FNAME" class="" id="mce-FNAME">
						</div>
						<div class="mc-field-group">
							<label for="mce-LNAME">Last Name</label>
							<input type="text" value="" name="LNAME" class="" id="mce-LNAME">
						</div>
						<div class="mc-field-group">
							<label for="mce-EMAIL">Email Address  <span class="asterisk">*</span></label>
							<input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL">
						</div>
						<div id="mce-responses" class="clear">
							<div class="response" id="mce-error-response" style="display:none"></div>
							<div class="response" id="mce-success-response" style="display:none"></div>
						</div><!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
						<div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_6d71860d429d3461309568b92_05f39a2a20" tabindex="-1" value=""></div>
						<div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button"></div>
					</form>
				</div>
				<script type="text/javascript" src="//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js"></script>';
				$subscribe .= "<script type='text/javascript'>(function($) {window.fnames = new Array(); window.ftypes = new Array();fnames[1]='FNAME';ftypes[1]='text';fnames[2]='LNAME';ftypes[2]='text';fnames[0]='EMAIL';ftypes[0]='email';fnames[6]='MMERGE6';ftypes[6]='radio';fnames[3]='MMERGE3';ftypes[3]='text';fnames[5]='MMERGE5';ftypes[5]='radio';}(jQuery));var $mcj = jQuery.noConflict(true);</script>";
			$subscribe .= '</div>
		</div>';

		return $subscribe;
	}

	/**
	 * Print the network subscribe markup.
	 *
	 * @access  public
	 * @return  void
	 */
	public function print_network_subscribe( $args = array() ) {
		echo $this->get_network_subscribe( $args );
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
					<div class="wpc-container">
						<div class="wpc-notification-message">
							{{{content.rendered}}}
						</div>
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

	/**
	 * Register the network footer menu.
	 *
	 * @access  public
	 * @return  void
	 */
	function register_network_footer_menu() {
		if ( $this->enable_network_footer ) {
			register_nav_menu( 'footer', __( 'Footer Menu', 'wpcampus' ) );
		}
	}

	/**
	 * Get the network footer markup.
	 *
	 * @access  public
	 * @return  string|HTML - the markup.
	 */
	public function get_network_footer() {

		// Make sure it's enabled.
		if ( ! $this->enable_network_footer ) {
			return;
		}

		$images_dir = "{$this->plugin_url}assets/images/";

		$home_url = 'https://wpcampus.org/';
		$get_involved_url = 'https://wpcampus.org/get-involved/';
		$github_url = 'https://github.com/wpcampus/wpcampus-wp-theme';
		$wp_org_url = 'https://wordpress.org/';

		// Build the footer.
		$footer = '<div id="wpc-network-footer">
			<div class="wpc-container">
				<a class="wpc-logo" href="' . $home_url . '"><img src="' . $images_dir . 'wpcampus-black-tagline.svg" alt="' . sprintf( __( '%1$s: Where %2$s Meets Higher Education', 'wpcampus' ), 'WPCampus', 'WordPress' ) . '" /></a><br />';

		// Add the footer menu.
		$footer .= wp_nav_menu( array(
			'echo'              => false,
			'theme_location'    => 'footer',
			'container'         => false,
			'menu_id'           => 'wpc-network-footer-menu',
			'menu_class'        => 'wpc-network-footer-menu',
			'fallback_cb'       => false,
		));

		$footer .= '<p class="message"><strong>' . sprintf( __( '%1$s is a community of networking, resources, and events for those using %2$s in the world of higher education.', 'wpcampus' ), 'WPCampus', 'WordPress' ) . '</strong><br />' . sprintf( __( 'If you are not a member of the %1$s community, we\'d love for you to %2$sget involved%3$s.', 'wpcampus' ), 'WPCampus', '<a href="' . $get_involved_url . '">', '</a>' ) . '</p>
				<p class="disclaimer">' . sprintf( __( 'This site is powered by %1$s. You can view, and contribute to, the theme on %2$s.', 'wpcampus' ), '<a href="' . $wp_org_url . '">WordPress</a>', '<a href="' . $github_url . '">GitHub</a>' ) . '<br />' . sprintf( __( '%1$s events are not %2$s and are not affiliated with the %3$s Foundation.', 'wpcampus' ), 'WPCampus', 'WordCamps', 'WordPress' ) . '</p>' .
		        $this->get_social_media_icons() . '<p class="copyright">&copy; ' . date( 'Y' ) . ' <a href="' . $home_url . '">WPCampus</a></p>
			</div>
		</div>';

		return $footer;
	}

	/**
	 * Print the network footer markup.
	 *
	 * @access  public
	 * @return  void
	 */
	public function print_network_footer() {
		echo $this->get_network_footer();
	}

	/**
	 * Print the code of conduct message.
	 */
	public function print_code_of_conduct_message() {
		?>
		<div id="wpc-code-of-conduct">
			<div class="wpc-container">
				<div class="container-title"><?php _e( 'Our Code of Conduct', 'wpcampus' ); ?></div>
				<p><?php printf( __( '%1$s seeks to provide a friendly, safe environment in which all participants can engage in productive dialogue, sharing, and learning with each other in an atmosphere of mutual respect. In order to promote such an environment, we require all participants to adhere to our %2$scode of conduct%3$s, which applies to all community interaction and events.', 'wpcampus' ), 'WPCampus', '<a href="https://wpcampus.org/code-of-conduct/">', '</a>' ); ?></p>
			</div>
		</div>
		<?php
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
function wpcampus_print_network_banner( $args = array() ) {
	wpcampus_network()->print_network_banner( $args );
}

/**
 * Interact with the subscribe popup.
 */
function wpcampus_enable_network_subscribe() {
	return wpcampus_network()->enable_network_subscribe();
}
function wpcampus_disable_network_subscribe() {
	return wpcampus_network()->disable_network_subscribe();
}
function wpcampus_get_network_subscribe() {
	return wpcampus_network()->get_network_subscribe();
}
function wpcampus_print_network_subscribe( $args = array() ) {
	wpcampus_network()->print_network_subscribe( $args );
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

/**
 * Interact with the footer.
 */
function wpcampus_enable_network_footer() {
	return wpcampus_network()->enable_network_footer();
}
function wpcampus_disable_network_footer() {
	return wpcampus_network()->disable_network_footer();
}
function wpcampus_get_network_footer() {
	return wpcampus_network()->get_network_footer();
}
function wpcampus_print_network_footer() {
	wpcampus_network()->print_network_footer();
}

/**
 * Interact with social media.
 */
function wpcampus_print_social_media_icons() {
	return wpcampus_network()->print_social_media_icons();
}

/**
 * Interact with the code of conduct.
 */
function wpcampus_print_code_of_conduct_message() {
	return wpcampus_network()->print_code_of_conduct_message();
}
