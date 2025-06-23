<?php
/**
 * Various functionality that enhances the Customizer experience.
 *
 * @link       https://pixelgrade.com
 * @since      1.4.5
 *
 * @package    PixelgradeCare
 * @subpackage PixelgradeCare/ThemeHelpers
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class PixelgradeCare_Customizer_Helper {

	/**
	 * Version used for cache-busting of style and script file enqueues.
	 * @since   1.4.5
	 * @const   string
	 */
	protected $_version = '1.0.0';

	/**
	 * Instance of this class.
	 * @since    1.4.5
	 * @var      object
	 */
	protected static $_instance = null;

	/**
	 * List of the tag names seen for before_widget strings.
	 *
	 * This is used in the {@see 'filter_wp_kses_allowed_html'} filter to ensure that the
	 * data-* attributes can be whitelisted.
	 *
	 * @since 1.4.5
	 * @var array
	 */
	protected $before_widget_tags_seen = [];

	protected function __construct() {
		$this->init();
	}

	/**
	 * Initialize class
	 */
	private function init() {
		// Register all the needed hooks
		$this->register_hooks();
	}

	/**
	 * Register our actions and filters
	 *
	 * @return void
	 */
	function register_hooks() {
		// Styles for the Customizer controls
		add_action( 'customize_controls_init', [ $this, 'register_styles' ], 10 );
		add_action( 'customize_controls_enqueue_scripts', [ $this, 'enqueue_styles' ], 10 );
		// Scripts for the Customizer controls
		add_action( 'customize_controls_init', [ $this, 'register_scripts' ], 10 );
		add_action( 'customize_controls_enqueue_scripts', [ $this, 'enqueue_scripts' ], 10 );

		// Styles enqueued in the Customizer only in the theme preview
		add_action( 'customize_preview_init', [ $this, 'register_live_preview_styles' ], 10 );
		add_action( 'customize_preview_init', [ $this, 'enqueue_live_preview_styles' ], 99999 );

		// Scripts enqueued in the Customizer only in the theme preview
		add_action( 'customize_preview_init', [ $this, 'register_live_preview_scripts' ], 10 );
		add_action( 'customize_preview_init', [ $this, 'enqueue_live_preview_scripts' ], 99999 );

		// Handle stuff related to selective refresh (partial refresh)
		add_action( 'customize_preview_init', [ $this, 'selective_refresh_init' ] );
	}

	/**
	 * Register styles for the Customizer controls
	 */
	function register_styles() {
		wp_register_style( 'pixelgrade_care_customizer_helper', plugins_url( 'customizer-helper/css/customizer.css', __FILE__ ), [], $this->_version );
	}

	/**
	 * Enqueue Customizer controls styles
	 */
	function enqueue_styles() {
		wp_enqueue_style( 'pixelgrade_care_customizer_helper' );
	}

	/**
	 * Register Customizer controls scripts
	 */
	function register_scripts() {
		wp_register_script( 'pixelgrade_care_customizer_helper', plugins_url( 'customizer-helper/js/customizer.js', __FILE__ ), [
			'jquery',
			'underscore',
			'customize-controls'
		], $this->_version );
	}

	/**
	 * Enqueue Customizer controls scripts
	 */
	function enqueue_scripts() {
		wp_enqueue_script( 'pixelgrade_care_customizer_helper' );
	}

	/**
	 * Register styles for the Customizer controls
	 */
	function register_live_preview_styles() {
		wp_register_style( 'pixelgrade_care_customizer_helper_preview', plugins_url( 'customizer-helper/css/customizer-preview.css', __FILE__ ), [], $this->_version );
	}

	/**
	 * Enqueue Customizer controls styles
	 */
	function enqueue_live_preview_styles() {
		wp_enqueue_style( 'pixelgrade_care_customizer_helper_preview' );
	}

	/**
	 * Register Customizer scripts loaded only in the live preview frame
	 */
	function register_live_preview_scripts() {
		wp_register_script( 'pixelgrade_care_customizer_helper_preview', plugins_url( 'customizer-helper/js/customizer-preview.js', __FILE__ ), [
			'jquery',
			'customize-preview',
		], $this->_version, true );
	}

	/**
	 * Enqueue Customizer scripts loaded only in the live preview frame
	 */
	function enqueue_live_preview_scripts() {
		wp_enqueue_script( 'pixelgrade_care_customizer_helper_preview' );
	}

	/**
	 * Adds hooks for selective refresh.
	 */
	public function selective_refresh_init() {
		if ( ! current_theme_supports( 'customize-selective-refresh-widgets' ) ) {
			return;
		}
		add_filter( 'dynamic_sidebar_params', [ $this, 'filter_dynamic_sidebar_params' ] );
		add_filter( 'wp_kses_allowed_html', [ $this, 'filter_wp_kses_allowed_data_attributes' ] );
	}

	/**
	 * Inject selective refresh data attributes into widget container elements.
	 *
	 * @since    1.4.5
	 *
	 * @param array $params {
	 *     Dynamic sidebar params.
	 *
	 *     @type array $args        Sidebar args.
	 *     @type array $widget_args Widget args.
	 * }
	 * @see WP_Customize_Nav_Menus_Partial_Refresh::filter_wp_nav_menu_args()
	 *
	 * @return array Params.
	 */
	public function filter_dynamic_sidebar_params( $params ) {
		$sidebar_args = array_merge(
			[
				'before_widget' => '',
				'after_widget' => '',
			],
			$params[0]
		);

		// Skip widgets not in a registered sidebar or ones which lack a proper wrapper element to attach the data-* attributes to.
		$matches = [];
		$is_valid = (
			isset( $sidebar_args['id'] )
			&&
			is_registered_sidebar( $sidebar_args['id'] )
			&&
			preg_match( '#^<(?P<tag_name>\w+)#', $sidebar_args['before_widget'], $matches )
		);
		if ( ! $is_valid ) {
			return $params;
		}
		$this->before_widget_tags_seen[ $matches['tag_name'] ] = true;

		$attributes = sprintf( ' data-customize-widget-name="%s"', esc_attr( $sidebar_args['widget_name'] ) );
		$sidebar_args['before_widget'] = preg_replace( '#^(<\w+)#', '$1 ' . $attributes, $sidebar_args['before_widget'] );

		$params[0] = $sidebar_args;
		return $params;
	}

	/**
	 * Ensures the HTML data-* attributes for selective refresh are allowed by kses.
	 *
	 * This is needed in case the `$before_widget` is run through wp_kses() when printed.
	 *
	 * @since 1.4.5
	 *
	 * @param array $allowed_html Allowed HTML.
	 * @return array (Maybe) modified allowed HTML.
	 */
	public function filter_wp_kses_allowed_data_attributes( $allowed_html ) {
		foreach ( array_keys( $this->before_widget_tags_seen ) as $tag_name ) {
			if ( ! isset( $allowed_html[ $tag_name ] ) ) {
				$allowed_html[ $tag_name ] = [];
			}
			$allowed_html[ $tag_name ] = array_merge(
				$allowed_html[ $tag_name ],
				array_fill_keys( [
					'data-customize-partial-widget-name',
				], true )
			);
		}
		return $allowed_html;
	}

	/**
	 * Main PixelgradeCare_Customizer_Helper Instance
	 *
	 * Ensures only one instance of PixelgradeCare_Customizer_Helper is loaded or can be loaded.
	 *
	 * @since    1.4.5
	 * @static
	 *
	 * @return object Main PixelgradeCare_Customizer_Helper instance
	 */
	public static function instance() {
		// If the single instance hasn't been set, set it now.
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since    1.4.5
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), '' );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since    1.4.5
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), '' );
	} // End __wakeup ()
}

$pixelgrade_care_customizer_helper = PixelgradeCare_Customizer_Helper::instance();
