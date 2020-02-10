<?php
/**
 * Plugin Name:       WPCampus: Network
 * Plugin URI:        https://wpcampus.org
 * Description:       Manages network-wide functionality for the WPCampus network of sites.
 * Version:           1.0.0
 * Author:            WPCampus
 * Author URI:        https://wpcampus.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpcampus-network
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) or die();

/**
 * Load plugin files.
 *
 * @since 1.0.0
 */
$plugin_dir = plugin_dir_path( __FILE__ );

require_once $plugin_dir . 'inc/class-wpcampus-network.php';
require_once $plugin_dir . 'inc/class-wpcampus-network-global.php';
require_once $plugin_dir . 'inc/wpcampus-forms.php';

if ( is_admin() ) {
	require_once $plugin_dir . 'inc/class-wpcampus-network-admin.php';
}

/**
 * Only certain people can see the site
 * while we set things up.
 *
 * Using a lower priority 'parse_request'
 * because that's where 'REST_REQUEST' is defined.
 *
 * @TODO:
 * - Remove before launch.

add_action( 'parse_request', function() {

	// Ignore on the login page.
	if ( 'wp-login.php' == $GLOBALS['pagenow'] ) {
		return;
	}

	// Ignore on the REST API. We need for Printful.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	// For login.
	if ( ! is_user_logged_in() ) {
		auth_redirect();
	}

	// Only certain users can view.
	if ( ! current_user_can( 'manage_wpcampus_shop' ) ) {
		wp_safe_redirect( 'https://wpcampus.org' );
		exit;
	}
}, 100 );*/

/**
 * Returns the instance of our main WPCampus_Network class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin.
 *
 * @return	WPCampus_Network
 */
function wpcampus_network() {
	return WPCampus_Network::instance();
}

/**
 * Helper functions.
 */
function wpcampus_get_network_site_url() {
	return wpcampus_network()->get_network_site_url();
}

/**
 * Interact with network components.
 */
function wpcampus_network_enable( $comp ) {
	wpcampus_network()->enable( $comp );
}
function wpcampus_network_disable( $comp ) {
	wpcampus_network()->disable( $comp );
}

/**
 * Interact with the banner.
 */
function wpcampus_get_network_banner() {
	return wpcampus_network()->get_network_banner();
}
function wpcampus_print_network_banner( $args = array() ) {
	wpcampus_network()->print_network_banner( $args );
}

/**
 * Interact with the notifications.
 */
function wpcampus_get_network_notifications() {
	return wpcampus_network()->get_network_notifications();
}
function wpcampus_print_network_notifications() {
	wpcampus_network()->print_network_notifications();
}

/**
 * Interact with the callout.
 */
function wpcampus_get_network_callout() {
	return wpcampus_network()->get_callout();
}
function wpcampus_print_network_callout() {
	wpcampus_network()->print_callout();
}

/**
 * Interact with the Code of Conduct.
 */
function wpcampus_get_network_coc() {
	return wpcampus_network()->get_code_of_conduct_container();
}
function wpcampus_print_network_coc() {
	wpcampus_network()->print_code_of_conduct_container();
}

/**
 * Interact with the footer.
 */
function wpcampus_get_network_footer() {
	return wpcampus_network()->get_network_footer();
}
function wpcampus_print_network_footer() {
	wpcampus_network()->print_network_footer();
}

/**
 * Interact with the MailChimp signup.
 */
function wpcampus_print_mailchimp_signup() {
	wpcampus_network()->print_mailchimp_signup();
}

/**
 * Print our sessions.
 */
function wpcampus_print_sessions() {
	wpcampus_network()->print_sessions();
}

/**
 * Get markup for the watch videos page.
 */
function wpcampus_print_watch_filters( $videos_id, $args = array() ) {
	wpcampus_network()->print_watch_filters( $videos_id, $args );
}
function wpcampus_print_watch_videos( $html_id, $args = array() ) {
	wpcampus_network()->print_watch_videos( $html_id, $args );
}

/**
 * Interact with social media.
 */
function wpcampus_print_social_media_icons( $args = [] ) {
	wpcampus_network()->print_social_media_icons( $args );
}

/**
 * Help users log in and out.
 */
function wpcampus_get_login_form( $args = array() ) {
	return wpcampus_network()->get_login_form( $args );
}
function wpcampus_print_login_form( $args = array() ) {
	wpcampus_network()->print_login_form( $args );
}

function wpcampus_get_current_rest_route() {
	return wpcampus_network()->get_current_rest_route();
}
