<?php
/**
 * The class that sets up
 * global plugin functionality.
 *
 * This class is initiated on every page
 * load and does not have to be instantiated.
 *
 * @class       WPCampus_Network_Global
 * @package     WPCampus Network
 */
final class WPCampus_Network_Global {

	/**
	 * We don't need to instantiate this class.
	 */
	protected function __construct() {}

	/**
	 * Registers all of our hooks and what not.
	 */
	public static function register() {
		$plugin = new self();

		// Load our text domain.
		add_action( 'init', array( $plugin, 'textdomain' ) );

		// Change the login logo URL.
		add_filter( 'login_headerurl', array( $plugin, 'change_login_header_url' ) );

		// Add login stylesheet.
		add_action( 'login_head', array( $plugin, 'enqueue_login_styles' ) );

		// Set default user role to "member".
		add_filter( 'pre_option_default_role', array( $plugin, 'set_default_user_role' ) );

		// Hide Query Monitor if admin bar isn't showing.
		add_filter( 'qm/process', array( $plugin, 'hide_query_monitor' ), 10, 2 );

		// Mark posts as viewed.
		add_action( 'wp', array( $plugin, 'mark_viewed' ) ) ;

		// Removes default REST API functionality.
		add_action( 'rest_api_init', array( $plugin, 'init_rest_api' ) );

		// Add custom headers for the REST API.
		add_filter( 'rest_pre_serve_request', array( $plugin, 'add_rest_headers' ) );

		// Register the network footer menu.
		add_action( 'after_setup_theme', array( $plugin, 'register_network_footer_menu' ), 20 );

		// Enqueue front-end scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $plugin, 'enqueue_scripts_styles' ), 0 );
		add_action( 'wp_print_footer_scripts', array( $plugin, 'add_mailchimp_popup_script' ) );

		// Customize the arguments for the multi author post author dropdown.
		add_filter( 'my_multi_author_post_author_dropdown_args', array( $plugin, 'filter_multi_author_primary_dropdown_args' ), 10, 2 );

		// Adding titles to iframes for accessibility.
		add_filter( 'oembed_dataparse', array( $plugin, 'filter_oembed_dataparse' ), 10, 3 );

		// Make sure we can use any post type and taxonomy in Gravity Forms.
		add_filter( 'gfcpt_post_type_args', array( $plugin, 'filter_gfcpt_post_type_args' ), 10, 2 );
		add_filter( 'gfcpt_tax_args', array( $plugin, 'filter_gfcpt_tax_args' ), 10, 2 );

		// Disable cache for account pages.
		if ( preg_match( '#^/my\-account/?#', $_SERVER['REQUEST_URI'] ) ) {
			add_action( 'send_headers', array( $plugin, 'add_header_nocache' ), 15 );
		}
	}

	/**
	 * Internationalization FTW.
	 * Load our text domain.
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpcampus', false, wpcampus_network()->plugin_base . '/languages' );
	}

	/**
	 * Change the login logo URL to point
	 * to the site's home page.
	 */
	public function change_login_header_url( $login_header_url ) {
		return get_bloginfo( 'url' );
	}

	/**
	 * Add login stylesheet.
	 */
	public function enqueue_login_styles() {
		wp_enqueue_style( 'wpc-network-login', trailingslashit( wpcampus_network()->plugin_url . 'assets/build/css' ) . 'wpc-network-login.min.css', array(), null );
	}

	/**
	 * Set the default user role to "member".
	 *
	 * @param $default_role
	 * @return string
	 */
	public function set_default_user_role( $default_role ) {
		return 'member';
	}

	/**
	 * Hide Query Monitor if admin bar isn't showing.
	 */
	public function hide_query_monitor( $show_qm, $is_admin_bar_showing ) {
		return $is_admin_bar_showing;
	}

	/**
	 * If somene is logged in, mark that
	 * the user has viewed the post.
	 */
	public function mark_viewed() {
		global $wpdb;

		if ( ! is_singular() ) {
			return;
		}

		// If logged in, mark that the user has viewed the post.
		$current_user_id = get_current_user_id();
		if ( $current_user_id > 0 ) {

			$post_id  = get_the_ID();
			$meta_key = "wpc_has_viewed_{$current_user_id}";

			$has_viewed = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $post_id, $meta_key ) );
			if ( empty( $has_viewed ) ) {
				add_post_meta( $post_id, $meta_key, time(), false );
			}
		}
	}

	/**
	 * Fires when preparing to serve an API request.
	 *
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
	 * Register the network footer menu.
	 *
	 * @return  void
	 */
	function register_network_footer_menu() {
		if ( wpcampus_network()->enable_network_footer ) {
			register_nav_menu( 'footer', __( 'Footer Menu', 'wpcampus' ) );
		}
	}

	/**
	 * Enqueue our front-end scripts.
	 *
	 * @return  void
	 */
	public function enqueue_scripts_styles() {

		// Define the directories.
		$plugin_url = wpcampus_network()->plugin_url;
		$css_dir = trailingslashit( $plugin_url . 'assets/build/css' );
		$js_dir = trailingslashit( $plugin_url . 'assets/build/js' );

		// Setup the font weights we need.
		$open_sans_weights = apply_filters( 'wpcampus_open_sans_font_weights', array() );

		if ( ! is_array( $open_sans_weights ) ) {
			$open_sans_weights = array();
		} else {
			$open_sans_weights = array_filter( $open_sans_weights, 'intval' );
		}

		// Make sure the weights we need for our components are there.
		if ( wpcampus_network()->enable_network_banner ) {
			$open_sans_weights = array_merge( $open_sans_weights, array( 400, 600, 700 ) );
		}

		if ( wpcampus_network()->enable_network_notifications ) {
			$open_sans_weights = array_merge( $open_sans_weights, array( 400 ) );
		}

		if ( wpcampus_network()->enable_network_footer ) {
			$open_sans_weights = array_merge( $open_sans_weights, array( 400, 600 ) );
		}

		if ( wpcampus_network()->enable_watch_videos ) {
			$open_sans_weights = array_merge( $open_sans_weights, array( 600 ) );
		}

		// Register our fonts.
		wp_register_style( 'wpc-fonts-open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans:' . implode( ',', array_unique( $open_sans_weights ) ) );

		// Register assets needed below.
		wp_register_script( 'handlebars', $js_dir . 'handlebars.min.js', array(), null, true );
		wp_register_script( 'mustache', $js_dir . 'mustache.min.js', array(), null, true );

		// Keep this one outside logic so I can register as a dependency in scripts outside the plugin.
		wp_register_script( 'wpc-network-toggle-menu', $js_dir . 'wpc-network-toggle-menu.min.js', array( 'jquery', 'jquery-ui-core' ), null );

		// Enqueue the network banner styles.
		if ( wpcampus_network()->enable_network_banner ) {
			wp_enqueue_style( 'wpc-network-banner', $css_dir . 'wpc-network-banner.min.css', array( 'wpc-fonts-open-sans' ), null );
			wp_enqueue_script( 'wpc-network-toggle-menu' );
		}

		// Enqueue the network notification assets.
		if ( wpcampus_network()->enable_network_notifications ) {
			wp_enqueue_style( 'wpc-network-notifications', $css_dir . 'wpc-network-notifications.min.css', array( 'wpc-fonts-open-sans' ), null );
			wp_enqueue_script( 'wpc-network-notifications', $js_dir . 'wpc-network-notifications.min.js', array( 'jquery', 'mustache' ), null, true );
			wp_localize_script( 'wpc-network-notifications', 'wpc_network', array(
				'main_url' => wpcampus_network()->get_network_site_url(),
			));
		}

		// Enqueue the network footer styles.
		if ( wpcampus_network()->enable_network_footer ) {
			wp_enqueue_style( 'wpc-network-footer', $css_dir . 'wpc-network-footer.min.css', array( 'wpc-fonts-open-sans' ), null );
		}

		// Enable the watch video assets.
		if ( wpcampus_network()->enable_watch_videos ) {

			// Enqueue styles and scripts for the display.
			wp_enqueue_style( 'magnific-popup', $css_dir . 'magnific-popup.min.css' );
			wp_enqueue_script( 'magnific-popup', $js_dir . 'jquery.magnific-popup.min.js', array( 'jquery' ) );

			wp_enqueue_style( 'wpc-network-watch', $css_dir . 'wpc-network-watch.min.css', array( 'magnific-popup' ) );
			wp_enqueue_script( 'wpc-network-watch', $js_dir . 'wpc-network-watch.min.js', array( 'jquery', 'handlebars', 'magnific-popup' ) );
			wp_localize_script( 'wpc-network-watch', 'wpc_network', array(
				'main_url' => wpcampus_network()->get_network_site_url(),
			));
		}
	}

	/**
	 * Add Mailchimp popup code to footer.
	 */
	function add_mailchimp_popup_script() {

		if ( ! wpcampus_network()->enable_mailchimp_popup ) {
			return;
		}

		?>
		<script type="text/javascript" src="//downloads.mailchimp.com/js/signup-forms/popup/embed.js" data-dojo-config="usePlainJson: true, isDebug: false"></script>
		<script type="text/javascript">
		function showMailingPopUp() {
			require(["mojo/signup-forms/Loader"], function(L) { L.start({"baseUrl":"mc.us11.list-manage.com","uuid":"6d71860d429d3461309568b92","lid":"05f39a2a20"}) })
			document.cookie = "MCEvilPopupClosed=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
			document.cookie = "MCPopupClosed=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
		}
		document.querySelector('.wpc-subscribe-open').onclick = function() {showMailingPopUp()};
		</script>
		<?php
	}

	/**
	 * Customize the dropdown args for the multi author
	 * post author dropdown so we can get all members.
	 *
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
	 * Make sure we can use any post type in
	 * the Gravity Forms custom post type extension.
	 *
	 * @param   $args - array - arguments passed to get_post_types().
	 * @param   $form_id - int - the form ID.
	 * @return  array - the arguments we want to use.
	 */
	public function filter_gfcpt_post_type_args( $args, $form_id ) {
		return array();
	}

	/**
	 * Make sure we can use any taxonomy in
	 * the Gravity Forms custom post type extension.
	 *
	 * @param   $args - array - arguments passed to get_taxonomies().
	 * @param   $form_id - int - the form ID.
	 * @return  array - the arguments we want to use.
	 */
	public function filter_gfcpt_tax_args( $args, $form_id ) {
		return array(
			'_builtin' => false,
		);
	}

	/**
	 * Disables cache.
	 */
	public function add_header_nocache() {
		header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	}
}
WPCampus_Network_Global::register();
