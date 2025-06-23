<?php
/**
 * This is the class that handles the overall logic for fetching remote site guides data.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PixelgradeCare_Remote_Site_Guides' ) ) :

	class PixelgradeCare_Remote_Site_Guides {

		/**
		 * Holds the only instance of this class.
		 * @since  1.17.0
		 * @var null|PixelgradeCare_Remote_Site_Guides
		 * @access protected
		 */
		protected static $_instance = null;

		/**
		 * The current site guides data/config.
		 * @since   1.17.0
		 * @var     array
		 * @access  public
		 */
		protected $data = null;

		/**
		 * The cloud API object used to communicate with the cloud.
		 * @since   1.17.0
		 * @var     PixelgradeCare_Cloud_Api
		 * @access  public
		 *
		 */
		protected $cloud_api = null;

		/**
		 * Constructor.
		 * @since 1.17.0
		 */
		private function __construct() {
			$this->init();
		}

		/**
		 * Initialize this module.
		 * @since 1.17.0
		 */
		public function init() {
			/**
			 * Initialize the Cloud API logic.
			 */
			$this->cloud_api = PixelgradeCare_Cloud_Api::instance();
		}

		/**
		 * Get the site guides data/configuration.
		 * @since 1.17.0
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

			return apply_filters( 'pixelgrade_care/site_guides/get_remote', $this->data );
		}

		/**
		 * Fetch the site guides data from the Pixelgrade Cloud.
		 *
		 * Caches the data for 6 hours.
		 * @since 1.17.0
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
				return $data;
			}

			// Get the cache data expiration timestamp.
			$expire_timestamp = get_option( $this->get_cache_key() . '_timestamp' );

			// We don't force skip the cache for AJAX requests for performance reasons.
			if ( ! wp_doing_ajax() && defined( 'PIXELGRADE_CARE__SKIP_INTERNAL_CACHE' ) && true === PIXELGRADE_CARE__SKIP_INTERNAL_CACHE ) {
				$skip_cache = true;
			}

			// The data isn't set, is expired, or we were instructed to skip the cache; we need to fetch fresh data.
			if ( true === $skip_cache || false === $data || false === $expire_timestamp || $expire_timestamp < time() ) {
				// Fetch the site guides from the cloud.
				$fetched_data = $this->cloud_api->fetch_asset( 'site_guide' );
				// Bail in case of failure to retrieve data.
				// We will return the data already available.
				if ( false === $fetched_data ) {
					return $data;
				}

				$data = $fetched_data;

				// Cache the data in an option for 6 hours
				update_option( $this->get_cache_key(), $data, true );
				update_option( $this->get_cache_key() . '_timestamp', time() + 6 * HOUR_IN_SECONDS, true );
			}

			return apply_filters( 'pixelgrade_cloud/site_guides/maybe_fetch_remote', $data );
		}

		/**
		 * Get the site guides cache key.
		 * @since 1.17.0
		 * @return string
		 */
		private function get_cache_key() {
			return 'pixelgrade_cloud_remote_site_guides';
		}

		/**
		 * Main PixelgradeCare_Remote_Site_Guides Instance
		 *
		 * Ensures only one instance of PixelgradeCare_Remote_Site_Guides is loaded or can be loaded.
		 * @since 1.17.0
		 * @static
		 *
		 * @return PixelgradeCare_Remote_Site_Guides Main PixelgradeCare_Remote_Site_Guides instance
		 */
		public static function instance() {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Cloning is forbidden.
		 * @since 1.17.0
		 */
		public function __clone() {

			_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), null );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 * @since 1.17.0
		 */
		public function __wakeup() {

			_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), null );
		}
	}

endif;
