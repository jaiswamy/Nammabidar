<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    PixelgradeCare
 * @subpackage PixelgradeCare/admin
 * @author     Pixelgrade <help@pixelgrade.com>
 */
class PixelgradeCare_Support {

	/**
	 * The main plugin object (the parent).
	 * @var     PixelgradeCare
	 * @access  public
	 * @since     1.3.0
	 */
	public $parent = null;

	/**
	 * The only instance.
	 * @var     PixelgradeCare_Admin
	 * @access  protected
	 * @since   1.3.0
	 */
	protected static $_instance = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Initialize this module.
	 */
	public function init() {
		// Allow others to disable this module.
		if ( false === apply_filters( 'pixcare_allow_support_module', true ) ) {
			return;
		}

		$this->register_hooks();
	}

	/**
	 * Register the hooks related to this module.
	 */
	public function register_hooks() {
		add_action( 'admin_footer', [ $this, 'support_setup' ] );
		add_action( 'admin_footer', [ $this, 'support_content' ] );
		add_action( 'customize_controls_enqueue_scripts', [ $this, 'support_setup' ] );
		add_action( 'customize_controls_print_scripts', [ $this, 'support_content' ] );

		// Handle special cases where we will not load the support module.
		add_filter( 'pixcare_allow_support_module', [ $this, 'disable_module_in_special_cases' ] );
	}

	public function support_setup() {
		// We don't show the Theme Help button and overlay if the current user can't manage options
		// or if we are in the network admin sections on a multisite installation.
		// We also want to avoid loading a big chunk of JS on an already loaded page like the block editor.
		$allow_support = current_user_can( 'manage_options' ) && ! is_network_admin() && ! pixelgrade_is_block_editor();
		if ( false === apply_filters( 'pixcare_allow_support_module', $allow_support ) ) {
			return;
		}

		$rtl_suffix = is_rtl() ? '-rtl' : '';
		wp_enqueue_style( $this->parent->get_plugin_name(), plugin_dir_url( $this->parent->file ) . 'admin/css/pixelgrade_care-admin' . $rtl_suffix . '.css', [], $this->parent->get_version(), 'all' );

		wp_register_script( $this->parent->get_plugin_name() . '-awssdk-js', plugin_dir_url( $this->parent->file ) . 'admin/js/vendor/aws-sdk-2.643.0.min.js' );
		wp_register_script( $this->parent->get_plugin_name() . '-es-js', plugin_dir_url( $this->parent->file ) . 'admin/js/vendor/elasticsearch.min.js', [], '15.5.0' );
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_enqueue_script( $this->parent->get_plugin_name() . '-support', plugin_dir_url( $this->parent->file ) . 'admin/js/support' . $suffix . '.js', [
			'jquery',
			'wp-util',
			'wp-a11y',
			'updates',
			$this->parent->get_plugin_name() . '-es-js',
			$this->parent->get_plugin_name() . '-awssdk-js',
		], $this->parent->get_version(), true );

		if ( ! wp_script_is('pixelgrade_care-dashboard') ) {
			PixelgradeCare_Admin::localize_js_data( $this->parent->get_plugin_name() . '-support', true, 'support' );
		}

		// We need to remove lodash from the global scope
		// since it overwrites Underscores and everything breaks in the Customizer.
		$lodash_noconflict = '
			window.lodash = _.noConflict();
		';
		wp_add_inline_script( $this->parent->get_plugin_name() . '-es-js', $lodash_noconflict );
	}

	/**
	 * Handle special cases where for better user experience we will not allow the support module.
	 *
	 * Cases like plugins that introduce buttons where our Theme Support button is (e.g. Press This).
	 *
	 * @param bool $allow_support
	 *
	 * @return bool
	 */
	public function disable_module_in_special_cases( $allow_support ) {
		// We may not always have access to get_current_screen().
		if ( function_exists( 'get_current_screen' ) ) {
			$current_screen = get_current_screen();
		}

		if ( ! empty( $current_screen ) ) {
			// If we are on a Press This page, don't allow the module since the Save button is exactly in the same place.
			if ( false !== strpos( $current_screen->parent_file, 'press-this.php' ) || 'press-this' === $current_screen->base ) {
				return false;
			}
		}

		return $allow_support;
	}

	/**
	 * Output the content for the current step.
	 */
	public function support_content() {
		if ( ! current_user_can( 'manage_options' ) || is_network_admin() ) {
			return;
		} ?>
		<div id="pixelgrade_care_support_section"></div>
	<?php
	}

	protected static function get_kb_cache_key() {
		return 'pixcare_support_' . PixelgradeCare_Admin::get_original_theme_slug() . '_kb';
	}

	/**
	 * Retrieve the KnowledgeBase data from the server.
	 *
	 * @return array|mixed
	 */
	public static function get_knowledgeBase_data( $skip_cache = false ) {
		$data = false;

		if ( defined( 'PIXELGRADE_CARE__SKIP_INTERNAL_CACHE' ) && PIXELGRADE_CARE__SKIP_INTERNAL_CACHE === true ) {
			$skip_cache = true;
		}

		// First try and get the cached data.
		if ( ! $skip_cache ) {
			$data = get_site_transient( self::get_kb_cache_key() );
		}

		// The transient isn't set, is expired, or we're supposed to skip the cache; we need to fetch fresh data.
		if ( false === $data ) {
			$data = [
				'categories' => self::fetch_kb_categories(),
			];

			// Sanitize it.
			$data = PixelgradeCare_Admin::sanitize_theme_mods_holding_content( $data, [] );

			// Cache the data in a transient for 12 hours.
			set_site_transient( self::get_kb_cache_key(), $data, 12 * HOUR_IN_SECONDS );
		}

		// Standardize it a bit.
		if ( empty( $data ) ) {
			$data = [];
		}
		if ( empty( $data['categories'] ) ) {
			$data['categories'] = [];
		}

		return $data;
	}

	/**
	 * Delete the cached KnowledgeBase data from the server.
	 *
	 * @return bool
	 */
	public static function clear_knowledgeBase_data_cache() {
		return delete_site_transient( self::get_kb_cache_key() );
	}

	/**
	 * Retrieve the KnowledgeBase categories from the server.
	 *
	 * @return array
	 */
	public static function fetch_kb_categories() {
		// Get existing categories.
		$request_args = [
			'method' => PixelgradeCare_Admin::$externalApiEndpoints['pxm']['getHTKBCategories']['method'],
			'timeout' => 5,
			'sslverify' => false, // There is no need to verify the SSL certificate - this is not sensitive data.
		];
		// Add the slug of the theme to the request args so we will only receive data for the current theme.
		$request_args['body']['kb_current_product_sku'] = PixelgradeCare_Admin::get_original_theme_slug();
		$request_args['body']['hash_id'] = PixelgradeCare_Admin::get_theme_hash_id();
		$request_args['body']['type'] = PixelgradeCare_Admin::get_theme_type();

		// Increase timeout if the target URL is a development one so we can account for slow local (development) installations.
		if ( PixelgradeCare_Admin::is_development_url( PixelgradeCare_Admin::$externalApiEndpoints['pxm']['getHTKBCategories']['url'] ) ) {
			$request_args['timeout'] = 10;
		}

		$categories = wp_remote_request( PixelgradeCare_Admin::$externalApiEndpoints['pxm']['getHTKBCategories']['url'], $request_args );

		if ( is_wp_error( $categories ) ) {
			return [];
		}
		$response = json_decode( wp_remote_retrieve_body( $categories ), true );

		$parsed_categories = [];
		if ( isset($response['code'] ) && $response['code'] == 'success' && isset( $response['data'] ) ) {
			$parsed_categories = $response['data']['htkb_categories'];
		}
		return $parsed_categories;
	}

	/**
	 * Main PixelgradeCare_Support Instance.
	 *
	 * Ensures only one instance of PixelgradeCare_Support is loaded or can be loaded.
	 *
	 * @since  1.3.0
	 * @static
	 * @param  object $parent Main PixelgradeCare instance.
	 * @return object Main PixelgradeCare_Support instance
	 */
	public static function instance( $parent ) {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), esc_html( $this->parent->get_version() ) );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), esc_html( $this->parent->get_version() ) );
	}
}
