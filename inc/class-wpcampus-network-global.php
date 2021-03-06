<?php

/**
 * The class that sets up
 * global plugin functionality.
 * This class is initiated on every page
 * load and does not have to be instantiated.
 *
 * @class       WPCampus_Network_Global
 * @package     WPCampus Network
 */
final class WPCampus_Network_Global {

	/**
	 * Whether or not debug is enabled.
	 *
	 * @var bool
	 */
	private $debug = false;

	/**
	 * Will hold the main "helper" class.
	 *
	 * @var WPCampus_Network
	 */
	private $helper;

	/**
	 * We don't need to instantiate this class.
	 */
	protected function __construct() { }

	/**
	 * Registers all of our hooks and what not.
	 */
	public static function register() {
		$plugin = new self();

		$plugin->helper = wpcampus_network();

		if ( ( defined( 'WPCAMPUS_DEV' ) && WPCAMPUS_DEV )
		     || ( ! empty( $_ENV['PANTHEON_ENVIRONMENT'] ) && 'dev' == $_ENV['PANTHEON_ENVIRONMENT'] ) ) {
			$plugin->debug = true;
		}

		// Load our text domain.
		add_action( 'init', [ $plugin, 'textdomain' ] );

		add_filter( 'headless_mode_disable_front_end', [ $plugin, 'headless_mode_disable_front_end' ] );

		// Add headers to the login page.
		add_action( 'login_init', [ $plugin, 'add_header_content_security_policy' ] );

		// Add favicons.
		add_action( 'wp_head', [ $plugin, 'add_favicons' ] );
		add_action( 'admin_head', [ $plugin, 'add_favicons' ] );
		add_action( 'login_head', [ $plugin, 'add_favicons' ] );

		// Change the login logo URL.
		add_filter( 'login_headerurl', [ $plugin, 'change_login_header_url' ] );

		// Add login stylesheet.
		add_action( 'login_head', [ $plugin, 'enqueue_login_styles' ] );

		add_action( 'login_footer', [ $plugin, 'add_to_login_footer' ] );

		// Set default user role to "member".
		add_filter( 'pre_option_default_role', [ $plugin, 'set_default_user_role' ] );

		// Set default media sizes
		add_filter( 'pre_option_thumbnail_size_w', [ $plugin, 'set_thumbnail_size' ] );
		add_filter( 'pre_option_thumbnail_size_h', [ $plugin, 'set_thumbnail_size' ] );
		add_filter( 'pre_option_medium_size_w', [ $plugin, 'set_medium_size_w' ] );
		add_filter( 'pre_option_medium_size_h', [ $plugin, 'set_medium_size_h' ] );
		add_filter( 'pre_option_large_size_w', [ $plugin, 'set_large_size_w' ] );
		add_filter( 'pre_option_large_size_h', [ $plugin, 'set_large_size_h' ] );

		add_filter( 'upload_mimes', [ $plugin, 'filter_upload_mimes' ], 20, 2 );

		// When users are registered, make sure they're added to every site on the network.
		add_action( 'user_register', [ $plugin, 'process_user_registration' ] );

		// Filter user capabilities.
		add_filter( 'user_has_cap', [ $plugin, 'filter_user_has_cap' ], 100, 4 );

		// Hide Query Monitor if admin bar isn't showing.
		add_filter( 'qm/process', [ $plugin, 'hide_query_monitor' ], 10, 2 );

		// Mark posts as viewed.
		add_action( 'wp', [ $plugin, 'mark_viewed' ] );

		// Manage the REST API.
		add_action( 'rest_api_init', [ $plugin, 'register_rest_routes' ] );
		add_action( 'rest_api_init', [ $plugin, 'register_rest_fields' ] );
		add_filter( 'rest_user_query', [ $plugin, 'filter_rest_user_query' ], 10, 2 );
		add_filter( 'rest_prepare_post', [ $plugin, 'filter_rest_prepare_post' ], 20, 3 );
		add_filter( 'rest_prepare_page', [ $plugin, 'filter_rest_prepare_page' ], 10, 3 );

		// Register the network footer menu.
		add_action( 'after_setup_theme', [ $plugin, 'register_network_footer_menu' ], 20 );

		// Enqueue front-end scripts and styles.
		add_action( 'wp_enqueue_scripts', [ $plugin, 'enqueue_scripts_styles' ], 0 );
		//add_action( 'wp_print_footer_scripts', array( $plugin, 'add_mailchimp_popup_script' ) );

		// Customize the arguments for the multi author post author dropdown.
		add_filter( 'my_multi_author_post_author_dropdown_args', [ $plugin, 'filter_multi_author_primary_dropdown_args' ], 10, 2 );

		// Adding titles to iframes for accessibility.
		add_filter( 'oembed_dataparse', [ $plugin, 'filter_oembed_dataparse' ], 10, 3 );

		// Make sure we can use any post type and taxonomy in Gravity Forms.
		add_filter( 'gfcpt_post_type_args', [ $plugin, 'filter_gfcpt_post_type_args' ], 10, 2 );
		add_filter( 'gfcpt_tax_args', [ $plugin, 'filter_gfcpt_tax_args' ], 10, 2 );

		// Tweak FooGallery CPT args.
		add_filter( 'foogallery_gallery_posttype_register_args', [ $plugin, 'filter_foogallery_cpt_args' ] );

		// Add content to login forms.
		add_filter( 'login_form_top', [ $plugin, 'add_to_login_form_top' ], 1, 2 );
		add_filter( 'login_form_bottom', [ $plugin, 'add_to_login_form_bottom' ], 1, 2 );

		add_shortcode( 'wpc_speaker_app_deadline_time', [ $plugin->helper, 'print_speaker_app_deadline_time' ] );
		add_shortcode( 'wpc_speaker_app_deadline_date', [ $plugin->helper, 'print_speaker_app_deadline_date' ] );

		add_shortcode( 'wpc_print_code_of_conduct', [ $plugin->helper, 'get_code_of_conduct' ] );
		add_shortcode( 'wpc_print_content', [ $plugin, 'get_content_for_shortcode' ] );

		add_shortcode( 'wpcampus_print_posts', [ $plugin->helper, 'print_posts' ] );

		// Enable users to login via AJAX.
		add_action( 'wp_ajax_wpc_ajax_login', [ $plugin, 'process_ajax_login' ] );
		add_action( 'wp_ajax_nopriv_wpc_ajax_login', [ $plugin, 'process_ajax_login' ] );
		add_action( 'wp_ajax_wpc_ajax_logout', [ $plugin, 'process_ajax_logout' ] );
		add_action( 'wp_ajax_nopriv_wpc_ajax_logout', [ $plugin, 'process_ajax_logout' ] );

		// Get sessions data.
		add_action( 'wp_ajax_wpcampus_get_sessions', [ $plugin, 'process_ajax_get_sessions_public' ] );
		add_action( 'wp_ajax_nopriv_wpcampus_get_sessions', [ $plugin, 'process_ajax_get_sessions_public' ] );

		add_filter( 'get_comment_author_link', [ $plugin, 'filter_comment_author_link' ], 100, 3 );

		// Print our Javascript templates when needed.
		add_action( 'wp_footer', [ $plugin, 'print_js_templates' ] );

		// Filter search results.
		add_filter( 'posts_clauses', [ $plugin, 'filter_search_results_query' ], 10, 2 );

		// Stupid Gravity Slider Fields notices.
		add_filter( 'gsf_show_notices', '__return_false' );

		// Disable cache for account pages.
		if ( preg_match( '#^/my\-account/?#', $_SERVER['REQUEST_URI'] ) ) {
			add_action( 'send_headers', 'wpcampus_add_header_nocache', 15 );
		}

		// Don't cache specific pages.
		$exclude_pages = [
			'wpcampus.org'      => [
				'#^/donation-confirmation/?#',
				'#^/donation-history/?#',
			],
			'shop.wpcampus.org' => [
				'#^/my\-account/?#',
			],
		];

		// Loop through the patterns.
		if ( array_key_exists( $_SERVER['HTTP_HOST'], $exclude_pages ) ) {
			foreach ( $exclude_pages[ $_SERVER['HTTP_HOST'] ] as $page ) {
				if ( preg_match( $page, $_SERVER['REQUEST_URI'] ) ) {
					add_action( 'send_headers', 'wpcampus_add_header_nocache', 15 );
				}
			}
		}
	}

	/**
	 * Internationalization FTW.
	 * Load our text domain.
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpcampus-network', false, $this->helper->get_plugin_basename() . '/languages' );
	}

	/**
	 * Return true if the front end should be disabled.
	 *
	 * @param $disable - default disabled status.
	 *
	 * @return bool
	 */
	public function headless_mode_disable_front_end( $disable ) {

		/*
		 * @TODO set back to "manage_options" after speaker app is done
		 */
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {

			if ( in_array( $_SERVER['REQUEST_URI'], [ '/speaker-application/', '/registration/' ] ) ) {
				return false;
			}
		}

		return ! current_user_can( 'manage_options' );
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
			[
				'id' => 0,
			],
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

		$favicons_folder = trailingslashit( $this->helper->get_plugin_url() ) . 'assets/images/favicons/';

		?>
		<link rel="shortcut icon" href="<?php echo $favicons_folder; ?>wpcampus-favicon-60.png"/>
		<?php

		// Set the Apple image sizes.
		$apple_image_sizes = [ 57, 60, 72, 76, 114, 120, 144, 152, 180 ];
		foreach ( $apple_image_sizes as $size ) :
			?>
			<link rel="apple-touch-icon" sizes="<?php echo "{$size}x{$size}"; ?>" href="<?php echo $favicons_folder; ?>wpcampus-favicon-<?php echo $size; ?>.png">
		<?php
		endforeach;

		// Set the Android image sizes.
		$android_image_sizes = [ 16, 32, 96, 192 ];
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
	 * Filter comment URLs to only use author URLs.
	 *
	 * @param string $return     The HTML-formatted comment author link.
	 *                           Empty for an invalid URL.
	 * @param string $author     The comment author's username.
	 * @param int    $comment_ID The comment ID.
	 *
	 * @return string
	 */
	public function filter_comment_author_link( $return, $author, $comment_id ) {

		$user_id = $this->helper->get_comment_user_id( $comment_id );

		if ( empty( $user_id ) ) {
			return $author;
		}

		$author_url = get_author_posts_url( $user_id );

		if ( empty( $author_url ) ) {
			return $author;
		}

		return sprintf( '<a href="%1$s">%2$s</a>', esc_url( $author_url ), $author );
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
		$login_ver = '1.3';
		wp_enqueue_style( 'wpc-network-login', trailingslashit( $this->helper->get_plugin_url() ) . 'assets/css/wpc-network-login.min.css', [], $login_ver );
	}

	public function add_to_login_footer() {
		global $blog_id;

		// Default action is to login.
		$action = 'login';

		$available_actions = [ 'login', 'lostpassword' ];

		if ( ! empty( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $available_actions ) ) {
			$action = $_REQUEST['action'];
		}

		$nav_items = [];

		if ( 'lostpassword' === $action ) {

			$nav_items[] = [
				'href' => wp_login_url(),
				'text' => 'Login',
				'icon' => 'login',
			];
		} else {

			$lost_password_url = wp_lostpassword_url();

			$nav_items[] = [
				'href' => $lost_password_url,
				'text' => 'Lost your password',
				'icon' => 'question',
			];

			$nav_items[] = [
				'href' => $lost_password_url,
				'text' => 'Not sure if you have an account',
				'icon' => 'question',
			];
		}

		// Create an account.
		$nav_items[] = [
			'href' => 'https://www.wpcampus.org/community/membership/',
			'text' => 'Create an account',
			'icon' => 'plus',
		];

		/*
		 * Always add home nav.
		 */
		if ( 1 == $blog_id ) {
			$home_url = 'https://www.wpcampus.org';
			$home_text = 'Go to wpcampus.org';
		} else {

			$home_url = home_url( '/' );
			$home_url_parts = parse_url( $home_url );

			if ( ! empty( $home_url_parts['host'] ) ) {
				$home_text = "Go to {$home_url_parts['host']}";
			} else {
				$home_text = 'Go to website';
			}
		}

		$nav_items[] = [
			'href' => $home_url,
			'text' => $home_text,
			'icon' => 'home',
		];

		if ( 'lostpassword' === $action ) :

			$contact_url = 'https://www.wpcampus.org/about/contact/';

			?>
			<div id="wpc-login-instructions">
				<h2>When to use this form</h2>
				<p>If you've lost your password or need to confirm you have an account.</p>
				<h2>Next steps</h2>
				<h3>If you know your username or email address</h3>
				<p>Enter your username or email address. You will receive an email message with instructions on how to reset your password.</p>
				<h3>If you're unsure if you have an account</h3>
				<p>Enter your email address. If your account exists, you will receive an email message with instructions on how to reset your password. Feel free to try multiple email addresses.</p>
				<h3>If nothing works</h3>
				<p><a href="<?php echo esc_url( $contact_url ); ?>>">Contact WPCampus</a> and report the issue.</p>
			</div>
		<?php
		endif;

		?>
		<nav id="wpc-login-nav">
			<ul>
				<?php

				$allowed_icons = [ 'home', 'login', 'question', 'plus' ];

				foreach ( $nav_items as $item ) :
					if ( empty( $item['href'] ) || empty( $item['text'] ) ) {
						continue;
					}

					$icon = '';
					if ( ! empty( $item['icon'] ) && in_array( $item['icon'], $allowed_icons ) ) {
						$icon = '<span class="wpc-icon wpc-icon--' . $item['icon'] . '"></span>';
					}

					?>
					<li><a href="<?php echo esc_url( $item['href'] ); ?>"><?php echo $icon; ?><span><?php echo $item['text']; ?></span></a></li>
				<?php
				endforeach;

				?>
			</ul>
		</nav>
		<?php
	}

	/**
	 * Process when a user registers.
	 * We make sure they are added to every
	 * site on the network.
	 */
	public function process_user_registration( $user_id ) {

		// Assign to every blog on the network.
		$this->helper->assign_user_to_all_blogs( $user_id );

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
	 * Manage file types allowed for upload.
	 *
	 * @param array            $types Mime types keyed by the file extension regex corresponding to
	 *                                those types. 'swf' and 'exe' removed from full list. 'htm|html' also
	 *                                removed depending on '$user' capabilities.
	 * @param int|WP_User|null $user  User ID, User object or null if not provided (indicates current user).
	 *
	 * @return array
	 */
	public function filter_upload_mimes( $types, $user ): array {

		if ( empty( $types ) || ! is_array( $types ) ) {
			$types = [];
		}

		// Allow SVGs.
		$types['svg'] = 'image/svg+xml';

		return $types;
	}

	/**
	 * Filter user capabilities.
	 *
	 * @param array - $allcaps - An array of all the user's capabilities.
	 * @param array - $caps - Actual capabilities for meta capability.
	 * @param array - $args - Optional parameters passed to has_cap(), typically object ID.
	 * @param WP_User - $user - The user object.
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

			$post_id = get_the_ID();
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
	 * Register our custom REST routes.
	 */
	public function register_rest_routes() {

		register_rest_route(
			'wpcampus',
			'/search/',
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'get_search_results' ],
			]
		);
	}

	/**
	 * Register custom fields for REST API.
	 */
	public function register_rest_fields() {

		register_rest_field(
			[
				'post',
				'page',
				'podcast',
			],
			'wpc_seo',
			[
				'get_callback' => [ $this, 'get_wpc_seo_meta' ],
			]
		);
	}

	/**
	 * Add data to "wpc_seo" API field.
	 *
	 * @param $object
	 * @param $field_name
	 *
	 * @return array
	 */
	public function get_wpc_seo_meta( $object, $field_name ) {

		$title = get_post_meta( $object['id'], 'wpc_seo_title', true );
		if ( empty( $title ) ) {
			$title = "";
		}

		$meta_desc = get_post_meta( $object['id'], 'wpc_seo_meta_desc', true );
		if ( empty( $meta_desc ) ) {

			// Add excerpt info.
			$post_excerpt_basic = '';

			if ( ! empty( $object['excerpt']['rendered'] ) ) {
				$post_excerpt_basic = $this->get_excerpt_basic( $object['excerpt']['rendered'] );
			}

			if ( ! empty( $post_excerpt_basic ) ) {
				$meta_desc = $post_excerpt_basic;
			} else {
				$meta_desc = "";
			}
		}

		$robots = get_post_meta( $object['id'], 'wpc_seo_robots', true );
		if ( empty( $robots ) || ! is_array( $robots ) ) {
			$robots = [];
		}

		return [
			'title' => $title,
			'meta'  => [
				'description' => $meta_desc,
				'robots'      => $robots,
			],
		];
	}

	/**
	 * Filter search results query to get author.
	 *
	 * @param $pieces
	 * @param $query
	 *
	 * @return mixed
	 */
	public function filter_search_results_query( $pieces, $query ) {
		global $wpdb;

		// Only run on our search.
		if ( empty( $query->get( 'wpcampus_search' ) ) ) {
			return $pieces;
		}

		$pieces['fields'] .= ", wpc_users.display_name AS author_display_name, wpc_users.user_nicename AS author_path";
		$pieces['join'] .= " LEFT JOIN {$wpdb->users} wpc_users ON wpc_users.ID = {$wpdb->posts}.post_author";

		return $pieces;
	}

	/**
	 * Prepare REST response for /wpcampus/search/ endpoint.
	 *
	 * @TODO add pagination?
	 *
	 * @return WP_REST_Response
	 */
	public function get_search_results() {
		global $wpdb;

		if ( empty( $_GET['search'] ) ) {
			return new WP_REST_Response( [] );
		}

		$search_term = sanitize_text_field( $_GET['search'] );

		$post_types = [ 'post', 'page', 'podcast' ];

		$query_args = [
			's'                   => $search_term,
			'post_type'           => $post_types,
			'post_status'         => 'publish',
			//'paged'               => (int) $request['page'],
			'posts_per_page'      => - 1,
			'ignore_sticky_posts' => true,
			'orderby'             => 'post_modified',
			'order'               => 'DESC',
			'wpcampus_search'     => true,
			'meta_query'          => [
				'relation' => 'OR',
				[
					'key'     => 'wpc_search_disable',
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => 'wpc_search_disable',
					'value'   => '1',
					'compare' => '!=',
				],
			],
		];

		$query = new WP_Query( $query_args );

		$posts = $query->posts;

		$clean_posts = [];

		$blog_url = get_bloginfo( 'url' );

		// Will hold ID of user info we need.
		$author_ids = [];

		$categories = [];

		foreach ( $posts as $post ) {

			// Author will be converted to an array for content that displays authors.
			$author = (int) $post->post_author;

			if ( in_array( $post->post_type, [ 'post', 'podcast' ] ) ) {

				$categories = wp_get_post_categories( $post->ID, [ 'fields' => 'all' ] );

				$author = [
					[
						'ID'           => $author,
						'path'         => $post->author_path,
						'display_name' => $post->author_display_name,
					],
				];

				// Only running for posts that display author info.
				if ( function_exists( 'my_multi_author' ) ) {

					$multi_authors = my_multi_author()->get_authors( $post->ID );

					if ( ! empty( $multi_authors ) ) {

						// If we have the same amount of authors, nevermind.
						if ( count( $multi_authors ) !== count( $author ) ) {

							foreach ( $author as $author_info ) {

								$author_id = 0;

								if ( ! is_array( $author_info ) ) {
									$author_id = (int) $author_info;
								} else if ( ! empty( $author_info['ID'] ) ) {
									$author_id = (int) $author_info['ID'];
								}

								if ( ! $author_id ) {
									continue;
								}

								$key = array_search( $author_id, $multi_authors );

								if ( $key >= 0 ) {
									unset( $multi_authors[ $key ] );
								}
							}

							// Store multi author ID back in result.
							foreach ( $multi_authors as $author_id ) {
								$author[] = [ 'ID' => $author_id ];
								$author_ids[] = $author_id;
							}
						}
					}
				}

				$post_path = get_permalink( $post->ID );

				// Remove protocol and host so its complete slug path.
				if ( ! empty( $post_path ) ) {
					$post_path = str_replace( $blog_url, '', $post_path );
				}
			} else {
				$post_path = get_page_uri( $post );

				if ( ! empty( $post_path ) ) {
					$post_path = '/' . trailingslashit( $post_path );
				}
			}

			// Add excerpt info.
			$post_excerpt_basic = '';

			if ( ! empty( $post->post_excerpt ) ) {
				$post_excerpt_basic = $this->get_excerpt_basic( $post->post_excerpt );
			}

			if ( empty( $post_excerpt_basic ) && ! empty( $post->post_content ) ) {
				$post_excerpt_basic = $this->get_excerpt_basic( $post->post_content );
			}

			$clean_posts[] = [
				'ID'         => $post->ID,
				'title'      => $post->post_title,
				'type'       => $post->post_type,
				'author'     => $author,
				'parent'     => $post->post_parent,
				'date'       => $post->post_date,
				'modified'   => $post->post_modified,
				'slug'       => $post->post_name,
				'path'       => $post_path,
				'status'     => $post->post_status,
				'categories' => $categories,
				'excerpt'    => [
					'basic'    => ! empty( $post_excerpt_basic ) ? $post_excerpt_basic : '',
					'rendered' => ! empty( $post_excerpt_basic ) ? wpautop( $post_excerpt_basic ) : '',
				],
				'content'    => [
					'basic'    => $post->post_content,
					'rendered' => wpautop( $post->post_content ),
				],
			];
		}

		if ( ! empty( $author_ids ) ) {

			$author_ids = array_unique( $author_ids );

			// Do 1 DB query to get all author info we need.
			$author_id_str = "(" . implode( ",", $author_ids ) . ")";
			$authors = $wpdb->get_results( "SELECT ID, display_name, user_nicename AS path FROM {$wpdb->users} WHERE ID IN {$author_id_str}" );

			$authors_by_id = [];
			foreach ( $authors as $author ) {
				if ( isset( $authors_by_id[ $author->ID ] ) ) {
					continue;
				}
				$authors_by_id[ $author->ID ] = $author;
			}

			if ( ! empty( $authors ) ) {

				foreach ( $clean_posts as &$post ) {
					if ( "post" != $post['type'] ) {
						continue;
					}
					foreach ( $post['author'] as &$author ) {
						if ( ! empty( $author['display_name'] ) ) {
							continue;
						}
						if ( ! isset( $authors_by_id[ $author['ID'] ] ) ) {
							continue;
						}
						$author = $authors_by_id[ $author['ID'] ];
					}
				}
			}
		}

		if ( ! function_exists( 'wpcampus_speakers' ) ) {
			return new WP_REST_Response( $clean_posts );
		}

		// Get sessions. Force these defaults.
		$args['proposal_status'] = 'confirmed';
		$args['get_feedback'] = false;
		$args['get_subjects'] = true;
		$args['search'] = $search_term;

		// @TODO right now doesn't search speaker names.
		$sessions = wpcampus_speakers()->get_sessions_public( $args );

		if ( ! empty( $sessions ) ) {

			// Add type to each session.
			$sessions = array_map( function ( $session ) {
				$session->type = "session";
				return $session;
			}, $sessions );

			$clean_posts = array_merge( $clean_posts, $sessions );
		}

		//$found_ids = $query->query( $query_args );
		//$total     = $query->found_posts;

		/*return array(
			self::RESULT_IDS   => $found_ids,
			self::RESULT_TOTAL => $total,
		);*/

		return new WP_REST_Response( $clean_posts );
	}

	/**
	 * Filter the main user REST query.
	 *
	 * @param $prepared_args - array - Arguments for WP_User_Query.
	 * @param $request       - WP_REST_Request - The current request.
	 *
	 * @return mixed
	 */
	public function filter_rest_user_query( $prepared_args, $request ) {

		$prepared_args['number'] = - 1;

		$post_types = get_post_types( [ 'show_in_rest' => true, 'public' => true ], 'names' );

		$post_types_remove = [ 'attachment' ];

		foreach ( $post_types_remove as $post_type ) {
			if ( ! isset( $post_types[ $post_type ] ) ) {
				continue;
			}
			unset( $post_types[ $post_type ] );
		}

		$post_types = apply_filters( 'wpcampus_rest_published_post_types', $post_types );

		$prepared_args['has_published_posts'] = $post_types;

		return $prepared_args;
	}

	/**
	 * Returns a shortened, no tags post excerpt.
	 *
	 * @param $post
	 *
	 * @return mixed
	 */
	private function get_excerpt_basic( $excerpt, $length = 30 ) {
		if ( empty( $excerpt ) ) {
			return "";
		}

		// Remove any tags.
		$excerpt = strip_tags( $excerpt );

		// Trim the length.
		return wp_trim_words( $excerpt, $length, '...' );
	}

	/**
	 * Filter the response for blog posts.
	 *
	 * @param $response - WP_REST_Response - The response object.
	 * @param $post     - WP_Post - Post object.
	 * @param $request  - WP_REST_Request - Request object.
	 *
	 * @return mixed
	 */
	public function filter_rest_prepare_post( $response, $post, $request ) {
		global $wpdb;

		// Add excerpt info.
		$post_excerpt_basic = '';

		if ( ! empty( $post->post_excerpt ) ) {
			$post_excerpt_basic = $this->get_excerpt_basic( $post->post_excerpt );
		}

		if ( empty( $post_excerpt_basic ) && ! empty( $post->post_content ) ) {
			$post_excerpt_basic = $this->get_excerpt_basic( $post->post_content );
		}

		// Make sure we have an excerpt item.
		if ( ! isset( $response->data['excerpt'] ) ) {
			$response->data['excerpt'] = [];
		}

		// Add a "basic" excerpt.
		$response->data['excerpt']['basic'] = ! empty( $post_excerpt_basic ) ? $post_excerpt_basic : '';
		$response->data['excerpt']['rendered'] = ! empty( $post_excerpt_basic ) ? wpautop( $post_excerpt_basic ) : '';

		$params = $request->get_params();

		// Only keep going if we need meta.
		if ( empty( $params['get_meta'] ) ) {
			return $response;
		}

		// Convert author IDs to info needed.
		if ( ! empty( $response->data['author'] ) ) {

			$author = $response->data['author'];

			if ( ! is_array( $author ) ) {
				$author = [ $author ];
			}

			$author = array_map( function ( $author ) {
				if ( ! empty( $author->ID ) ) {
					return $author->ID;
				}
				return $author;
			}, $author );

			$author_str = "(" . implode( ",", $author ) . ")";

			$authors = $wpdb->get_results( "SELECT ID, display_name, user_nicename AS path FROM {$wpdb->users} WHERE ID IN " . $author_str );

			$response->data['author'] = $authors;
		}

		return $response;
	}

	/**
	 * Filter the response for pages.
	 *
	 * @param $response - WP_REST_Response - The response object.
	 * @param $post     - WP_Post - Post object.
	 * @param $request  - WP_REST_Request - Request object.
	 *
	 * @return mixed
	 */
	public function filter_rest_prepare_page( $response, $post, $request ) {

		// Get the provided crumb text.
		$crumb_text = get_post_meta( $post->ID, 'wpc_crumb_text', true );

		// If no provided crumb text, get the page title.
		if ( empty( $crumb_text ) ) {

			if ( ! empty( $response->data['title']['rendered'] ) ) {
				$crumb_text = $response->data['title']['rendered'];
			} else if ( ! empty( $response->data['title'] ) && is_string( $response->data['title'] ) ) {
				$crumb_text = $response->data['title'];
			} else {
				$crumb_text = '';
			}
		}

		// Get the provided crumb ARIA label.
		$crumb_aria = get_post_meta( $post->ID, 'wpc_crumb_aria_label', true );

		// Get the page link.
		if ( ! empty( $response->data['link'] ) ) {
			$page_link = $response->data['link'];
		} else if ( ! empty( $post->ID ) ) {
			$page_link = get_permalink( $post->ID );
		} else {
			$page_link = '';
		}

		// Make sure the link is a string.
		if ( empty( $page_link ) ) {
			$page_link = '';
		}

		// Get the page path.
		$page_path = get_page_uri( $post );
		if ( ! empty( $page_path ) ) {
			$page_path = '/' . trailingslashit( $page_path );
		} else {
			$page_path = '';
		}

		// Add crumbs to data.
		$crumb = [
			'aria_label' => $crumb_aria,
			'text'       => $crumb_text,
			'link'       => $page_link,
			'path'       => $page_path,
		];

		$response->data['crumb'] = $crumb;

		// Add excerpt info.
		$post_excerpt_basic = '';

		if ( ! empty( $post->post_excerpt ) ) {
			$post_excerpt_basic = $this->get_excerpt_basic( $post->post_excerpt );
		}

		if ( empty( $post_excerpt_basic ) && ! empty( $post->post_content ) ) {
			$post_excerpt_basic = $this->get_excerpt_basic( $post->post_content );
		}

		// Make sure we have an excerpt item.
		if ( ! isset( $response->data['excerpt'] ) ) {
			$response->data['excerpt'] = [];
		}

		// Add a "basic" excerpt.
		$response->data['excerpt']['basic'] = ! empty( $post_excerpt_basic ) ? $post_excerpt_basic : '';
		$response->data['excerpt']['rendered'] = ! empty( $post_excerpt_basic ) ? wpautop( $post_excerpt_basic ) : '';

		return $response;
	}

	/**
	 * Register the network footer menu.
	 *
	 * @return  void
	 */
	function register_network_footer_menu() {
		if ( $this->helper->is_enabled( 'footer' ) ) {
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
		$plugin_url = trailingslashit( $this->helper->get_plugin_url() );
		$css_dir = $plugin_url . 'assets/css/';
		$js_dir = $plugin_url . 'assets/js/';

		$assets_ver = '1.4';

		// Setup the font weights we need.
		$open_sans_weights = apply_filters( 'wpcampus_open_sans_font_weights', [] );

		if ( ! is_array( $open_sans_weights ) ) {
			$open_sans_weights = [];
		} else {
			$open_sans_weights = array_filter( $open_sans_weights, 'intval' );
		}

		// Make sure the weights we need for our components are there.
		if ( $this->helper->is_enabled( 'banner' ) ) {
			$open_sans_weights = array_merge( $open_sans_weights, [ 400, 600, 700 ] );
		}

		if ( $this->helper->is_enabled( 'notifications' ) ) {
			$open_sans_weights = array_merge( $open_sans_weights, [ 400 ] );
		}

		if ( $this->helper->is_enabled( 'footer' ) ) {
			$open_sans_weights = array_merge( $open_sans_weights, [ 400, 600 ] );
		}

		if ( $this->helper->is_enabled( 'videos' ) ) {
			$open_sans_weights = array_merge( $open_sans_weights, [ 600 ] );
		}

		// Register our fonts.
		wp_register_style( 'wpc-fonts-open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans:' . implode( ',', array_unique( $open_sans_weights ) ) );

		// Register assets needed below.
		wp_register_script( 'handlebars', $js_dir . 'handlebars.min.js', [], null, true );
		wp_register_script( 'mustache', $js_dir . 'mustache.min.js', [], null, true );

		$toggle_menu_js = $this->debug ? 'src/wpc-network-toggle-menu.js' : 'wpc-network-toggle-menu.min.js';

		// Keep this one outside logic so I can register as a dependency in scripts outside the plugin.
		wp_register_script( 'wpc-network-toggle-menu', $js_dir . $toggle_menu_js, [ 'jquery', 'jquery-ui-core' ], null );

		// Enqueue the network banner styles.
		if ( $this->helper->is_enabled( 'banner' ) ) {
			wp_enqueue_style( 'wpc-network-banner', $css_dir . 'wpc-network-banner.min.css', [ 'wpc-fonts-open-sans' ], $assets_ver );
			wp_enqueue_script( 'wpc-network-toggle-menu' );
		}

		// Enqueue the network notification assets.
		if ( $this->helper->is_enabled( 'notifications' ) ) {
			wp_enqueue_style( 'wpc-network-notifications', $css_dir . 'wpc-network-notifications.min.css', [ 'wpc-fonts-open-sans' ], null );

			$notifications_js = $this->debug ? 'src/wpc-network-notifications.js' : 'wpc-network-notifications.min.js';

			wp_enqueue_script( 'wpc-network-notifications', $js_dir . $notifications_js, [ 'jquery', 'mustache' ], null, true );
			wp_localize_script( 'wpc-network-notifications', 'wpc_net_notifications', [
				'main_url' => wpcampus_get_network_site_url(),
			] );
		}

		// Enqueue the network Code of Conduct styles.
		if ( $this->helper->is_enabled( 'coc' ) ) {
			wp_enqueue_style( 'wpc-network-conduct', $css_dir . '@wpcampus/wpcampus-conduct.min.css', [ 'wpc-fonts-open-sans' ] , $assets_ver );
			wp_enqueue_script( 'wpc-network-conduct', $js_dir . '@wpcampus/wpcampus-conduct.min.js', [], $assets_ver, true );
		}

		// Enqueue the network footer styles.
		if ( $this->helper->is_enabled( 'footer' ) ) {
			wp_enqueue_style( 'wpc-network-footer', $css_dir . '@wpcampus/wpcampus-footer.min.css', [ 'wpc-fonts-open-sans' ] , $assets_ver );
			wp_enqueue_script( 'wpc-network-footer', $js_dir . '@wpcampus/wpcampus-footer.min.js', [], $assets_ver, true );
		}

		// Enqueue the sessions assets.
		if ( $this->helper->is_enabled( 'sessions' ) ) {

			// Get this site's timezone and offset.
			$timezone = new DateTimeZone( get_option( 'timezone_string' ) ?: 'UTC' );
			$current_time_offset = $timezone->getOffset( new DateTime() );

			// Get the difference in hours.
			$timezone_offset_hours = ( $current_time_offset / 60 ) / 60;

			$sessions_ver = '1.6';

			$sessions_js = $this->debug ? 'src/wpc-network-sessions.js' : 'wpc-network-sessions.min.js';

			wp_register_style( 'wpc-network-sessions-icons', $css_dir . 'conf-schedule-icons.min.css', [], $sessions_ver );

			wp_enqueue_style( 'wpc-network-sessions', $css_dir . 'wpc-network-sessions.min.css', [ 'wpc-network-sessions-icons' ], $sessions_ver );

			wp_enqueue_script( 'wpc-network-sessions', $js_dir . $sessions_js, [ 'jquery', 'handlebars' ], $sessions_ver, true );
			wp_localize_script( 'wpc-network-sessions', 'wpc_sessions', [
				'ajaxurl'        => admin_url( 'admin-ajax.php' ),
				'load_error_msg' => '<p>' . __( 'Oops. Looks like something went wrong. Please refresh the page and try again.', 'wpcampus-network' ) . '</p><p>' . sprintf( __( 'If the problem persists, please %1$slet us know%2$s.', 'wpcampus' ), '<a href="/contact/">', '</a>' ) . '</p>',
				'tz_offset'      => $timezone_offset_hours,
			] );
		}

		// Enable the watch video assets.
		if ( $this->helper->is_enabled( 'videos' ) ) {

			// Enqueue styles and scripts for the display.
			wp_enqueue_style( 'magnific-popup', $css_dir . 'magnific-popup.min.css' );
			wp_enqueue_script( 'magnific-popup', $js_dir . 'jquery.magnific-popup.min.js', [ 'jquery' ] );

			wp_enqueue_style( 'wpc-network-watch', $css_dir . 'wpc-network-watch.min.css', [ 'magnific-popup' ] );

			$watch_js = $this->debug ? 'src/wpc-network-watch.js' : 'wpc-network-watch.min.js';

			wp_enqueue_script( 'wpc-network-watch', $js_dir . $watch_js, [ 'jquery', 'handlebars', 'magnific-popup' ] );
			wp_localize_script( 'wpc-network-watch', 'wpc_net_watch', [
				'main_url'  => wpcampus_get_network_site_url(),
				'no_videos' => __( 'There are no videos available.', 'wpcampus-network' ),
			] );
		}

		$this->helper->enqueue_base_script();

		//$this->helper->enqueue_login_script();

	}

	/**
	 * Add Mailchimp popup code to footer.
	 */
	function add_mailchimp_popup_script() {

		if ( ! $this->helper->is_enabled( 'mailchimp_popup' ) ) {
			return;
		}

		?>
		<script type="text/javascript" src="//downloads.mailchimp.com/js/signup-forms/popup/embed.js" data-dojo-config="usePlainJson: true, isDebug: false"></script>
		<script type="text/javascript">
			function showMailingPopUp() {
				require( [ "mojo/signup-forms/Loader" ], function( L ) {
					L.start( { "baseUrl": "mc.us11.list-manage.com", "uuid": "6d71860d429d3461309568b92", "lid": "05f39a2a20" } )
				} )
				document.cookie = "MCEvilPopupClosed=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
				document.cookie = "MCPopupClosed=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
			}
			document.querySelector( '.wpc-subscribe-open' ).onclick = function() {
				showMailingPopUp()
			};
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
	 * @param string - $return - The returned oEmbed HTML.
	 * @param object - $data - A data object result from an oEmbed provider.
	 * @param string - $url - The URL of the content to be embedded.
	 *
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
	 * @param   $args    - array - arguments passed to get_post_types().
	 * @param   $form_id - int - the form ID.
	 *
	 * @return  array - the arguments we want to use.
	 */
	public function filter_gfcpt_post_type_args( $args, $form_id ) {
		return [];
	}

	/**
	 * Make sure we can use any taxonomy in
	 * the Gravity Forms custom post type extension.
	 *
	 * @param   $args    - array - arguments passed to get_taxonomies().
	 * @param   $form_id - int - the form ID.
	 *
	 * @return  array - the arguments we want to use.
	 */
	public function filter_gfcpt_tax_args( $args, $form_id ) {
		return [
			'_builtin' => false,
		];
	}

	/**
	 * Filter the arguments for the FooGallery galleries post type.
	 *
	 * @param   $args - array - the original post type arguments.
	 *
	 * @return  array - the filtered arguments.
	 */
	public function filter_foogallery_cpt_args( $args ) {
		$args['capability_type'] = [ 'gallery', 'galleries' ];

		return $args;
	}

	/**
	 * Add content to top of login forms.
	 *
	 * @param   $content - string - the default content, which is blank.
	 * @param   $args    - array - the login form arguments.
	 *
	 * @return  string - the returned content.
	 */
	public function add_to_login_form_top( $content, $args ) {
		global $post;

		$header = '';
		$default_header = 'h2';

		$title = '';
		if ( ! empty( $args['wpc_form_title'] ) ) {

			if ( is_singular() && ! empty( $post->ID ) ) {
				$header = get_post_meta( $post->ID, 'wpcampus_login_form_header', true );
				$title = get_post_meta( $post->ID, 'wpcampus_login_form_title', true );

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
		if ( ! empty( $args['wpc_form_message'] ) ) {

			if ( is_singular() && ! empty( $post->ID ) ) {
				$message = get_post_meta( $post->ID, 'wpcampus_login_form_message', true );
			}

			// Add our login message.
			$message .= '<p>Don\'t have a WPCampus user account? <a class="button inline royal-blue" href="https://wpcampus.org/get-involved/">Create an account</a></p>';

			$message = apply_filters( 'wpcampus_login_form_message', $message );

		}

		if ( ! empty( $args['wpc_ajax'] ) && true === $args['wpc_ajax'] ) {
			wp_nonce_field( 'wpc_ajax_login', 'wpc_ajax_login_nonce' );
		}

		return $title . $message;
	}

	/**
	 * Add content to bottom of login forms.
	 *
	 * @param   $content - string - the default content, which is blank.
	 * @param   $args    - array - the login form arguments.
	 *
	 * @return  string - the returned content.
	 */
	public function add_to_login_form_bottom( $content, $args ) {

		$message = '<p><a href="' . esc_url( wp_lostpassword_url() ) . '">Lost your password?</a></p>';

		return $content . $message;
	}

	/**
	 *
	 */
	public function process_ajax_login() {

		check_ajax_referer( 'wpc_ajax_login', 'wpc_ajax_login_nonce' );

		$info = [
			'user_login'    => $_POST['log'],
			'user_password' => $_POST['pwd'],
			'remember'      => $_POST['rememberme'],
		];

		$user_signon = wp_signon( $info, false );

		if ( is_wp_error( $user_signon ) ) {
			echo json_encode(
				[
					'loggedin' => false,
					'message'  => $user_signon->get_error_message(),
				]
			);
		} else {
			echo json_encode(
				[
					'loggedin' => true,
					'message'  => __( 'Login successful, redirecting...' ),
				]
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
	public function process_ajax_get_sessions_public() {
		$args = [];
		$sessions = [];

		$filters = [
			'assets'  => [ 'slides', 'video' ],
			'orderby' => [ 'date', 'title' ],
			'order'   => [ 'asc', 'desc' ],
			'search'  => [],
			'subject' => [],
			'format'  => [],
		];

		$display_events = wpcampus_speakers()->get_sessions_display_event_terms();

		// Will hold event term IDs and slugs from our settings.
		$event_terms = [];

		// Is used if no events are passed to restrict the query to event settings.
		$event_term_ids = [];

		if ( ! empty( $display_events ) ) {
			foreach ( $display_events as $event ) {
				if ( ! empty( $event->term_id ) ) {
					$event_terms[] = $event->term_id;
					$event_term_ids[] = $event->term_id;
				}
				if ( ! empty( $event->slug ) ) {
					$event_terms[] = $event->slug;
				}
			}
		}

		if ( ! empty( $event_terms ) ) {
			$filters['event'] = $event_terms;
		}

		if ( ! empty( $_GET['filters'] ) ) {
			foreach ( $filters as $filter => $options ) {
				if ( ! empty( $_GET['filters'][ $filter ] ) ) {
					$filter_val = strtolower( str_replace( ' ', '', $_GET['filters'][ $filter ] ) );

					$has_open_field = in_array( $filter, [ 'search', 'subject', 'format' ] );

					if ( $has_open_field ) {
						$filter_val = sanitize_text_field( $filter_val );
					}

					// Means it has a comma so convert to array.
					if ( strpos( $filter_val, ',' ) !== false ) {
						$filter_val = explode( ',', $filter_val );
					} else if ( ! is_array( $filter_val ) ) {
						$filter_val = [ $filter_val ];
					}

					$filtered_values = [];
					foreach ( $filter_val as $value ) {
						if ( $has_open_field || in_array( $value, $options ) ) {
							$filtered_values[] = $value;
						}
					}

					// Convert back to CSVs.
					$args[ $filter ] = implode( ',', $filtered_values );
				}
			}
		}

		// Set default event IDs.
		if ( empty( $args['event'] ) && ! empty( $event_term_ids ) ) {
			$args['event'] = $event_term_ids;
		}

		// Build query URL.
		$url = get_bloginfo( 'url' ) . '/wp-json/wpcampus/data/public/sessions/';

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		// Get our profiles.
		$response = wp_safe_remote_get(
			$url,
			[
				'timeout' => 10,
			]
		);

		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
			$sessions = json_decode( wp_remote_retrieve_body( $response ) );
		}

		echo json_encode(
			[
				'count'    => ! empty( $sessions ) ? count( $sessions ) : 0,
				'sessions' => $sessions,
			]
		);

		wp_die();

	}

	/**
	 * Add JS templates to the footer when needed.
	 */
	public function print_js_templates() {
		// Add the sessions template.
		if ( ! $this->helper->is_enabled( 'sessions' ) ) {
			return;
		}

		$formats = [];

		$format_terms = get_terms(
			'session_format',
			[
				'hide_empty' => false,
			]
		);

		if ( ! empty( $format_terms ) ) {
			foreach ( $format_terms as $format ) {
				$formats[ $format->slug ] = $format->name;
			}
		}

		$events = [
			'wpcampus-2019'        => 'WPCampus 2019',
			'wpcampus-2018'        => 'WPCampus 2018',
			'wpcampus-2017'        => 'WPCampus 2017',
			'wpcampus-2016'        => 'WPCampus 2016',
			'wpcampus-online-2019' => 'WPCampus Online 2019',
			'wpcampus-online-2018' => 'WPCampus Online 2018',
			'wpcampus-online-2017' => 'WPCampus Online 2017',
		];

		$subjects = function_exists( 'wpcampus_get_sessions_subjects' ) ? wpcampus_get_sessions_subjects() : [];

		$plugin_url = $this->helper->get_plugin_url();
		$images_dir = trailingslashit( $plugin_url ) . 'assets/images/';

		/*
		 * TODO:
		 * - Add tracking for filters.
		 * - Make filters bar fixed when scrolling.
		 */

		/*{{^session_video_url}}<br><span class="session-video">NO VIDEO</span>{{/session_video_url}}*/

		?>
		<script id="wpc-sessions-filters-template" type="text/x-handlebars-template">
            <form class="wpcampus-sessions-filters-form" aria-label="Filter items by subject, event and keyword">
                <div class="wpcampus-sessions-filter-field wpcampus-sessions-filter-field--subjects">
                    <label for="wpc-session-filter-subjects" class="wpcampus-sessions-filter-field__label"
                           aria-label="Filter items by subject">Subjects</label>
                    <select id="wpc-session-filter-subjects"
                            class="wpcampus-sessions-filter wpcampus-sessions-filter--select wpcampus-sessions-filter--subjects" name="subject"
                            aria-controls="wpcampus-sessions">
                        <option value=""><?php _e( 'All subjects', 'wpcampus-network' ); ?></option>
                        <?php

			foreach ( $subjects as $subject ) :
				?>
				<option value="<?php echo $subject->slug; ?>" {{{selected "<?php echo $subject->slug; ?>" subject}}}><?php echo $subject->name; ?></option>
				<?php
			endforeach;

			?>
                    </select>
                </div>
                <div class="wpcampus-sessions-filter-field wpcampus-sessions-filter-field--format">
                    <label for="wpc-session-filter-format" class="wpcampus-sessions-filter-field__label"
                           aria-label="Filter items by format">Formats</label>
                    <select id="wpc-session-filter-format"
                            class="wpcampus-sessions-filter wpcampus-sessions-filter--select wpcampus-sessions-filter--format" name="format"
                            aria-controls="wpcampus-sessions">
                        <option value="">All formats</option>
                        <?php

			foreach ( $formats as $slug => $format ) :
				?>
				<option value="<?php echo $slug; ?>" {{{selected "<?php echo $slug; ?>" format}}}><?php echo $format; ?></option>
				<?php
			endforeach;

			?>
                    </select>
                </div>
                <div class="wpcampus-sessions-filter-field wpcampus-sessions-filter-field--event">
                    <label for="wpc-session-filter-event" class="wpcampus-sessions-filter-field__label"
                           aria-label="Filter items by event">Events</label>
                    <select id="wpc-session-filter-event"
                            class="wpcampus-sessions-filter wpcampus-sessions-filter--select wpcampus-sessions-filter--event" name="event"
                            aria-controls="wpcampus-sessions">
                        <option value=""><?php _e( 'All events', 'wpcampus-network' ); ?></option>
                        <?php

			foreach ( $events as $slug => $event ) :
				?>
				<option value="<?php echo $slug; ?>" {{{selected "<?php echo $slug; ?>" event}}}><?php echo $event; ?></option>
				<?php
			endforeach;

			?>
                    </select>
                </div>
                <div class="wpcampus-sessions-filter-field wpcampus-sessions-filter-field--search">
                    <label for="wpc-session-filter-search" class="wpcampus-sessions-filter-field__label"
                           aria-label="Search items by keyword">Search</label>
                    <input id="wpc-session-filter-search"
                           class="wpcampus-sessions-filter wpcampus-sessions-filter--search wpcampus-sessions-filter--text"
                           type="search" name="search" placeholder="Search sessions" value="{{search}}"
                           aria-controls="wpcampus-sessions"/>
                </div>
                <div class="wpcampus-sessions-filter-field wpcampus-sessions-filter-field--orderby">
                    <label for="wpc-session-filter-orderby" class="wpcampus-sessions-filter-field__label" aria-label="Order items by date or title">Order by</label>
                    <select id="wpc-session-filter-orderby" class="wpcampus-sessions-filter wpcampus-sessions-filter--select wpcampus-sessions-filter--orderby" name="orderby" aria-controls="wpcampus-sessions">
                        <option value="title,asc" {{{selected_orderby "title" "asc"}}}><?php _e( 'Title, ascending', 'wpcampus-network' ); ?></option>
                        <option value="title,desc" {{{selected_orderby "title" "desc"}}}><?php _e( 'Title, descending', 'wpcampus-network' ); ?></option>
                        <option value="date,asc" {{{selected_orderby "date" "asc"}}}><?php _e( 'Date, ascending', 'wpcampus-network' ); ?></option>
                        <option value="date,desc" {{{selected_orderby "date" "desc"}}}><?php _e( 'Date, descending', 'wpcampus-network' ); ?></option>
                    </select>
                </div>
                <div class="wpcampus-sessions-filter-field wpcampus-sessions-filter-field--assets">
                    <fieldset>
                        <legend class="wpcampus-sessions-filter-field__label" aria-label="Filter items by assets">
                            Filter by assets
                        </legend>
                        <div class="wpcampus-sessions-filter-group wpcampus-sessions-filter-group--assets">
                            <label for="wpc-session-filter-assets-slides">
                                <input id="wpc-session-filter-assets-slides" type="checkbox" class="wpcampus-sessions-filter wpcampus-sessions-filter--assets" name="assets[]" value="slides" {{{checked_assets "slides"}}}> Has slides
							</label>
                            <label for="wpc-session-filter-assets-video">
                                <input id="wpc-session-filter-assets-video" type="checkbox" class="wpcampus-sessions-filter wpcampus-sessions-filter--assets" name="assets[]" value="video" {{{checked_assets "video"}}}> Has video
							</label>
                        </div>
                    </fieldset>
                </div>
                <input id="wpc-session-filter-submit" type="submit" class="wpcampus-sessions-update" value="<?php esc_attr_e( 'Update sessions', 'wpcampus-network' ); ?>" aria-controls="wpcampus-sessions"/>
                <input id="wpc-session-filter-reset" type="submit" class="wpcampus-sessions-reset" aria-label="<?php esc_attr_e( 'Reset sessions to default filters', 'wpcampus-network' ); ?>" value="<?php esc_attr_e( 'Reset filters', 'wpcampus-network' ); ?>" aria-controls="wpcampus-sessions"/>
            </form>



		</script>
		<script id="wpc-sessions-template" type="text/x-handlebars-template">
            <div class="wpcampus-sessions-count" aria-live="polite"></div>
            <div class="wpcampus-sessions-list">
                {{#each .}}
                <div class="wpcampus-session wpcampus-session--event-{{event_slug}}{{#if format_slug}} wpcampus-session--format-{{format_slug}}{{/if}}{{#if future}} wpcampus-session--future{{/if}}"
                     data-ID="{{ID}}">
                    {{#if future}}
                    <div class="session-notification">Future session</div>
                    {{/if}}
                    <div class="session-graphic">
                        <div class="event-thumbnail"></div>
                    </div>
                    <div class="session-info-wrapper {{sessionInfoWrapperClasses}}">
                        <div class="session-info">
                            <h2 class="session-title">{{#if permalink}}<a href="{{permalink}}">{{title}}</a>{{else}}{{title}}{{/if}}
                            </h2>
                            <ul class="session-metas">
                                {{#if event_date}}
                                    <li class="session-meta session-date">{{session_date}}</li>
                                {{/if}}
                                {{#if event_name}}
                                    <li class="session-meta session-event"><a href="{{event_permalink}}">{{event_name}}</a></li>
                                {{/if}}
                                {{#if format_name}}
                                    <li class="session-meta session-format">{{format_name}}</li>
                                {{/if}}
                            </ul>
                            {{#if subjects}}
	                            <ul class="session-subjects">
	                                {{#each subjects}}
	                                    <li class="session-subject">{{.}}</li>
	                                {{/each}}
	                            </ul>
                            {{/if}}
                            <div class="session-description">
                                <p>{{{excerpt.raw}}}</p>
                            </div>
                        </div>
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
                        {{sessionSidebar}}
                        <div class="event-name" data-event="{{event}}" aria-hidden="true"><span>{{session_event_name}}</span>
                        </div>
                    </div>
                </div>
                {{/each}}
            </div>


		</script>
		<?php

	}
}

WPCampus_Network_Global::register();
