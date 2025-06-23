<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin-specific experience improvements, unrelated to our offerings.
 *
 *
 * @package    PixelgradeCare
 * @subpackage PixelgradeCare/admin
 * @author     Pixelgrade
 */
class PixelgradeCare_AdminExperience {

	/**
	 * The main plugin object (the parent).
	 * @var     PixelgradeCare
	 * @access  public
	 * @since     1.5.3
	 */
	public $parent = null;

	/**
	 * The only instance.
	 * @var     PixelgradeCare_AdminExperience
	 * @access  protected
	 * @since   1.5.3
	 */
	protected static $_instance = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param $parent
	 *
	 * @since    1.5.3
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		add_action( 'after_setup_theme', [ $this, 'init' ] );
	}

	/**
	 * Initialize this module.
	 */
	public function init() {
		// Allow others to disable this.
		if ( false === apply_filters( 'pixcare_allow_admin_experience_module', true ) ) {
			return;
		}

		$this->register_hooks();
	}

	/**
	 * Register the hooks related to this module.
	 */
	public function register_hooks() {

		// Remove WooCommerce Marketplace Suggestions.
		add_filter( 'woocommerce_allow_marketplace_suggestions', '__return_false' );

		// Show only installed themes in the WooCommerce onboarding.
		add_filter( 'woocommerce_admin_onboarding_themes', [ $this, 'change_woo_admin_onboarding_themes' ], 999, 1 );
	}

	public function change_woo_admin_onboarding_themes( $themes ) {
		if ( ! class_exists( '\Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingThemes' ) ) {
			return $themes;
		}

		$themes = [];

		$installed_themes = wp_get_themes();
		$active_theme     = get_option( 'stylesheet' );

		foreach ( $installed_themes as $slug => $theme ) {
			$theme_data       = \Automattic\WooCommerce\Internal\Admin\Onboarding\OnboardingThemes::get_theme_data( $theme );
			if ( false !== strpos( $theme->get('ThemeURI'), 'pixelgrade.com' ) ) {
				// This is one of our premium themes.
				$theme_data['price']                   = '125.00'; // bogus price since it won't be show; only to mark as paid.
				$theme_data['has_woocommerce_support'] = true;
			}

			$themes[ $slug ]  = $theme_data;
		}

		$themes = [ $active_theme => $themes[ $active_theme ] ] + $themes;

		return $themes;
	}

	/**
	 * Main PixelgradeCare_AdminExperience Instance
	 *
	 * Ensures only one instance of PixelgradeCare_AdminExperience is loaded or can be loaded.
	 *
	 * @since  1.5.3
	 * @static
	 * @param  object $parent Main PixelgradeCare instance.
	 * @return object Main PixelgradeCare_AdminExperience instance
	 */
	public static function instance( $parent ) {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance().

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.5.3
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), esc_html( $this->parent->get_version() ) );
	} // End __clone().

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.5.3
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), esc_html( $this->parent->get_version() ) );
	} // End __wakeup().
}
