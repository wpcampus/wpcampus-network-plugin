<?php

/**
 * The class that sets up analytics  functionality.
 * This class is initiated on every page
 * load and does not have to be instantiated.
 *
 * @class       WPCampus_Network_Analytics
 * @package     WPCampus Network
 */
final class WPCampus_Network_Analytics {

	/**
	 * Will be true if Google Analytics is enabled.
	 *
	 * @var bool
	 */
	private $google_analytics_enabled;

	/**
	 * Will hold Google Analytics tracking ID.
	 *
	 * @var string
	 */
	private $google_analytics_tracking_id;

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

		add_action( 'wp_head', [ $plugin, 'add_google_analytics' ], - 1000 );

	}

	/**
	 * Returns true if Google Analytics is enabled.
	 *
	 * @return bool
	 */
	private function is_google_analytics_enabled() {
		if ( isset( $this->google_analytics_enabled ) ) {
			return $this->google_analytics_enabled;
		}
		$this->google_analytics_enabled = ! empty( get_option( 'options_wpc_google_analytics_enable' ) );
		return $this->google_analytics_enabled;
	}

	/**
	 * Returns the Google Analytics tracking ID.
	 *
	 * @return string
	 */
	private function get_google_analytics_tracking_id() {
		if ( isset( $this->google_analytics_tracking_id ) ) {
			return $this->google_analytics_tracking_id;
		}
		$this->google_analytics_tracking_id = trim( get_option( 'options_wpc_google_analytics_tracking_id' ) );
		return $this->google_analytics_tracking_id;
	}

	/**
	 * Adds Google Analytics tracking code to the <head>.
	 */
	public function add_google_analytics() {

		// Only add for production.
		if ( 'live' !== $this->helper->get_environment() ) {
			return;
		}

		if ( ! $this->is_google_analytics_enabled() ) {
			return;
		}

		$tracking_id = $this->get_google_analytics_tracking_id();
		if ( empty( $tracking_id ) ) {
			return;
		}

		$google_url = add_query_arg( 'id', $tracking_id, 'https://www.googletagmanager.com/gtag/js' );

		?>
		<script async src="<?php echo esc_url( $google_url ); ?>"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag() {
				dataLayer.push( arguments );
			}
			gtag( 'js', new Date() );
			gtag( 'config', '<?php echo esc_js( $tracking_id ); ?>' );
		</script>
		<?php
	}
}

WPCampus_Network_Analytics::register();
