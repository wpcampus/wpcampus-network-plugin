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
	protected function __construct() { }

	/**
	 * Registers all of our hooks and what not.
	 */
	public static function register() {
		$plugin = new self();
		$helper = wpcampus_network();

		// Load our text domain.
		add_action( 'init', array( $plugin, 'textdomain' ) );

		// Add headers to the login page.
		add_action( 'login_init', array( $plugin, 'add_header_content_security_policy' ) );

		// Add favicons.
		add_action( 'wp_head', array( $plugin, 'add_favicons' ) );
		add_action( 'admin_head', array( $plugin, 'add_favicons' ) );
		add_action( 'login_head', array( $plugin, 'add_favicons' ) );

		// Change the login logo URL.
		add_filter( 'login_headerurl', array( $plugin, 'change_login_header_url' ) );

		// Add login stylesheet.
		add_action( 'login_head', array( $plugin, 'enqueue_login_styles' ) );

		// Set default user role to "member".
		add_filter( 'pre_option_default_role', array( $plugin, 'set_default_user_role' ) );

		// Set default media sizes
		add_filter( 'pre_option_thumbnail_size_w', array( $plugin, 'set_thumbnail_size' ) );
		add_filter( 'pre_option_thumbnail_size_h', array( $plugin, 'set_thumbnail_size' ) );
		add_filter( 'pre_option_medium_size_w', array( $plugin, 'set_medium_size_w' ) );
		add_filter( 'pre_option_medium_size_h', array( $plugin, 'set_medium_size_h' ) );
		add_filter( 'pre_option_large_size_w', array( $plugin, 'set_large_size_w' ) );
		add_filter( 'pre_option_large_size_h', array( $plugin, 'set_large_size_h' ) );

		// When users are registered, make sure they're added to every site on the network.
		add_action( 'user_register', array( $plugin, 'process_user_registration' ) );

		// Filter user capabilities.
		//add_filter( 'user_has_cap', array( $plugin, 'filter_user_has_cap' ), 100, 4 );

		// Hide Query Monitor if admin bar isn't showing.
		add_filter( 'qm/process', array( $plugin, 'hide_query_monitor' ), 10, 2 );

		// Mark posts as viewed.
		add_action( 'wp', array( $plugin, 'mark_viewed' ) );

		// Removes default REST API functionality.
		add_action( 'rest_api_init', array( $plugin, 'init_rest_api' ) );

		// Add custom headers for the REST API.
		add_filter( 'rest_pre_serve_request', array( $plugin, 'add_rest_headers' ) );

		// Register the network footer menu.
		add_action( 'after_setup_theme', array( $plugin, 'register_network_footer_menu' ), 20 );

		// Enqueue front-end scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $plugin, 'enqueue_scripts_styles' ), 0 );
		//add_action( 'wp_print_footer_scripts', array( $plugin, 'add_mailchimp_popup_script' ) );

		// Customize the arguments for the multi author post author dropdown.
		add_filter( 'my_multi_author_post_author_dropdown_args', array( $plugin, 'filter_multi_author_primary_dropdown_args' ), 10, 2 );

		// Adding titles to iframes for accessibility.
		add_filter( 'oembed_dataparse', array( $plugin, 'filter_oembed_dataparse' ), 10, 3 );

		// Make sure we can use any post type and taxonomy in Gravity Forms.
		add_filter( 'gfcpt_post_type_args', array( $plugin, 'filter_gfcpt_post_type_args' ), 10, 2 );
		add_filter( 'gfcpt_tax_args', array( $plugin, 'filter_gfcpt_tax_args' ), 10, 2 );

		// Tweak FooGallery CPT args.
		add_filter( 'foogallery_gallery_posttype_register_args', array( $plugin, 'filter_foogallery_cpt_args' ) );

		// Add content to top of login forms.
		add_filter( 'login_form_top', array( $plugin, 'add_to_login_form_top' ), 1, 2 );

		add_shortcode( 'wpc_speaker_app_deadline_time', array( $helper, 'print_speaker_app_deadline_time' ) );
		add_shortcode( 'wpc_speaker_app_deadline_date', array( $helper, 'print_speaker_app_deadline_date' ) );

		add_shortcode( 'wpc_print_code_of_conduct', array( $helper, 'get_code_of_conduct' ) );
		add_shortcode( 'wpc_print_content', array( $plugin, 'get_content_for_shortcode' ) );

		// Enable users to login via AJAX.
		add_action( 'wp_ajax_wpc_ajax_login', array( $plugin, 'process_ajax_login' ) );
		add_action( 'wp_ajax_nopriv_wpc_ajax_login', array( $plugin, 'process_ajax_login' ) );
		add_action( 'wp_ajax_wpc_ajax_logout', array( $plugin, 'process_ajax_logout' ) );
		add_action( 'wp_ajax_nopriv_wpc_ajax_logout', array( $plugin, 'process_ajax_logout' ) );

		// Get sessions data.
		add_action( 'wp_ajax_wpcampus_get_sessions', array( $plugin, 'process_ajax_get_sessions' ) );
		add_action( 'wp_ajax_nopriv_wpcampus_get_sessions', array( $plugin, 'process_ajax_get_sessions' ) );

		// Print our Javascript templates when needed.
		add_action( 'wp_footer', array( $plugin, 'print_js_templates' ) );

		// Disable cache for account pages.
		if ( preg_match( '#^/my\-account/?#', $_SERVER['REQUEST_URI'] ) ) {
			add_action( 'send_headers', array( $plugin, 'add_header_nocache' ), 15 );
		}

		// Don't cache specific pages.
		$exclude_pages = array(
			'wpcampus.org'      => array(
				'#^/donation-confirmation/?#',
				'#^/donation-history/?#',
			),
			'shop.wpcampus.org' => array(
				'#^/my\-account/?#',
			),
		);

		// Loop through the patterns.
		if ( array_key_exists( $_SERVER['HTTP_HOST'], $exclude_pages ) ) {
			foreach ( $exclude_pages[ $_SERVER['HTTP_HOST'] ] as $page ) {
				if ( preg_match( $page, $_SERVER['REQUEST_URI'] ) ) {
					add_action( 'send_headers', array( $plugin, 'add_header_nocache' ), 15 );
				}
			}
		}
	}

	/**
	 * Internationalization FTW.
	 * Load our text domain.
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpcampus-network', false, wpcampus_network()->get_plugin_basename() . '/languages' );
	}

	/**
	 * Adds a content security policy that allows iframes on our other sites.
	 */
	public function add_header_content_security_policy() {
		@header( "Content-Security-Policy: frame-ancestors 'self' wpcampus.org *.wpcampus.org;" );
	}

	/**
	 * Processes the [wpc_print_content] shortcode.
	 */
	public function get_content_for_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'wpc_print_content'
		);

		if ( empty( $atts['id'] ) ) {
			return null;
		}

		$post_id = (int) $atts['id'];

		if ( empty( $post_id ) ) {
			return null;
		}

		$post = get_post( $post_id );

		if ( empty( $post->post_content ) ) {
			return null;
		}

		return $post->post_content;
	}

	/**
	 * Add favicons.
	 */
	public function add_favicons() {

		$favicons_folder = trailingslashit( wpcampus_network()->get_plugin_url() ) . 'assets/images/favicons/';

		?>
		<link rel="shortcut icon" href="<?php echo $favicons_folder; ?>wpcampus-favicon-60.png"/>
		<?php

		// Set the Apple image sizes.
		$apple_image_sizes = array( 57, 60, 72, 76, 114, 120, 144, 152, 180 );
		foreach ( $apple_image_sizes as $size ) :
			?>
			<link rel="apple-touch-icon" sizes="<?php echo "{$size}x{$size}"; ?>" href="<?php echo $favicons_folder; ?>wpcampus-favicon-<?php echo $size; ?>.png">
			<?php
		endforeach;

		// Set the Android image sizes.
		$android_image_sizes = array( 16, 32, 96, 192 );
		foreach ( $android_image_sizes as $size ) :

			?>
			<link rel="icon" type="image/png" sizes="<?php echo "{$size}x{$size}"; ?>" href="<?php echo $favicons_folder; ?>wpcampus-favicon-<?php echo $size; ?>.png">
			<?php

		endforeach;

		?>
		<meta name="msapplication-TileColor" content="#ffffff">
		<meta name="msapplication-TileImage" content="<?php echo $favicons_folder; ?>wpcampus-favicon-144x144.png">
		<meta name="theme-color" content="#ffffff">
		<?php
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
		wp_enqueue_style( 'wpc-network-login', trailingslashit( wpcampus_network()->get_plugin_url() ) . 'assets/css/wpc-network-login.min.css', array(), null );
	}

	/**
	 * Process when a user registers.
	 *
	 * We make sure they are added to every
	 * site on the network.
	 */
	public function process_user_registration( $user_id ) {

		// Assign to every blog on the network.
		wpcampus_network()->assign_user_to_all_blogs( $user_id );

	}

	/**
	 * Set the default user role to "member".
	 *
	 * @param $default_role
	 *
	 * @return string
	 */
	public function set_default_user_role( $default_role ) {
		return 'member';
	}

	/**
	 * Sets the default thumbnail size.
	 *
	 * @param mixed - $default The default value to return if the option does not exist in the database.
	 *
	 * @return int - the media size
	 */
	public function set_thumbnail_size( $default ) {
		return 300;
	}

	/**
	 * Sets the default medium size.
	 *
	 * @param mixed - $default The default value to return if the option does not exist in the database.
	 *
	 * @return int - the media size
	 */
	public function set_medium_size_w( $default ) {
		return 800;
	}

	public function set_medium_size_h( $default ) {
		return 1200;
	}

	/**
	 * Sets the default thumbnail size.
	 *
	 * @param mixed - $default The default value to return if the option does not exist in the database.
	 *
	 * @return int - the media size
	 */
	public function set_large_size_w( $default ) {
		return 1200;
	}

	public function set_large_size_h( $default ) {
		return 2000;
	}

	/**
	 * Filter user capabilities.
	 *
	 * @param   array - $allcaps - An array of all the user's capabilities.
	 * @param   array - $caps - Actual capabilities for meta capability.
	 * @param   array - $args - Optional parameters passed to has_cap(), typically object ID.
	 * @param   WP_User - $user - The user object.
	 *
	 * @return  array - the filtered capabilities.
	 */
	public function filter_user_has_cap( $allcaps, $caps, $args, $user ) {

		if ( ! is_array( $args ) ) {
			return $allcaps;
		}

		$capability = array_shift( $args );
		if ( 'edit_comment' != $capability ) {
			return $allcaps;
		}

		$user_id = array_shift( $args );
		if ( empty( $user_id ) ) {
			return $allcaps;
		}

		$comment_id = array_shift( $args );
		if ( empty( $comment_id ) ) {
			return $allcaps;
		}

		// If the user can moderate comments, get out of here.

		//$allcaps['edit_comment'] = false;
		//return array();
		unset( $allcaps['edit_post'] );
		unset( $allcaps['edit_posts'] );
		unset( $allcaps['edit_proposal'] );
		unset( $allcaps['edit_proposals'] );
		unset( $allcaps['edit_comment'] );
		unset( $allcaps['moderate_comments'] );

		/*echo "\n\ncapability: {$capability}";
		echo "\n\nallcaps:<pre>";
		print_r($allcaps);
		echo "</pre>";
		echo "\n\ncaps:<pre>";
		print_r($caps);
		echo "</pre>";
		echo "\n\nuser ID: [{$user->ID}][{$user_id}]";*/

		return $allcaps;
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
		$current_user_id = (int) get_current_user_id();
		if ( $current_user_id > 0 ) {

			$post_id  = get_the_ID();
			$meta_key = "wpc_has_viewed_{$current_user_id}";

			$has_viewed = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s",
					$post_id,
			        $meta_key
				)
			);

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
	 *
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
		$this->add_header_nocache();

		return $value;
	}

	/**
	 * Register the network footer menu.
	 *
	 * @return  void
	 */
	function register_network_footer_menu() {
		if ( wpcampus_network()->is_enabled( 'footer' ) ) {
			register_nav_menu( 'footer', __( 'Footer Menu', 'wpcampus-network' ) );
		}
	}

	/**
	 * Enqueue our front-end scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts_styles() {

		// Define the directories.
		$plugin_url = trailingslashit( wpcampus_network()->get_plugin_url() );
		$css_dir    = $plugin_url . 'assets/css/';
		$js_dir     = $plugin_url . 'assets/js/';

		// Setup the font weights we need.
		$open_sans_weights = apply_filters( 'wpcampus_open_sans_font_weights', array() );

		if ( ! is_array( $open_sans_weights ) ) {
			$open_sans_weights = array();
		} else {
			$open_sans_weights = array_filter( $open_sans_weights, 'intval' );
		}

		// Make sure the weights we need for our components are there.
		if ( wpcampus_network()->is_enabled( 'banner' ) ) {
			$open_sans_weights = array_merge( $open_sans_weights, array( 400, 600, 700 ) );
		}

		if ( wpcampus_network()->is_enabled( 'notifications' ) ) {
			$open_sans_weights = array_merge( $open_sans_weights, array( 400 ) );
		}

		if ( wpcampus_network()->is_enabled( 'footer' ) ) {
			$open_sans_weights = array_merge( $open_sans_weights, array( 400, 600 ) );
		}

		if ( wpcampus_network()->is_enabled( 'videos' ) ) {
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
		if ( wpcampus_network()->is_enabled( 'banner' ) ) {
			wp_enqueue_style( 'wpc-network-banner', $css_dir . 'wpc-network-banner.min.css', array( 'wpc-fonts-open-sans' ), null );
			wp_enqueue_script( 'wpc-network-toggle-menu' );
		}

		// Enqueue the network notification assets.
		if ( wpcampus_network()->is_enabled( 'notifications' ) ) {
			wp_enqueue_style( 'wpc-network-notifications', $css_dir . 'wpc-network-notifications.min.css', array( 'wpc-fonts-open-sans' ), null );
			wp_enqueue_script( 'wpc-network-notifications', $js_dir . 'wpc-network-notifications.min.js', array( 'jquery', 'mustache' ), null, true );
			wp_localize_script( 'wpc-network-notifications', 'wpc_net_notifications', array(
				'main_url' => wpcampus_get_network_site_url(),
			));
		}

		// Enqueue the network Code of Conduct styles.
		if ( wpcampus_network()->is_enabled( 'coc' ) ) {
			wp_enqueue_style( 'wpc-network-coc', $css_dir . 'wpc-network-coc.min.css', array( 'wpc-fonts-open-sans' ), null );
		}

		// Enqueue the network footer styles.
		if ( wpcampus_network()->is_enabled( 'footer' ) ) {
			wp_enqueue_style( 'wpc-network-footer', $css_dir . 'wpc-network-footer.min.css', array( 'wpc-fonts-open-sans' ), null );
		}

		// Enqueue the sessions assets.
		if ( wpcampus_network()->is_enabled( 'sessions' ) ) {

			// Get this site's timezone and offset.
			$timezone = new DateTimeZone( get_option( 'timezone_string' ) ?: 'UTC' );
			$current_time_offset = $timezone->getOffset( new DateTime() );

			// Get the difference in hours.
			$timezone_offset_hours = ( $current_time_offset / 60 ) / 60;

			$sessions_ver = '1.1';

			wp_register_style( 'wpc-network-sessions-icons', $css_dir . 'conf-schedule-icons.min.css', array(), $sessions_ver );

			wp_enqueue_style( 'wpc-network-sessions', $css_dir . 'wpc-network-sessions.min.css', array( 'wpc-network-sessions-icons' ), $sessions_ver );
			wp_enqueue_script( 'wpc-network-sessions', $js_dir . 'wpc-network-sessions.min.js', array( 'jquery', 'handlebars' ), $sessions_ver, true );
			wp_localize_script( 'wpc-network-sessions', 'wpc_sessions', array(
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'load_error_msg' => '<p>' . __( 'Oops. Looks like something went wrong. Please refresh the page and try again.', 'wpcampus-network' ) . '</p><p>' . sprintf( __( 'If the problem persists, please %1$slet us know%2$s.', 'wpcampus' ), '<a href="/contact/">', '</a>' ) . '</p>',
				'tz_offset'      => $timezone_offset_hours,
			));
		}

		// Enable the watch video assets.
		if ( wpcampus_network()->is_enabled( 'videos' ) ) {

			// Enqueue styles and scripts for the display.
			wp_enqueue_style( 'magnific-popup', $css_dir . 'magnific-popup.min.css' );
			wp_enqueue_script( 'magnific-popup', $js_dir . 'jquery.magnific-popup.min.js', array( 'jquery' ) );

			wp_enqueue_style( 'wpc-network-watch', $css_dir . 'wpc-network-watch.min.css', array( 'magnific-popup' ) );
			wp_enqueue_script( 'wpc-network-watch', $js_dir . 'wpc-network-watch.min.js', array( 'jquery', 'handlebars', 'magnific-popup' ) );
			wp_localize_script( 'wpc-network-watch', 'wpc_net_watch', array(
				'main_url'  => wpcampus_get_network_site_url(),
				'no_videos' => __( 'There are no videos available.', 'wpcampus-network' ),
			));
		}

		wpcampus_network()->enqueue_base_script();

		//wpcampus_network()->enqueue_login_script();

	}

	/**
	 * Add Mailchimp popup code to footer.
	 */
	function add_mailchimp_popup_script() {

		if ( ! wpcampus_network()->is_enabled( 'mailchimp_popup' ) ) {
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
	 *
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
	 *
	 * @return  string - the HTML.
	 */
	public function filter_oembed_dataparse( $return, $data, $url ) {

		// Get title from embed data to start.
		$title = ! empty( $data->title ) ? $data->title : '';

		// If no embed title, search the return markup for a title attribute.
		$preg_match     = '/title\=[\"|\\\']{1}([^\"\\\']*)[\"|\\\']{1}/i';
		$has_title_attr = preg_match( $preg_match, $return, $matches );
		if ( $has_title_attr && ! empty( $matches[1] ) ) {
			$title = $matches[1];
		}

		// Add embed type as title prefix.
		if ( $title && ! empty( $data->type ) ) {
			switch ( $data->type ) {

				// Capitalize first word.
				case 'video':
					$title = sprintf( __( '%s:', 'wpcampus-network' ), ucfirst( $data->type ) ) . ' ' . $title;
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
	 *
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
	 *
	 * @return  array - the arguments we want to use.
	 */
	public function filter_gfcpt_tax_args( $args, $form_id ) {
		return array(
			'_builtin' => false,
		);
	}

	/**
	 * Filter the arguments for the FooGallery galleries post type.
	 *
	 * @param   $args - array - the original post type arguments.
	 *
	 * @return  array - the filtered arguments.
	 */
	public function filter_foogallery_cpt_args( $args ) {
		$args['capability_type'] = array( 'gallery', 'galleries' );

		return $args;
	}

	/**
	 * Disables cache.
	 */
	public function add_header_nocache() {
		header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	}

	/**
	 * Add content to top of login forms.
	 *
	 * @param   $content - string - the default content, which is blank.
	 * @param   $args - array - the login form arguments.
	 *
	 * @return  string - the returned content.
	 */
	public function add_to_login_form_top( $content, $args ) {
		global $post;

		$header         = '';
		$default_header = 'h2';

		$title = '';
		if ( false !== $args['wpc_form_title'] ) {

			if ( is_singular() && ! empty( $post->ID ) ) {
				$header = get_post_meta( $post->ID, 'wpcampus_login_form_header', true );
				$title  = get_post_meta( $post->ID, 'wpcampus_login_form_title', true );

				if ( ! empty( $title ) ) {
					$title = strip_tags( $title, '<em><strong>' );
				}
			}

			if ( empty( $title ) ) {
				$title = sprintf( __( 'Login to %s', 'wpcampus-network' ), 'WPCampus' );
			}

			if ( ! empty( $header ) ) {
				$title = "<{$header}>" . $title . "</{$header}>";
			} else {
				$title = "<{$default_header}>" . $title . "</{$default_header}>";
			}
		}

		$message = '';
		if ( false !== $args['wpc_form_message'] ) {

			if ( is_singular() && ! empty( $post->ID ) ) {
				$message = get_post_meta( $post->ID, 'wpcampus_login_form_message', true );
			}

			// Add our login message.
			$message .= '<p>Don\'t have a WPCampus user account? No problem. <a class="button inline royal-blue" href="https://wpcampus.org/get-involved/">Create an account</a></p>';

		}

		if ( true === $args['wpc_ajax'] ) {
			wp_nonce_field( 'wpc_ajax_login', 'wpc_ajax_login_nonce' );
		}

		return $title . $message;
	}

	/**
	 *
	 */
	public function process_ajax_login() {

		check_ajax_referer( 'wpc_ajax_login', 'wpc_ajax_login_nonce' );

		$info = array(
			'user_login'    => $_POST['log'],
			'user_password' => $_POST['pwd'],
			'remember'      => $_POST['rememberme'],
		);

		$user_signon = wp_signon( $info, false );

		if ( is_wp_error( $user_signon ) ) {
			echo json_encode(
				array(
					'loggedin' => false,
				    'message'  => $user_signon->get_error_message(),
				)
			);
		} else {
			echo json_encode(
				array(
					'loggedin' => true,
					'message'  => __( 'Login successful, redirecting...' ),
				)
			);
		}

		wp_die();
	}

	/**
	 *
	 */
	public function process_ajax_logout() {

		check_ajax_referer( 'wpc_ajax_logout', 'wpc_ajax_logout_nonce' );

		//wp_logout();

		$form_id = 9; //isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;

		gravity_form( $form_id, true, false, false, false, true );

		wp_die();

	}

	/**
	 *
	 */
	public function process_ajax_get_sessions() {

		$sessions = array();

		$http_wpc_access = get_option( 'http_wpc_access' );
		if ( ! empty( $http_wpc_access ) ) {

			$url_args = [];

			$filters = array(
				'orderby'  => array( 'date', 'title' ),
				'order'    => array( 'asc', 'desc' ),
				'event'    => array(
					'wpcampus-2018',
					'wpcampus-2017',
					'wpcampus-2016',
					'wpcampus-online-2019',
					'wpcampus-online-2018',
					'wpcampus-online-2017'
				),
				'search'   => array(),
				'subjects' => array(),
			);

			if ( ! empty( $_GET['filters'] ) ) {
				foreach ( $filters as $filter => $options ) {
					if ( ! empty( $_GET['filters'][ $filter ] ) ) {
						$filter_val = strtolower( $_GET['filters'][ $filter ] );

						// @TODO optimize?
						if ( in_array( $filter, array( 'search', 'subjects' ) ) ) {
							$url_args[ $filter ] = sanitize_text_field( $filter_val );
						} else if ( in_array( $filter_val, $options ) ) {
							$url_args[ $filter ] = $filter_val;
						}
					}
				}
			}

			// Build query URL.
			$url = get_bloginfo( 'url' ) . '/wp-json/wpcampus/data/sessions/';

			if ( ! empty( $url_args ) ) {
				$url = add_query_arg( $url_args, $url );
			}

			// Get our profiles.
			$response = wp_safe_remote_get( $url, array(
				'timeout' => 10,
				'headers' => array(
					'WPC-Access' => $http_wpc_access,
				),
			) );

			if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
				$sessions = json_decode( wp_remote_retrieve_body( $response ) );
			}
		}

		echo json_encode(
			array(
				'count'    => ! empty( $sessions ) ? count( $sessions ) : 0,
				'sessions' => $sessions,
			)
		);

		wp_die();

	}

	/**
	 * Add JS templates to the footer when needed.
	 */
	public function print_js_templates() {

		// Add the sessions template.
		if ( wpcampus_network()->is_enabled( 'sessions' ) ) :

			$events = array(
				'wpcampus-2018'        => 'WPCampus 2018',
				'wpcampus-2017'        => 'WPCampus 2017',
				'wpcampus-2016'        => 'WPCampus 2016',
				'wpcampus-online-2019' => 'WPCampus Online 2019',
				'wpcampus-online-2018' => 'WPCampus Online 2018',
				'wpcampus-online-2017' => 'WPCampus Online 2017',
			);

			$subjects = function_exists( 'wpcampus_get_sessions_subjects' ) ? wpcampus_get_sessions_subjects() : array();

			$plugin_url = wpcampus_network()->get_plugin_url();
			$images_dir = trailingslashit( $plugin_url ) . 'assets/images/';

			/*
			 * TODO:
			 * - Add tracking for filters.
			 * - Make filters bar fixed when scrolling.
			 */

			/*{{^session_video_url}}<br><span class="session-video">NO VIDEO</span>{{/session_video_url}}*/

			?>
			<script id="wpc-sessions-filters-template" type="text/x-handlebars-template">
				<form class="wpcampus-sessions-filters-form" aria-label="Filter the sessions by subject, event and keyword">
					<div class="wpcampus-sessions-filter-field wpcampus-sessions-filter-field--subjects">
						<label for="wpc-session-filter-subjects" aria-label="Filter sessions by subject">Subjects</label>
						<select id="wpc-session-filter-subjects" class="wpcampus-sessions-filter wpcampus-sessions-filter--subjects" name="subjects" aria-controls="wpcampus-sessions">
							<option value=""><?php _e( 'All subjects', 'wpcampus-network' ); ?></option>
							<?php

							foreach ( $subjects as $subject ) :
								?>
								<option value="<?php echo $subject->slug; ?>"{{{selected "<?php echo $subject->slug; ?>" subjects}}}><?php echo $subject->name; ?></option>
								<?php
							endforeach;

							?>
						</select>
					</div>
					<div class="wpcampus-sessions-filter-field wpcampus-sessions-filter-field--event">
						<label for="wpc-session-filter-event" aria-label="Filter sessions by event">Events</label>
						<select id="wpc-session-filter-event" class="wpcampus-sessions-filter wpcampus-sessions-filter--event" name="event" aria-controls="wpcampus-sessions">
							<option value=""><?php _e( 'All events', 'wpcampus-network' ); ?></option>
							<?php

							foreach ( $events as $slug => $event ) :
								?>
								<option value="<?php echo $slug; ?>"{{{selected "<?php echo $slug; ?>" event}}}><?php echo $event; ?></option>
								<?php
							endforeach;

							?>
						</select>
					</div>
					<div class="wpcampus-sessions-filter-field wpcampus-sessions-filter-field--orderby">
						<label for="wpc-session-filter-orderby" aria-label="Order sessions by date or title">Order by</label>
						<select id="wpc-session-filter-orderby" class="wpcampus-sessions-filter wpcampus-sessions-filter--orderby" name="orderby" aria-controls="wpcampus-sessions">
							<option value="date,asc"{{{selected_orderby "date" "asc"}}}><?php _e( 'Date, ascending', 'wpcampus-network' ); ?></option>
							<option value="date,desc"{{{selected_orderby "date" "desc"}}}><?php _e( 'Date, descending', 'wpcampus-network' ); ?></option>
							<option value="title,asc"{{{selected_orderby "title" "asc"}}}><?php _e( 'Title, ascending', 'wpcampus-network' ); ?></option>
							<option value="title,desc"{{{selected_orderby "title" "desc"}}}><?php _e( 'Title, descending', 'wpcampus-network' ); ?></option>
						</select>
					</div>
					<div class="wpcampus-sessions-filter-field wpcampus-sessions-filter-field--search">
						<label for="wpc-session-filter-search" aria-label="Search items by keyword">Search</label>
						<input id="wpc-session-filter-search" class="wpcampus-sessions-filter wpcampus-sessions-filter--search wpcampus-sessions-filter--text" type="search" name="search" placeholder="Search items" value="{{search}}" aria-controls="wpcampus-sessions" />
					</div>
					<input id="wpc-session-filter-submit" type="submit" class="wpcampus-sessions-update" value="<?php esc_attr_e( 'Update items', 'wpcampus-network' ); ?>" aria-controls="wpcampus-sessions" />
				</form>
			</script>
			<script id="wpc-sessions-template" type="text/x-handlebars-template">
				<div class="wpcampus-sessions-count" aria-live="polite"></div>
				<div class="wpcampus-sessions-list">
					{{#each .}}
						<div class="wpcampus-session session-{{event_slug}} {{format_slug}}" data-ID="{{ID}}">
							<div class="session-graphic">
								<div class="event-thumbnail"></div>
							</div>
							<div class="session-info-wrapper {{sessionInfoWrapperClasses}}">
								<div class="session-info">
									<h2 class="session-title">{{#if permalink}}<a href="{{permalink}}">{{title}}</a>{{else}}{{title}}{{/if}}</h2>
									<ul class="session-metas">
										<li class="session-meta session-date">{{session_date}}</li>
										<li class="session-meta session-event">{{event_name}}</li>
										<li class="session-meta session-format">{{format_name}}</li>
									</ul>
									{{#if subjects}}
										<ul class="session-subjects">
											{{#each subjects}}<li class="session-subject {{slug}}">{{name}}</li>{{/each}}
										</ul>
									{{/if}}
									<p>{{{excerpt.raw}}}</p>
								</div>
								{{sessionAssets}}
								<div class="event-name" data-event="{{event}}" aria-hidden="true"><span>{{session_event_name}}</span></div>
								{{#if speakers}}
									<ul class="session-speakers">
										{{#each speakers}}
										<li class="session-speaker">
											<a href="{{permalink}}" aria-label="More from the speaker, {{display_name}}">
												{{#if avatar}}
													<img class="session-speaker__avatar" src="{{avatar}}" alt="Avatar for {{display_name}}">
												{{else}}
													<img class="session-speaker__avatar" src="<?php echo $images_dir; ?>avatar-default.png" alt="Avatar for {{display_name}}">
												{{/if}}
												<span class="session-speaker__name">{{display_name}}</span>
											</a>
										</li>
										{{/each}}
									</ul>
								{{/if}}
							</div>
						</div>
					{{/each}}
				</div>
			</script>
			<?php
		endif;

	}
}

WPCampus_Network_Global::register();
