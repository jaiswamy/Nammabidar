<?php
/**
 * This is the class that handles the overall logic for notifications data.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Pixcloud_Notifications' ) ) :

class Pixcloud_Notifications {

	/**
	 * Holds the only instance of this class.
	 * @var null|Pixcloud_Notifications
	 * @access protected
	 */
	protected static $_instance = null;

	/**
	 * The current notifications data/config.
	 * @var     array
	 * @access  public
	 */
	protected $data = null;

	/**
	 * The cloud API object used to communicate with the cloud.
	 * @var     PixelgradeCare_Cloud_Api
	 * @access  public
	 */
	protected $cloud_api = null;

	/**
	 * Constructor.
	 *
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize this module.
	 *
	 */
	public function init() {
		/**
		 * Initialize the Cloud API logic.
		 */
		$this->cloud_api = PixelgradeCare_Cloud_Api::instance();
	}

	/**
	 * Get the notifications data/configuration.
	 *
	 * @param bool $skip_cache Optional. Whether to use the cached config or fetch a new one.
	 *
	 * @return array
	 */
	public function get( $skip_cache = false ) {
		if ( ! is_null( $this->data ) && false === $skip_cache ) {
			return $this->data;
		}

		$this->data = $this->maybe_fetch( $skip_cache );

		return apply_filters( 'customify_style_manager_get_design_assets', $this->data );
	}

	/**
	 * Fetch the design assets data from the Pixelgrade Cloud.
	 *
	 * Caches the data for 12 hours. Use local defaults if not available.
	 *
	 * @param bool $skip_cache Optional. Whether to use the cached data or fetch a new one.
	 *
	 * @return array|false
	 */
	protected function maybe_fetch( $skip_cache = false ) {
		// First try and get the cached data
		$data = get_option( $this->get_cache_key() );

		// For performance reasons, we will ONLY fetch remotely when in the WP ADMIN area or via an ADMIN AJAX call, regardless of settings.
		if ( ! is_admin() && false !== $data ) {
			return  $data;
		}

		// Get the cache data expiration timestamp.
		$expire_timestamp = get_option( $this->get_cache_key() . '_timestamp' );

		// We don't force skip the cache for AJAX requests for performance reasons.
		if ( ! wp_doing_ajax() && defined('PIXELGRADE_CARE__SKIP_INTERNAL_CACHE' ) && true === PIXELGRADE_CARE__SKIP_INTERNAL_CACHE ) {
			$skip_cache = true;
		}

		// The data isn't set, is expired, or we were instructed to skip the cache; we need to fetch fresh data.
		if ( true === $skip_cache || false === $data || false === $expire_timestamp || $expire_timestamp < time() ) {
			// Fetch the design assets from the cloud.
			$fetched_data = $this->cloud_api->fetch_asset('notification');
			// Bail in case of failure to retrieve data.
			// We will return the data already available.
			if ( false === $fetched_data ) {
				return $data;
			}

			$data = $fetched_data;

			// Cache the data in an option for 6 hours
			update_option( $this->get_cache_key() , $data, true );
			update_option( $this->get_cache_key() . '_timestamp' , time() + 6 * HOUR_IN_SECONDS, true );
		}

		return apply_filters( 'pixelgrade_cloud_maybe_fetch_notifications', $data );
	}

	/**
	 * Get the design assets cache key.
	 *
	 * @return string
	 */
	private function get_cache_key() {
		return 'pixelgrade_cloud_notifications';
	}

	/**
	 * Main Pixcloud_Notifications Instance
	 *
	 * Ensures only one instance of Pixcloud_Notifications is loaded or can be loaded.
	 *
	 * @static
	 *
	 * @return Pixcloud_Notifications Main Pixcloud_Notifications instance
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__,esc_html__( 'You should not do that!', 'pixelgrade_care' ), null );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ),  null );
	}
}

endif;
