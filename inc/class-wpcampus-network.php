<?php

/**
 * Main class that manages
 * and returns plugin data.
 *
 * @class WPCampus_Network
 */
final class WPCampus_Network {

	/**
	 * Holds the absolute URL,
	 * directory path, and plugin
	 * basename for the plugin
	 * directory.
	 * Plugin basename holds
	 * the relative "path"
	 * to the main plugin file.
	 * Network site URLs holds
	 * the site url for the "main"
	 * site on the network.
	 *
	 * @var string
	 */
	private $plugin_url,
		$plugin_dir,
		$plugin_basename,
		$network_site_url;

	private $site_timezone;

	private $speaker_app_deadline_dt,
		$speaker_app_deadline_default_dt = '3/10/2020 23:59:59';

	/**
	 * List of components to enable and disable.
	 */
	private $components = [
		'banner'          => false,
		'notifications'   => false,
		'coc'             => false,
		'footer'          => false,
		'mailchimp_popup' => false,
		'sessions'        => false,
		'videos'          => false,
	];

	/**
	 * Will hold the current server environment.
	 *
	 * @var string
	 */
	private $environment;

	/**
	 * Holds the class instance.
	 *
	 * @var WPCampus_Network
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @return WPCampus_Network
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name = __CLASS__;
			self::$instance = new $class_name;
		}

		return self::$instance;
	}

	/**
	 * Magic method to output a string if
	 * trying to use the object as a string.
	 *
	 * @return string
	 */
	public function __toString() {
		return sprintf( __( '%s Network', 'wpcampus-network' ), 'WPCampus' );
	}

	/**
	 * Method to keep our instance
	 * from being cloned or unserialized
	 * and to prevent a fatal error when
	 * calling a method that doesn't exist.
	 *
	 * @return void
	 */
	public function __clone() { }

	public function __wakeup() { }

	public function __call( $method = '', $args = [] ) { }

	/**
	 * Start your engines.
	 */
	protected function __construct() { }

	/**
	 * Returns our current server environment.
	 *
	 * @return string
	 */
	public function get_environment() {
		if ( isset( $this->environment ) ) {
			return $this->environment;
		}
		$this->environment = ! empty( $_ENV['PANTHEON_ENVIRONMENT'] ) ? $_ENV['PANTHEON_ENVIRONMENT'] : '';
		return $this->environment;
	}

	/**
	 * Returns the absolute URL to
	 * the main plugin directory.
	 *
	 * @return string
	 */
	public function get_plugin_url() {
		if ( isset( $this->plugin_url ) ) {
			return $this->plugin_url;
		}
		$this->plugin_url = plugin_dir_url( dirname( __FILE__ ) );

		return $this->plugin_url;
	}

	/**
	 * Returns the directory path
	 * to the main plugin directory.
	 *
	 * @return string
	 */
	public function get_plugin_dir() {
		if ( isset( $this->plugin_dir ) ) {
			return $this->plugin_dir;
		}
		$this->plugin_dir = plugin_dir_path( dirname( __FILE__ ) );

		return $this->plugin_dir;
	}

	/**
	 * Returns the relative "path"
	 * to the main plugin file.
	 *
	 * @return string
	 */
	public function get_plugin_basename() {
		if ( isset( $this->plugin_basename ) ) {
			return $this->plugin_basename;
		}
		$this->plugin_basename = 'wpcampus-network-plugin/wpcampus-network.php';

		return $this->plugin_basename;
	}

	/**
	 * Get the site's timezone abbreviation.
	 */
	public function get_site_timezone() {

		// If already set, return.
		if ( isset( $this->site_timezone ) ) {
			return $this->site_timezone;
		}

		// Get from settings.
		$timezone = get_option( 'timezone_string' );
		if ( empty( $timezone ) ) {
			$timezone = 'UTC';
		}

		// Get abbreviation.
		return $this->site_timezone = new DateTimeZone( $timezone );
	}

	/**
	 * Disables cache.
	 */
	public function add_header_nocache() {
		header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	}

	public function get_current_rest_route() {
		$global_query = $GLOBALS['wp']->query_vars;
		if ( empty( $global_query['rest_route'] ) ) {
			return '';
		}
		$rest_route = $global_query['rest_route'];

		return '/' == $rest_route ? $rest_route : untrailingslashit( $rest_route );
	}

	public function get_post_by_name( $name, $post_type = '' ) {
		global $wpdb;
		$query = $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_name = %s", $name );
		if ( ! empty( $post_type ) ) {
			$query .= $wpdb->prepare( ' AND post_type = %s', $post_type );
		}

		return $wpdb->get_row( $query );
	}

	/**
	 * Get the main site URL.
	 *
	 * @return string
	 */
	public function get_network_site_url() {
		if ( isset( $this->network_site_url ) ) {
			return $this->network_site_url;
		}
		$this->network_site_url = network_site_url();

		return $this->network_site_url;
	}

	/**
	 * Enable and disable various network components.
	 *
	 * @param string|array - $comp - list of components to enable
	 */
	public function enable( $comp ) {
		if ( ! is_array( $comp ) ) {
			$comp = explode( ',', str_replace( ' ', '', $comp ) );
		}
		foreach ( $comp as $component ) {
			if ( ! array_key_exists( $component, $this->components ) ) {
				continue;
			}
			$this->components[ $component ] = true;
		}
	}

	public function disable( $comp ) {
		if ( ! is_array( $comp ) ) {
			$comp = explode( ',', str_replace( ' ', '', $comp ) );
		}
		foreach ( $comp as $component ) {
			if ( ! array_key_exists( $component, $this->components ) ) {
				continue;
			}
			$this->components[ $component ] = false;
		}
	}

	/**
	 * Returns true if a component is enabled.
	 *
	 * @return bool - true if component is enabled.
	 */
	public function is_enabled( $component ) {
		if ( ! array_key_exists( $component, $this->components ) ) {
			return null;
		}

		return ( true === $this->components[ $component ] );
	}

	public function get_login_nonce_field() {
		return wp_nonce_field( 'wpc_ajax_login', 'wpc_ajax_login_nonce', true, false );
	}

	public function get_logout_nonce_field() {
		return wp_nonce_field( 'wpc_ajax_logout', 'wpc_ajax_logout_nonce', true, false );
	}

	/**
	 * Enqueue the network base script
	 */
	public function enqueue_base_script() {
		$ver = '1.0';
		wp_enqueue_script(
			'wpc-network-base',
			trailingslashit( $this->get_plugin_url() ) . 'assets/js/wpc-network.min.js',
			[],
			null,
			$ver
		);
	}

	public function enqueue_login_script() {

		return;

		// Enqueue login script.
		$plugin_url = $this->get_plugin_url();
		$js_dir = trailingslashit( $plugin_url . 'assets/js' );

		wp_enqueue_script( 'wpc-ajax-login', $js_dir . 'wpc-network-login.min.js', [ 'jquery' ], null, true );
		wp_localize_script(
			'wpc-ajax-login',
			'wpc_ajax_login',
			[
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			]
		);
	}

	/**
	 * Assigns all users to all blogs.
	 *
	 * @return void
	 */
	public function assign_all_users_to_all_blogs() {

		$users = get_users( [ 'fields' => 'id' ] );
		if ( empty( $users ) ) {
			return;
		}

		foreach ( $users as $user_id ) {
			$this->assign_user_to_all_blogs( $user_id );
		}
	}

	/**
	 * Makes sure a user is added to every site.
	 *
	 * @param  $user_id - int - the user ID.
	 *
	 * @return void
	 */
	public function assign_user_to_all_blogs( $user_id ) {

		$all_blogs = get_sites(
			[
				'public'   => 1,
				'archived' => 0,
				'spam'     => 0,
				'deleted'  => 0,
			]
		);

		if ( empty( $all_blogs ) ) {
			return;
		}

		// Get user's existing blog info.
		$user_existing_blogs = get_blogs_of_user( $user_id );
		$user_existing_blog_ids = ! empty( $user_existing_blogs ) ? wp_list_pluck( $user_existing_blogs, 'userblog_id' ) : [];

		/*
		 * Loops through each blog. Checks if user
		 * is already a member of the blog. If so, skips.
		 * If not, then adds user to the blog as a "member".
		 */
		foreach ( $all_blogs as $this_blog ) {

			// Don't need to worry about if user already member of blog.
			if ( in_array( $this_blog->blog_id, $user_existing_blog_ids ) ) {
				continue;
			}

			// Add as "member" role.
			add_user_to_blog( $this_blog->blog_id, $user_id, 'member' );

		}
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_login_form( $args = [] ) {

		$defaults = [
			'redirect'       => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
			'form_id'        => 'loginform',
			'label_username' => __( 'Username or Email Address' ),
			'label_password' => __( 'Password' ),
			'label_remember' => __( 'Remember Me' ),
			'label_log_in'   => __( 'Log In' ),
			'id_username'    => 'user_login',
			'id_password'    => 'user_pass',
			'id_remember'    => 'rememberme',
			'id_submit'      => 'wp-submit',
			'remember'       => true,
			'value_username' => '',
			'value_remember' => false,
			'wpc_ajax'       => true,
		];

		$args = wp_parse_args( $args, $defaults );

		// Make sure we don't echo.
		$args['echo'] = false;

		return '<div class="wpcampus-login-form wpcampus-login-ajax">' . wp_login_form( $args ) . '</div>';
	}

	/**
	 * @param array $args
	 */
	public function print_login_form( $args = [] ) {
		echo $this->get_login_form( $args );
	}

	/**
	 * Get the network banner markup.
	 *
	 * @return string|HTML - the markup.
	 */
	public function get_network_banner( $args = [] ) {

		// Make sure it's enabled.
		if ( ! $this->is_enabled( 'banner' ) ) {
			return;
		}

		// Parse incoming $args with defaults.
		$args = wp_parse_args(
			$args,
			[
				'skip_nav_id'    => '',
				'skip_nav_label' => __( 'Skip to content', 'wpcampus-network' ),
			]
		);

		// Add the banner.
		$banner = '<nav id="wpc-network-banner" aria-label="' . __( 'Network-wide', 'wpcampus-network' ) . '">';

		// Add skip navigation.
		if ( ! empty( $args['skip_nav_id'] ) ) {

			// Make sure we have a valid ID.
			$skip_nav_id = preg_replace( '/[^a-z0-9\-]/i', '', $args['skip_nav_id'] );
			if ( ! empty( $skip_nav_id ) ) {
				$banner .= sprintf( '<a href="#%s" class="wpc-skip-to-content">%s</a>', $skip_nav_id, $args['skip_nav_label'] );
			}
		}

		$banner .= '<div class="wpc-container">
				<div class="wpc-logo">
					<a href="https://wpcampus.org">
						<?xml version="1.0" encoding="utf-8"?>
						<svg version="1.1" id="WPCampusOrgLogo" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 1275 100" style="enable-background:new 0 0 1275 100;" xml:space="preserve">
							<title>' . sprintf( __( '%1$s: Where %2$s Meets Higher Education', 'wpcampus-network' ), 'WPCampus', 'WordPress' ) . '</title>
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
				<div class="wpc-menu-container">
					<button class="wpc-toggle-menu" data-toggle="wpc-network-banner" aria-label="' . __( 'Toggle menu', 'wpcampus-network' ) . '">
						<div class="wpc-toggle-bar"></div>
					</button>
					<ul class="wpc-menu">
						<li><a href="https://wpcampus.org/about/" title="' . sprintf( __( 'About %s', 'wpcampus-network' ), 'WPCampus' ) . '">' . __( 'About', 'wpcampus-network' ) . '</a></li>
						<li><a href="https://wpcampus.org/blog/">' . __( 'Blog', 'wpcampus-network' ) . '</a></li>
						<li><a href="https://wpcampus.org/library/">' . __( 'Library', 'wpcampus-network' ) . '</a></li>
						<li><a href="https://wpcampus.org/conferences/">' . __( 'Conferences', 'wpcampus-network' ) . '</a></li>
						<li><a href="https://shop.wpcampus.org/">' . __( 'Shop', 'wpcampus-network' ) . '</a></li>
						<li><a href="https://wpcampus.org/contact/">' . __( 'Contact', 'wpcampus-network' ) . '</a></li>
						<li class="highlight"><a href="https://wpcampus.org/get-involved/">' . __( 'Get Involved', 'wpcampus-network' ) . '</a></li>
					</ul>' . $this->get_social_media_icons() .
		           '</div>
			</div>
		</nav>';

		return $banner;
	}

	/**
	 * Print the network banner markup.
	 *
	 * @return void
	 */
	public function print_network_banner( $args = [] ) {
		echo $this->get_network_banner( $args );
	}

	/**
	 * Get the network notifications markup.
	 *
	 * @return string|HTML - the markup.
	 */
	public function get_network_notifications() {

		// Make sure it's enabled.
		if ( ! $this->is_enabled( 'notifications' ) ) {
			return;
		}

		// Build the notifications.
		$notifications = '<aside id="wpc-notifications" aria-label="' . esc_attr__( 'Notifications', 'wpcampus-network' ) . '"></aside>
		<script id="wpc-notification-template" type="x-tmpl-mustache">
			{{#.}}
				<div role="alert" class="wpc-notification">
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
	 * @return void
	 */
	public function print_network_notifications() {
		echo $this->get_network_notifications();
	}

	/**
	 * Get the network callout.
	 *
	 * @return string
	 */
	public function get_callout() {
		return '';
		ob_start();
		//$this->print_livestream_callout();
		//$this->print_audit_callout();
		return ob_get_clean();
	}

	/**
	 * Print the network callout.
	 *
	 * @return void
	 */
	public function print_callout() {
		//$this->print_livestream_callout();
		//$this->print_audit_callout();
	}

	public function print_audit_webinar_callout() {
		?>
		<div class="panel center">
			<h2>Webinar for Gutenberg accessibility audit</h2>
			<p>Tenon LLC, will host a public webinar to discuss the <a href="https://wpcampus.org/2019/05/gutenberg-audit-results/">results of the Gutenberg accessibility audit</a>. The webinar will take place <strong>Monday, May 13, 2019 at 12:00 PM CDT</strong>. You must register to attend.</p>
			<a class="button primary expand bigger" href="https://wpcampus.org/audit/webinar/">Learn more about the accessibility audit webinar</a>
		</div>
		<?php
	}

	public function print_audit_callout() {
		?>
		<div class="panel center">
			<h2>Results of Gutenberg Accessibility Audit</h2>
			<p>In late 2018, WPCampus released a request for proposals to conduct an accessibility audit of the WordPress block editor, also known as Gutenberg. In early 2019, we announced our selection of Tenon, LLC to conduct the audit. We are excited to share the results of the Gutenberg accessibility audit.</p>
			<a class="button primary expand bigger" href="https://wpcampus.org/2019/05/gutenberg-audit-results/">Read the Gutenberg accessibility audit results</a>
		</div>
		<?php
	}

	public function print_register_callout() {
		?>
		<div class="panel callout light-royal-blue light-blue center">
			<h2>Register for WPCampus 2019</h2>
			<p>Registration is open for <a href="https://2019.wpcampus.org/">WPCampus 2019</a>! Join us this summer, July 25-27, for three days of hands-on workshops, sessions, and networking focused on accessibility and WordPress in higher education.</p>
			<a class="button primary expand bigger" style="text-decoration:underline;" href="https://2019.wpcampus.org/tickets/"><strong>Attend WPCampus 2019</strong></a>
		</div>
		<?php
	}

	public function print_livestream_callout() {
		?>
		<div class="panel callout light-royal-blue light-blue center">
			<h2>Join WPCampus 2019 on the live stream</h2>
			<p>WPCampus 2019 will be live streamed for free, no registration necessary. Simply visit our website July 26-27 for 2 days of sessions focused on accessibility and WordPress in higher education.</p>
			<a class="button primary expand bigger" style="text-decoration:underline;" href="https://2019.wpcampus.org/watch/"><strong>Watch WPCampus 2019</strong></a>
		</div>
		<?php
	}

	/**
	 * Get the network footer markup.
	 *
	 * @return string|HTML - the markup.
	 */
	public function get_network_footer() {

		// Make sure it's enabled.
		if ( ! $this->is_enabled( 'footer' ) ) {
			return;
		}

		$images_dir = "{$this->get_plugin_url()}assets/images/";

		$home_url = 'https://wpcampus.org/';
		$get_involved_url = 'https://wpcampus.org/get-involved/';
		$github_url = 'https://github.com/wpcampus/wpcampus-wp-theme';
		$wp_org_url = 'https://wordpress.org/';

		// Build the footer.
		$footer = '<footer id="wpc-network-footer">
			<div class="wpc-container">
				<a class="wpc-logo" href="' . $home_url . '"><img src="' . $images_dir . 'wpcampus-black-tagline.svg" alt="' . sprintf( __( '%1$s: Where %2$s Meets Higher Education', 'wpcampus-network' ), 'WPCampus', 'WordPress' ) . '" /></a><br />';

		// Add the footer menu.
		$footer_menu = wp_nav_menu(
			[
				'echo'           => false,
				'theme_location' => 'footer',
				'container'      => false,
				'menu_id'        => false,
				'menu_class'     => false,
				'fallback_cb'    => false,
			]
		);

		if ( empty( $footer_menu ) ) {
			$footer_menu = '<ul>
                <li><a href="https://wpcampus.org/about/">About WPCampus</a></li>
                <li><a href="https://wpcampus.org/code-of-conduct/">Code of Conduct</a></li>
                <li><a href="https://wpcampus.org/diversity/">Diversity, Equity, and Inclusion</a></li>
                <li><a href="https://wpcampus.org/contact/">Contact us</a></li>
            </ul>';
		}

		if ( ! empty( $footer_menu ) ) {
			$footer .= '<nav id="wpc-network-footer-menu" class="wpc-network-footer-menu" aria-label="' . esc_attr__( 'Footer', 'wpcampus-network' ) . '">' . $footer_menu . '</nav>';
		}

		$footer .= '<p class="message"><strong>' . sprintf( __( '%1$s is a community of networking, resources, and events for those using %2$s in the world of higher education.', 'wpcampus-network' ), 'WPCampus', 'WordPress' ) . '</strong><br />' . sprintf( __( 'If you are not a member of the %1$s community, we\'d love for you to %2$sget involved%3$s.', 'wpcampus-network' ), 'WPCampus', '<a href="' . $get_involved_url . '">', '</a>' ) . '</p>
				<p class="disclaimer">' . sprintf( __( 'This site is powered by %1$s. You can view, and contribute to, the theme on %2$s.', 'wpcampus-network' ), '<a href="' . $wp_org_url . '">WordPress</a>', '<a href="' . $github_url . '">GitHub</a>' ) . '</p>' .
		           $this->get_social_media_icons() . '<p class="copyright">&copy; 2015-' . date( 'Y' ) . ' <a href="' . $home_url . '">WPCampus</a></p>
			</div>
		</footer>';

		return $footer;
	}

	/**
	 * Print the network footer markup.
	 *
	 * @return void
	 */
	public function print_network_footer() {
		echo $this->get_network_footer();
	}

	/**
	 * Print the MailChimp signup form.
	 *
	 * @return string
	 */
	public function print_mailchimp_signup() {

		$css_ver = 4;

		?>
		<link href="<?php echo trailingslashit( $this->get_plugin_url() . 'assets/css' ); ?>wpc-network-mailchimp.min.css?ver=<?php echo $css_ver; ?>" rel="stylesheet" type="text/css">
		<aside class="wpc-mc-signup" aria-label="<?php esc_attr_e( 'Signup for newsletters', 'wpcampus-network' ); ?>">
			<div class="wpc-container">
				<h2><?php printf( __( 'Subscribe to %s updates', 'wpcampus-network' ), 'WPCampus' ); ?></h2>
				<div class="wpc-mc-cols">
					<div class="wpc-mc-text">
						<h3><?php printf( __( 'The %s newsletter', 'wpcampus-network' ), 'WPCampus' ); ?></h3>
						<p>This newsletter is sent out manually and includes broad updates about our community and conferences.</p>
					</div>
					<a class="button wpc-mc-button" href="http://eepurl.com/dukZvP"><?php _e( 'Subscribe to our newsletter', 'wpcampus-network' ); ?></a>
					<div class="wpc-mc-text">
						<h3><?php printf( __( '%s blog updates', 'wpcampus-network' ), 'WPCampus' ); ?></h3>
						<p>This mailing list sends an automated email that lets you know when we post to the WPCampus blog.</p>
					</div>
					<a class="button wpc-mc-button" href="http://eepurl.com/dOd-Q9"><?php _e( 'Subscribe to blog updates', 'wpcampus-network' ); ?></a>
				</div>
				<p class="wpc-mc-disclaimer">You can unsubscribe at any time by clicking the link in the footer of our emails. We use <a href="https://mailchimp.com/">Mailchimp</a> as our marketing platform. By subscribing, you acknowledge that your information will be transferred to Mailchimp for processing. <a href="https://mailchimp.com/legal/" target="_blank">Learn more about Mailchimp's privacy practices.</a></p>
			</div>
		</aside>
		<?php

		return;
		?>
		<link href="//cdn-images.mailchimp.com/embedcode/classic-10_7.css" rel="stylesheet" type="text/css">
		<link href="<?php echo trailingslashit( $this->get_plugin_url() . 'assets/css' ); ?>wpc-network-mailchimp.min.css?ver=<?php echo $css_ver; ?>" rel="stylesheet" type="text/css">
		<aside class="wpc-mc-signup-forms" aria-label="<?php esc_attr_e( 'Signup for newsletters', 'wpcampus-network' ); ?>">
			<h2><?php printf( __( 'Subscribe to %s updates', 'wpcampus-network' ), 'WPCampus' ); ?></h2>
			<div class="indicates-required"><span class="asterisk">*</span> indicates required</div>
			<div class="wpc-mc-signup-forms">
				<form action="https://wpcampus.us11.list-manage.com/subscribe/post?u=6d71860d429d3461309568b92&amp;id=05f39a2a20" method="post" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
					<h3><?php printf( __( 'The %s newsletter', 'wpcampus-network' ), 'WPCampus' ); ?></h3>
					<p>This newsletter is sent out manually and includes broad updates about our community and conferences.</p>
					<div class="mc-field-group">
						<label for="mce-EMAIL1">Email <span class="asterisk">*</span></label>
						<div class="mc-field-email">
							<input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL1">
						</div>
					</div>
					<div id="mce-responses1" class="clear">
						<div class="response" id="mce-error-response1" style="display:none"></div>
						<div class="response" id="mce-success-response1" style="display:none"></div>
					</div><!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
					<div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_6d71860d429d3461309568b92_05f39a2a20" tabindex="-1" value=""></div>
					<div class="clear"><input type="submit" value="<?php esc_attr_e( 'Subscribe to newsletter', 'wpcampus-network' ); ?>" name="subscribe" class="wpc-mc-button button"></div>
				</form>
				<form action="https://wpcampus.us11.list-manage.com/subscribe/post?u=6d71860d429d3461309568b92&amp;id=20487ce102" method="post" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
					<h3><?php printf( __( '%s blog updates', 'wpcampus-network' ), 'WPCampus' ); ?></h3>
					<p>This mailing list sends an automated email that lets you know when we post to the WPCampus blog.</p>
					<div class="mc-field-group">
						<label for="mce-EMAIL2">Email <span class="asterisk">*</span></label>
						<div class="mc-field-email">
							<input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL2">
						</div>
					</div>
					<div id="mce-responses2" class="clear">
						<div class="response" id="mce-error-response2" style="display:none"></div>
						<div class="response" id="mce-success-response2" style="display:none"></div>
					</div><!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
					<div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_6d71860d429d3461309568b92_20487ce102" tabindex="-1" value=""></div>
					<div class="clear"><input type="submit" value="Subscribe to blog updates" name="subscribe" class="wpc-mc-button button"></div>
				</form>
			</div>
			<p class="wpc-mc-disclaimer">You can unsubscribe at any time by clicking the link in the footer of our emails. We use <a href="https://mailchimp.com/">Mailchimp</a> as our marketing platform. By subscribing, you acknowledge that your information will be transferred to Mailchimp for processing. <a href="https://mailchimp.com/legal/" target="_blank">Learn more about Mailchimp's privacy practices.</a></p>
		</aside>
		<?php
		//<script type='text/javascript' src='//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js'></script><script type='text/javascript'>(function($) {window.fnames = new Array(); window.ftypes = new Array();fnames[1]='FNAME';ftypes[1]='text';fnames[2]='LNAME';ftypes[2]='text';fnames[0]='EMAIL';ftypes[0]='email';fnames[6]='MMERGE6';ftypes[6]='radio';fnames[3]='MMERGE3';ftypes[3]='text';fnames[5]='MMERGE5';ftypes[5]='radio';}(jQuery));var $mcj = jQuery.noConflict(true);</script>

	}

	/**
	 * Print our list of sessions.
	 */
	public function print_sessions() {

		// Get filters.
		$args = [];
		$filters = [
			'assets'  => [ 'slides', 'video' ],
			'orderby' => [ 'date', 'title' ],
			'order'   => [ 'asc', 'desc' ],
			'event'   => [
				'wpcampus-2019',
				'wpcampus-2018',
				'wpcampus-2017',
				'wpcampus-2016',
				'wpcampus-online-2019',
				'wpcampus-online-2018',
				'wpcampus-online-2017',
			],
		];

		foreach ( $filters as $filter => $options ) {
			if ( ! empty( $_GET[ $filter ] ) ) {

				$filter_val = strtolower( str_replace( ' ', '', $_GET[ $filter ] ) );

				if ( ! is_array( $filter_val ) ) {
					$filter_val = explode( ',', $filter_val );
				}

				foreach ( $filter_val as $value ) {

					if ( in_array( $value, $options ) ) {

						if ( ! is_array( $args[ $filter ] ) ) {
							$args[ $filter ] = [];
						}

						$args[ $filter ][] = $value;

					}
				}
			}
		}

		if ( ! empty( $_GET['search'] ) ) {
			$args['search'] = sanitize_text_field( $_GET['search'] );
		}

		if ( ! empty( $_GET['subject'] ) ) {
			$subjects = $_GET['subject'];
			if ( is_array( $subjects ) ) {
				$subjects = implode( ',', $subjects );
			}
			$args['subject'] = sanitize_text_field( $subjects );
		}

		if ( ! empty( $_GET['format'] ) ) {
			$formats = $_GET['format'];
			if ( is_array( $formats ) ) {
				$formats = implode( ',', $formats );
			}
			$args['format'] = sanitize_text_field( $formats );
		}

		// Create the string.
		$data_str = '';
		foreach ( $args as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = implode( ',', $value );
			}
			$data_str .= ' data-' . $key . '="' . esc_attr( $value ) . '"';
		}

		?>
		<div id="wpcampus-sessions" role="region" class="wpcampus-sessions-container loading"<?php echo $data_str; ?>>
			<div class="wpcampus-sessions-filters"></div>
			<div class="wpcampus-sessions"></div>
			<div class="wpcampus-sessions-error">
				<p><?php _e( 'Oops. Looks like something went wrong. Please refresh the page and try again.', 'wpcampus-network' ); ?></p><p><?php printf( __( 'If the problem persists, please %1$slet us know%2$s.', 'wpcampus' ), '<a href="/contact/">', '</a>' ); ?></p>
			</div>
			<div class="wpcampus-sessions-loading">
				<p class="screen-reader-text"><?php _e( 'Loading sessions.', 'wpcampus' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Print the watch video filters.
	 *
	 * @return void
	 */
	public function print_watch_filters( $videos_id, $args = [] ) {

		$args = wp_parse_args(
			$args,
			[
				'playlist' => null,
				'category' => null,
				'search'   => null,
			]
		);

		// Remove empty filters.
		$args = array_filter( $args );

		// Get the playlists.
		$playlists = get_terms(
			'playlist',
			[
				'hide_empty' => true,
			]
		);

		// Get the categories.
		$categories = get_terms(
			'category',
			[
				'hide_empty' => true,
			]
		);

		$filters_class = [ 'wpc-watch-filters' ];

		if ( ! empty( $args ) ) {
			$filters_class[] = 'has-filters';
		}

		?>
		<div class="<?php echo implode( ' ', $filters_class ); ?>" data-videos="<?php echo $videos_id; ?>">
			<span class="form-label"><?php _e( 'Filter videos:', 'wpcampus-network' ); ?></span>
			<form action="/videos/">
				<select name="playlist" class="filter filter-event" title="<?php esc_attr_e( 'Filter videos by event', 'wpcampus-network' ); ?>">
					<option value=""><?php _e( 'All events', 'wpcampus-network' ); ?></option>
					<option value="podcast"<?php selected( ! empty( $args['playlist'] ) && 'podcast' == $args['playlist'] ) ?>><?php printf( __( '%s Podcast', 'wpcampus-network' ), 'WPCampus' ); ?></option>
					<?php

					foreach ( $playlists as $playlist ) :
						?>
						<option value="<?php echo $playlist->slug; ?>"<?php selected( ! empty( $args['playlist'] ) && $args['playlist'] == $playlist->slug ); ?>><?php echo $playlist->name; ?></option>
					<?php
					endforeach;

					?>
				</select>
				<select name="category" class="filter filter-category" title="<?php esc_attr_e( 'Filter videos by category', 'wpcampus-network' ); ?>">
					<option value=""><?php _e( 'All categories', 'wpcampus-network' ); ?></option>
					<?php

					foreach ( $categories as $cat ) :
						?>
						<option value="<?php echo $cat->slug; ?>"<?php selected( ! empty( $args['category'] ) && $args['category'] == $cat->slug ); ?>><?php echo $cat->name; ?></option>
					<?php
					endforeach;

					?>
				</select>
				<select name="orderby"></select>
				<?php

				// Filter by authors.
				if ( function_exists( 'wpcampus_media_videos' ) && method_exists( wpcampus_media_videos(), 'get_video_authors' ) ) :

					// Get authors.
					$authors = wpcampus_media_videos()->get_video_authors();
					if ( ! empty( $authors ) ) :

						?>
						<select name="author" class="filter filter-author" title="<?php esc_attr_e( 'Filter videos by author', 'wpcampus-network' ); ?>">
							<option value=""><?php _e( 'All authors', 'wpcampus-network' ); ?></option>
							<?php

							foreach ( $authors as $author ) :
								?>
								<option value="<?php echo $author->nicename; ?>"<?php selected( ! empty( $args['author'] ) && $args['author'] == $author->nicename ); ?>><?php echo $author->display_name; ?></option>
							<?php
							endforeach;

							?>
						</select>
					<?php
					endif;
				endif;

				?>
				<input type="search" class="search-videos" name="search" value="<?php echo ! empty( $args['search'] ) ? esc_attr( $args['search'] ) : ''; ?>" placeholder="<?php esc_attr_e( 'Search videos', 'wpcampus-network' ); ?>" title="<?php esc_attr_e( 'Search videos', '' ); ?>"/> <span role="button" tabindex="0" class="button red expand clear" aria-label="<?php esc_attr_e( 'Clear filters', 'wpcampus-network' ); ?>"><?php _e( 'Clear', 'wpcampus-network' ); ?></span>
				<input type="submit" class="update-videos" value="<?php esc_attr_e( 'Update', 'wpcampus-network' ); ?>" title="<?php esc_attr_e( 'Update videos', 'wpcampus-network' ); ?>"/>
			</form>
		</div>
		<?php
	}

	/**
	 * Processes and returns the markup
	 * for displaying videos.
	 *
	 * @param  $args - array - arguments for display.
	 *
	 * @return string - the markup.
	 */
	public function print_watch_videos( $html_id, $args = [] ) {

		$args = wp_parse_args(
			$args,
			[
				'show_event'   => true,
				'show_filters' => true,
				'playlist'     => null,
				'category'     => null,
				'search'       => null,
			]
		);

		// Remove empty filters.
		$args = array_filter( $args );

		$watch_attrs = [];

		foreach ( [ 'playlist', 'category', 'search' ] as $key ) {
			if ( ! empty( $args[ $key ] ) ) {
				$watch_attrs[ $key ] = $args[ $key ];
			}
		}

		$watch_attrs_string = '';
		foreach ( $watch_attrs as $attr => $value ) {
			$watch_attrs_string .= ' data-' . $attr . '="' . esc_attr( $value ) . '"';
		}

		if ( isset( $args['show_filters'] ) && true == $args['show_filters'] ) {
			$this->print_watch_filters( $html_id, $watch_attrs );
		}

		?>
		<div id="<?php echo $html_id; ?>" class="wpc-watch loading"<?php echo $watch_attrs_string; ?>>
			<span class="wpc-watch-loading-message"><?php _e( 'Loading videos...', 'wpcampus-network' ); ?></span>
		</div>
		<script id="wpc-watch-template" type="text/x-handlebars-template">
			{{#if .}}
			{{{videos_count_message}}}
			<div class="wpc-watch-videos">
				{{#each .}}
				<div class="wpc-watch-video">
					<div class="video-media">
						<a class="video-popup" role="button" title="Play the video for {{post_title}}" href="{{watch_permalink}}">
							<img class="video-thumbnail" src="{{thumbnail}}" alt="<?php _e( 'Thumbnail for the video', 'wpcampus-network' ); ?>" />
							<span class="video-play"></span>
						</a>
					</div>
					<div class="video-info">
						<div class="video-title"><a href="{{permalink}}">{{{post_title}}}</a></div>
						<div class="video-meta">
							<?php echo ( isset( $args['show_event'] ) && true == $args['show_event'] ) ? '{{{video_event}}}' : ''; ?>
							{{#if authors}}
							<ul class="video-authors">
								{{#each authors}}
								<li class="video-author">
									<a href="{{permalink}}">
										<img class="video-author-avatar" src="{{avatar}}" alt="Avatar for {{display_name}}">
										<span class="video-author-name">{{display_name}}</span>
									</a>
								</li>
								{{/each}}
							</ul>
							{{/if}}
						</div>
					</div>
				</div>
				{{/each}}
			</div>
			{{/if}}
		</script>
		<?php
	}

	/**
	 * Gets markup for list of social media icons.
	 *
	 * @return string|HTML - the markup.
	 */
	public function get_social_media_icons( $args = [] ) {

		$defaults = [];

		$args = wp_parse_args( $args, $defaults );

		$images_dir = $this->get_plugin_dir() . 'assets/images/';
		$social = [
			'slack'    => [
				'title' => sprintf( __( 'Join %1$s on %2$s', 'wpcampus-network' ), 'WPCampus', 'Slack' ),
				'href'  => 'https://wpcampus.org/get-involved/',
			],
			'twitter'  => [
				'title' => sprintf( __( 'Follow %1$s on %2$s', 'wpcampus-network' ), 'WPCampus', 'Twitter' ),
				'href'  => 'https://twitter.com/wpcampusorg',
			],
			'facebook' => [
				'title' => sprintf( __( 'Follow %1$s on %2$s', 'wpcampus-network' ), 'WPCampus', 'Facebook' ),
				'href'  => 'https://www.facebook.com/wpcampus',
			],
			'youtube'  => [
				'title' => sprintf( __( 'Follow %1$s on %2$s', 'wpcampus-network' ), 'WPCampus', 'YouTube' ),
				'href'  => 'https://www.youtube.com/wpcampusorg',
			],
			'github'   => [
				'title' => sprintf( __( 'Follow %1$s on %2$s', 'wpcampus-network' ), 'WPCampus', 'GitHub' ),
				'href'  => 'https://github.com/wpcampus/',
			],
		];

		$icons = '<nav class="social-media-icons" aria-label="' . __( 'Social media', 'wpcampus-network' ) . '"><ul>';

		foreach ( $social as $key => $info ) {
			$filename = "{$images_dir}{$key}.php";
			if ( file_exists( $filename ) ) {
				$icons .= sprintf(
					'<li class="%1$s"><a href="%2$s" title="%3$s">%4$s</a></li>',
					$key,
					$info['href'],
					$info['title'],
					file_get_contents( $filename )
				);
			}
		}

		$icons .= '</ul></nav>';

		return $icons;
	}

	/**
	 * Prints markup for list of social media icons.
	 *
	 * @return void
	 */
	public function print_social_media_icons( $args = [] ) {
		echo $this->get_social_media_icons( $args );
	}

	/**
	 * Get the full content of the WPCampus Code of Conduct.
	 */
	public function get_code_of_conduct() {

		$output = null;
		$request_url = 'https://wpcampus.org/wp-json/wp/v2/pages/8716';
		$request = wp_safe_remote_get( $request_url );

		// Get the content.
		if ( '200' == wp_remote_retrieve_response_code( $request ) ) {
			$body = json_decode( wp_remote_retrieve_body( $request ) );
			if ( ! empty( $body->content->rendered ) ) {
				$output = $body->content->rendered;
			}
		}

		if ( empty( $output ) ) {
			$read_url = 'https://wpcampus.org/code-of-conduct/';
			$output = '<p>Read the <a href="' . $read_url . '">WPCampus Code of Conduct</a></p>';
		}

		return $output;
	}

	/**
	 * Print the full content of the WPCampus Code of Conduct.
	 */
	public function print_code_of_conduct() {
		echo $this->get_code_of_conduct();
	}

	/**
	 * Get the short Code of Conduct message.
	 *
	 * @return string
	 */
	public function get_code_of_conduct_message() {
		return sprintf( __( '%1$s seeks to provide a friendly, safe environment.  All participants should be able to engage in productive dialogue. They should share and learn with each other in an atmosphere of mutual respect. We require all participants adhere to our %2$scode of conduct%3$s. This applies to all community interaction and events.', 'wpcampus-network' ), 'WPCampus', '<a href="https://wpcampus.org/code-of-conduct/">', '</a>' );
	}

	/**
	 * Gets the markup for the Code of Conduct container.
	 *
	 * @return string
	 */
	public function get_code_of_conduct_container() {

		// Make sure it's enabled.
		if ( ! $this->is_enabled( 'coc' ) ) {
			return;
		}

		return '<aside id="wpc-code-of-conduct" aria-label="' . esc_attr__( 'Code of Conduct', 'wpcampus-network' ) . '">
			<div class="wpc-container">
				<h2 class="container-title">' . __( 'Our Code of Conduct', 'wpcampus-network' ) . '</h2>
				<p>' . $this->get_code_of_conduct_message() . '</p>
			</div>
		</aside>';
	}

	/**
	 * Print the code of conduct in a container.
	 *
	 * @return void
	 */
	public function print_code_of_conduct_container() {
		echo $this->get_code_of_conduct_container();
	}

	public function print_posts( $args ) {
		$args = shortcode_atts(
			[
				'blog'    => 1,
				'heading' => 'Latest from our blog',
			],
			$args,
			'wpcampus_print_posts'
		);

		if ( ! empty( $args['blog'] ) && is_numeric( $args['blog'] ) ) {
			$blog_id = intval( $args['blog'] );
		} else {
			$blog_id = 1;
		}

		$blog_url = get_site_url( $blog_id );

		if ( empty( $blog_url ) ) {
			$blog_id = 1;
			$blog_url = get_site_url( $blog_id );
		}

		$response = wp_safe_remote_get( $blog_url . '/wp-json/wp/v2/posts' );

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$posts = [];
		} else {
			$posts = wp_remote_retrieve_body( $response );

			if ( ! empty( $posts ) ) {
				$posts = json_decode( $posts );
			}
		}

		if ( empty( $posts ) ) {
			$markup = '<p><em>There are no blog posts.</em></p>';
		} else {

			$markup = '';
			$post_index = 0;
			$post_max = 1;
			foreach ( $posts as $post ) {

				if ( $post_index >= $post_max ) {
					continue;
				}

				$post_markup = '<h3><a href="' . esc_url( $post->link ) . '">' . $post->title->rendered . '</a></h3>';

				$post_markup .= $post->excerpt->rendered;

				$markup .= $post_markup;

				$post_index ++;
			}
		}

		if ( ! empty( $args['heading'] ) ) {
			$markup = '<h2>' . $args['heading'] . '</h2>' . $markup;
		}

		return '<div class="wpcampus-blog-posts">' . $markup . '<a class="button center" href="' . $blog_url . '/blog">Visit the WPCampus blog</a></div>';
	}

	/**
	 * Get the date/time for the speaker app deadline.
	 *
	 * @return  DateTime
	 */
	private function get_speaker_app_deadline_dt() {
		if ( isset( $this->speaker_app_deadline_dt ) ) {
			return $this->speaker_app_deadline_dt;
		}
		$date_str = get_option( 'wpc_speaker_app_deadline_dt' );
		if ( empty( $date_str ) || false === strtotime( $date_str ) ) {
			$date_str = $this->speaker_app_deadline_default_dt;
		}
		$this->speaker_app_deadline_dt = new DateTime( $date_str );

		return $this->speaker_app_deadline_dt;
	}

	/**
	 * Print the current speaker app deadline date.
	 *
	 * @return bool
	 */
	public function print_speaker_app_deadline_date( $args ) {

		// Get the date.
		$date = $this->get_speaker_app_deadline_dt();
		if ( empty( $date ) ) {
			return '';
		}

		$args = shortcode_atts(
			[
				'format' => 'l, F j, Y',
			],
			$args,
			'wpc_speaker_app_deadline_date'
		);

		return $date->format( $args['format'] );
	}

	/**
	 * Print the current speaker app deadline time.
	 *
	 * @return bool
	 */
	public function print_speaker_app_deadline_time( $args ) {

		// Get the date.
		$date = $this->get_speaker_app_deadline_dt();
		if ( empty( $date ) ) {
			return '';
		}

		if ( '24' == $date->format( 'G' ) ) {
			return '12 midnight PDT';
		}

		$args = shortcode_atts(
			[
				'format' => 'g:i a',
			],
			$args,
			'wpc_speaker_app_deadline_time'
		);

		return $date->format( $args['format'] );
	}

	public function content_only() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<?php wp_head(); ?>
			</head>
			<body <?php body_class(); ?>>
				<?php

				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();

						the_content();

					endwhile;
				endif;

				wp_footer();

				?>
			</body>
		</html>
		<?php
	}

	public function get_comment_user_id( $comment_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( " SELECT user_id FROM {$wpdb->comments} WHERE comment_ID = %d", $comment_id ) );
	}

	public function html_redirect( $url ) {
		if ( empty( $url ) ) {
			$url = 'https://wpcampus.org/';
		}
		?>
		<!DOCTYPE html>
		<html>
			<head>
				<script type="text/javascript">
					window.top.location.href = '<?php echo $url; ?>';
				</script>
			</head>
			<body></body>
		</html>
		<?php
		exit;
	}
}
