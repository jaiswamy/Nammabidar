<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    PixelgradeCare
 * @subpackage PixelgradeCare/includes
 * @author     Pixelgrade <help@pixelgrade.com>
 */
class PixelgradeCare {
	/**
	 * The main plugin file.
	 * @since   1.3.0
	 * @var     string
	 * @access  public
	 */
	public $file;

	/**
	 * @var PixelgradeCare_Admin
	 */
	public $plugin_admin = null;

	/**
	 * @var PixelgradeCare_i18n
	 */
	public $plugin_i18n = null;

	/**
	 * @var PixelgradeCare_StarterContent
	 */
	public $starter_content = null;

	/**
	 * @var PixelgradeCare_Support
	 */
	public $plugin_support = null;

	/**
	 * @var PixelgradeCare_SetupWizard
	 */
	public $plugin_setup_wizard = null;

	/**
	 * @var PixelgradeCare_Club
	 */
	public $plugin_pixelgrade_club = null;

	/**
	 * @var PixelgradeCare_DataCollector
	 */
	public $plugin_data_collector = null;

	/**
	 * @var Pixcloud_Admin_Notifications
	 */
	public $pixcloud_admin_notifications = null;

	public $review_notification = null;

	/**
	 * @since    1.12.0
	 * @var PixelgradeCare_Block_Patterns
	 */
	public $plugin_block_patterns = null;

	/**
	 * @since    1.12.0
	 * @var PixelgradeCare_Portfolio
	 */
	public $plugin_cpt_portfolio = null;

	/**
	 * @since    1.12.0
	 * @var PixelgradeCare_Gallery
	 */
	public $plugin_cpt_gallery = null;

	/**
	 * @since    1.12.0
	 * @var PixelgradeCare_Testimonial
	 */
	public $plugin_cpt_testimonial = null;

	/**
	 * @since    1.12.2
	 * @var PixelgradeCare_CPT_Metafields
	 */
	public $plugin_cpt_metafields = null;

	/**
	 * @since    1.12.0
	 * @var PixelgradeCare_Conditional_Updates
	 */
	public $plugin_conditional_updates = null;

	/**
	 * @since    1.13.0
	 * @var PixelgradeCare_Sidebars
	 */
	public $plugin_sidebars = null;

	/**
	 * @since    1.17.0
	 * @var PixelgradeCare_Site_Guides
	 */
	public $plugin_site_guides = null;

	/**
	 * The only instance.
	 * @since   1.3.0
	 * @var     PixelgradeCare
	 * @access  protected
	 */
	protected static $_instance = null;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * The lowest supported WordPress version
	 * @var string
	 */
	protected $wp_support = '4.9.9';

	protected $theme_support = false;

	/**
	 * Minimal Required PHP Version
	 * @since   1.3.0
	 * @var string
	 * @access  private
	 */
	private $minimalRequiredPhpVersion = '5.3.0';

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 *
	 * @param string $file    The main plugin file
	 * @param string $version The current version of the plugin
	 */
	public function __construct( $file, $version = '1.0.0' ) {
		//the main plugin file (the one that loads all this)
		$this->file = $file;
		//the current plugin version
		$this->version = $version;

		$this->plugin_name = 'pixelgrade_care';

		// Handle the install and uninstall logic
		register_activation_hook( $this->file, [ 'PixelgradeCare', 'install' ] );
		register_deactivation_hook( $this->file, [ 'PixelgradeCare', 'uninstall' ] );

		if ( $this->php_version_check() ) {
			// Only load and run the init function if we know PHP version can parse it.
			$this->init();
		}
	}

	/**
	 * Initialize plugin
	 */
	private function init() {

		if ( $this->is_wp_compatible() ) {
			$this->load_modules();
			$this->set_locale();
			$this->register_hooks();
		} else {
			add_action( 'admin_notices', [ $this, 'add_incompatibility_notice' ] );
		}
	}

	function add_incompatibility_notice() {
		global $wp_version;

		printf(
			'<div class="%1$s"><p><strong>%2$s %3$s %4$s </strong></p><p>%5$s %6$s %7$s</p></div>',
			esc_attr( 'notice notice-error' ),
			esc_html__( 'Your Pixelgrade theme requires WordPress version', 'pixelgrade_care' ),
			$this->wp_support,
			esc_html__( 'or later', 'pixelgrade_care' ),
			esc_html__( 'You\'re using an old version of WordPress', 'pixelgrade_care' ),
			$wp_version,
			esc_html__( 'which is not compatible with the current theme. Please update to the latest version to benefit from all its features.', 'pixelgrade_care' )
		);
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - PixelgradeCare_i18n. Defines internationalization functionality.
	 * - PixelgradeCareAdmin. Defines all hooks for the admin area.
	 * - PixelgradeCare_Public. Defines all hooks for the public side of the site.
	 *
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_modules() {

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( $this->file ) . 'includes/class-pixelgrade_care-i18n.php';
		$this->plugin_i18n = PixelgradeCare_i18n::instance( $this );

		/**
		 * The class responsible for defining all logic that occurs in the admin area.
		 */
		require_once plugin_dir_path( $this->file ) . 'admin/class-pixelgrade_care-admin.php';
		$this->plugin_admin = PixelgradeCare_Admin::instance( $this );

		/**
		 * Import demo-data system
		 */
		require_once plugin_dir_path( $this->file ) . 'admin/class-pixelgrade_care-starter_content.php';
		$this->starter_content = PixelgradeCare_StarterContent::instance( $this );

		/**
		 * The class responsible for defining all actions that occur in the setup wizard.
		 */
		require_once plugin_dir_path( $this->file ) . 'admin/class-pixelgrade_care-setup_wizard.php';
		$this->plugin_setup_wizard = PixelgradeCare_SetupWizard::instance( $this );

		/**
		 * The class responsible for defining all actions that occur in the data collection section.
		 */
		require_once plugin_dir_path( $this->file ) . 'includes/class-pixelgrade_care-data-collector.php';
		$this->plugin_data_collector = PixelgradeCare_DataCollector::instance( $this );

		/**
		 * The class responsible for defining all actions that occur in support section.
		 */
		require_once plugin_dir_path( $this->file ) . 'admin/class-pixelgrade_care-support.php';
		$this->plugin_support = PixelgradeCare_Support::instance( $this );

		/**
		 * The class responsible for defining all actions that occur in the pixelgrade club section.
		 */
		require_once plugin_dir_path( $this->file ) . 'admin/class-pixelgrade_care-club.php';
		$this->plugin_pixelgrade_club = PixelgradeCare_Club::instance( $this );

		/**
		 * The class responsible for just in time (JIT) admin notifications.
		 */
		require_once plugin_dir_path( $this->file ) . 'includes/modules/admin-notifications/class-admin-notifications.php';
		$this->pixcloud_admin_notifications = Pixcloud_Admin_Notifications::instance(
			[
				'plugin_name' => 'Pixelgrade Care',
				'text_domain' => 'pixelgrade_care',
				'version'     => '',
			]
		);

		require_once plugin_dir_path( $this->file ) . 'admin/class-pixelgrade_care-review-notice.php';
		$this->review_notification = PixelgradeCare_ReviewNotification::instance( $this );

		/**
		 * The class responsible for block patterns registration.
		 */
		require_once plugin_dir_path( $this->file ) . 'includes/modules/block-patterns/class-pixelgrade_care-block-patterns.php';
		$this->plugin_block_patterns = PixelgradeCare_Block_Patterns::instance( $this );

		/**
		 * The Portfolio CPT.
		 */
		require plugin_dir_path( $this->file ) . 'includes/modules/custom-post-types/class-pixelgrade_care-portfolio.php';
		$this->plugin_cpt_portfolio = PixelgradeCare_Portfolio::instance( $this );
		// Check on plugin activation if theme supports Portfolio CPT.
		register_activation_hook( $this->file, [ 'PixelgradeCare_Portfolio', 'activation_post_type_support' ] );

		/**
		 * The Gallery CPT.
		 */
		require plugin_dir_path( $this->file ) . 'includes/modules/custom-post-types/class-pixelgrade_care-gallery.php';
		$this->plugin_cpt_gallery = PixelgradeCare_Gallery::instance( $this );
		// Check on plugin activation if theme supports Gallery CPT.
		register_activation_hook( $this->file, [ 'PixelgradeCare_Gallery', 'activation_post_type_support' ] );

		/**
		 * The Testimonial CPT.
		 */
		require plugin_dir_path( $this->file ) . 'includes/modules/custom-post-types/class-pixelgrade_care-testimonial.php';
		$this->plugin_cpt_testimonial = PixelgradeCare_Testimonial::instance( $this );
		// Check on plugin activation if theme supports Testimonial CPT.
		register_activation_hook( $this->file, [ 'PixelgradeCare_Testimonial', 'activation_post_type_support' ] );

		/**
		 * The CPTs metafields.
		 */
		require plugin_dir_path( $this->file ) . 'includes/modules/custom-post-types/metafields/core/bootstrap.php';
		require plugin_dir_path( $this->file ) . 'includes/modules/custom-post-types/metafields/class-pixelgrade_care-cpt-metafields.php';
		require plugin_dir_path( $this->file ) . 'includes/modules/custom-post-types/metafields/functions.php';
		$this->plugin_cpt_metafields = PixelgradeCare_CPT_Metafields::instance( $this );
		// Check on plugin activation if CPTs support metafields and save/update the appropriate settings.
		register_activation_hook( $this->file, [ 'PixelgradeCare_CPT_Metafields', 'activation_cpt_metafields_support' ] );
		register_activation_hook( $this->file, [ 'PixelgradeCare_CPT_Metafields', 'activation_cpt_metafields_management_support' ] );

		/**
		 * The conditional updates logic.
		 */
		require plugin_dir_path( $this->file ) . 'includes/modules/conditional-updates/class-pixelgrade_care-conditional-updates.php';
		$this->plugin_conditional_updates = PixelgradeCare_Conditional_Updates::instance( $this );

		/**
		 * The sidebars registration logic.
		 */
		require plugin_dir_path( $this->file ) . 'includes/modules/sidebars/class-pixelgrade_care-sidebars.php';
		$this->plugin_sidebars = PixelgradeCare_Sidebars::instance( $this );

		/**
		 * The site guides logic.
		 */
		require plugin_dir_path( $this->file ) . 'includes/modules/site-guides/class-pixelgrade_care-site-guides.php';
		$this->plugin_site_guides = PixelgradeCare_Site_Guides::instance( $this );

		/**
		 * Various specific integrations that have custom logic.
		 */
		require_once plugin_dir_path( $this->file ) . 'includes/integrations.php';

		/**
		 * Various functionality that helps our themes play nice with other themes and the whole ecosystem in general
		 * Think of custom post types, shortcodes, and so on. Things that are not theme territory.
		 */
		require_once plugin_dir_path( $this->file ) . 'theme-helpers.php';

		/**
		 * Load various migrations that need to run.
		 */
		require_once plugin_dir_path( $this->file ) . 'migrations/migrations.php';
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the PixelgradeCare_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		add_action( 'plugins_loaded', [ $this->plugin_i18n, 'load_plugin_textdomain' ] );
	}

	/**
	 * Register general plugin hooks.
	 * Other modules may add their own.
	 */
	public function register_hooks() {
		// Alter the theme_mods reading and saving to save separate LT themes theme_mods
		// since they all use the same Anima theme and WordPress would use the same `theme_mods_anina` DB option.
		// We use the `after_setup_theme` with a -1 priority to make sure that we do this before
		// the `pixelgrade_maybe_enable_theme_features` hook is fired.
		add_action( 'after_setup_theme', [ $this, 'alter_get_theme_mods_for_lt_themes' ], - 1 );
		add_action( 'after_setup_theme', [ $this, 'alter_style_manager_config_for_lt_themes' ], - 1 );
	}

	/**
	 * @since 1.9.3
	 *
	 * @return void
	 */
	public function alter_style_manager_config_for_lt_themes() {
		$wupdates_identification = PixelgradeCare_Admin::get_wupdates_identification_data();
		// We are only interested in premium LT Themes.
		if ( empty( $wupdates_identification['type'] ) || 'theme_lt' !== $wupdates_identification['type'] ) {
			return;
		}

		// Get the license's main product SKU.
		// This is what we will use as the actual theme slug when it comes to theme_mods.
		$license_main_product_sku = PixelgradeCare_Admin::get_license_mod_entry( 'main_product_sku' );
		if ( empty( $license_main_product_sku ) ) {
			return;
		}

		// Modify the `opt-name` entry to save Style Manager option under the proper key for LT themes,
		// since Anima will set 'anima_options' as the value.
		add_filter( 'style_manager/filter_fields', [ $this, 'handle_style_manager_config_for_lt_themes' ], 50, 1 );
	}

	/**
	 * Modify the Style Manager config for LT themes.
	 *
	 * @since 1.9.3
	 *
	 * @param $value
	 *
	 * @return void
	 */
	public function handle_style_manager_config_for_lt_themes( $config ) {
		// Best not to do anything if someone else modified this.
		if ( isset( $config['opt-name'] ) && 'anima_options' !== $config['opt-name'] ) {
			return $config;
		}

		// Get the license's main product SKU.
		// This is what we will use as the actual theme slug when it comes to theme_mods.
		$license_main_product_sku = PixelgradeCare_Admin::get_license_mod_entry( 'main_product_sku' );
		if ( empty( $license_main_product_sku ) ) {
			return $config;
		}

		$config['opt-name'] = $license_main_product_sku . '_options';

		return $config;
	}

	/**
	 * @since 1.9.3
	 *
	 * @return void
	 */
	public function alter_get_theme_mods_for_lt_themes() {
		$wupdates_identification = PixelgradeCare_Admin::get_wupdates_identification_data();
		// We are only interested in LT Themes.
		if ( empty( $wupdates_identification['type'] ) || 'theme_lt' !== $wupdates_identification['type'] ) {
			return;
		}

		// Retrieve the proper theme_mods when calling get_theme_mods().
		add_filter( 'pre_option_theme_mods_anima', [ $this, 'handle_get_theme_mods_for_lt_themes' ], 10, 1 );

		// Update the proper theme_mods when calling set_theme_mod() or remove_theme_mod().
		// We have no clean way of preventing WordPress from updating the `theme_mods_anima` entry,
		// so we let it write that also.
		add_action( 'update_option_theme_mods_anima', [ $this, 'handle_update_theme_mods_for_lt_themes' ], 10, 2 );
		// Also handle calling remove_theme_mods().
		add_action( 'delete_option_theme_mods_anima', [ $this, 'handle_remove_theme_mods_for_lt_themes' ], 10 );
	}

	/**
	 * Read the proper LT theme_mods and return it.
	 *
	 * @since 1.9.3
	 *
	 * @param $value
	 *
	 * @return void
	 */
	public function handle_get_theme_mods_for_lt_themes( $value ) {
		// Get the license's main product SKU.
		// This is what we will use as the actual theme slug when it comes to theme_mods.
		$has_cached_details = PixelgradeCare_Admin::has_cached_license_details();
		if ( ! $has_cached_details ) {
			// Because we don't have cached license details
			// we need to temporarily deactivate the theme_mods filtering to avoid infinite loops.
			remove_filter( 'pre_option_theme_mods_anima', [ $this, 'handle_get_theme_mods_for_lt_themes' ], 10 );
		}
		$license_main_product_sku = PixelgradeCare_Admin::get_license_mod_entry( 'main_product_sku' );
		if ( ! $has_cached_details ) {
			// Add the filter back.
			add_filter( 'pre_option_theme_mods_anima', [ $this, 'handle_get_theme_mods_for_lt_themes' ], 10, 1 );
		}

		if ( empty( $license_main_product_sku ) ) {
			return $value;
		}

		return get_option( "theme_mods_$license_main_product_sku" );
	}

	/**
	 * @since 1.9.3
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $value     The new option value.
	 *
	 * @return void
	 */
	public function handle_update_theme_mods_for_lt_themes( $old_value, $value ) {
		// Get the license's main product SKU.
		// This is what we will use as the actual theme slug when it comes to theme_mods.
		$license_main_product_sku = PixelgradeCare_Admin::get_license_mod_entry( 'main_product_sku' );
		if ( empty( $license_main_product_sku ) ) {
			return;
		}

		update_option( "theme_mods_$license_main_product_sku", $value, true );
	}

	/**
	 * @since 1.9.3
	 *
	 * @return void
	 */
	public function handle_remove_theme_mods_for_lt_themes() {
		// Get the license's main product SKU.
		// This is what we will use as the actual theme slug when it comes to theme_mods.
		$license_main_product_sku = PixelgradeCare_Admin::get_license_mod_entry( 'main_product_sku' );
		if ( empty( $license_main_product_sku ) ) {
			return;
		}

		delete_option( "theme_mods_$license_main_product_sku" );
	}

	/*
	 * Install everything needed
	 */
	public static function install() {

		require_once 'class-pixelgrade_care-activator.php';
		PixelgradeCareActivator::activate();
	}

	/*
	 * Uninstall everything needed
	 */
	public static function uninstall() {

		require_once 'class-pixelgrade_care-deactivator.php';
		PixelgradeCareDeactivator::deactivate();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public function get_theme_config() {
		$this->plugin_admin->get_remote_theme_config();
	}

	function is_wp_compatible() {
		global $wp_version;

		if ( version_compare( $wp_version, $this->wp_support, '>=' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Provide a useful error message when the user's PHP version is less than the required version
	 */
	public function notice_php_version_wrong() {
		$allowed = [
			'div'    => [
				'class' => [],
				'id'    => [],
			],
			'p'      => [],
			'br'     => [],
			'strong' => [],
		];
		$html    = '<div class="updated fade">' .
		           sprintf( esc_html__( 'Error: plugin "%s" requires a newer version of PHP to be running.', 'pixelgrade_care' ), $this->plugin_name ) .
		           '<br/>' . sprintf( esc_html__( 'Minimal version of PHP required: %s', 'pixelgrade_care' ), '<strong>' . $this->minimalRequiredPhpVersion . '</strong>' ) .
		           '<br/>' . sprintf( esc_html__( 'Your server\'s PHP version: %s', 'pixelgrade_care' ), '<strong>' . phpversion() . '</strong>' ) .
		           '</div>';
		echo wp_kses( $html, $allowed );
	}


	/**
	 * PHP version check
	 */
	protected function php_version_check() {

		if ( version_compare( phpversion(), $this->minimalRequiredPhpVersion ) < 0 ) {
			add_action( 'admin_notices', [ $this, 'notice_php_version_wrong' ] );

			return false;
		}

		return true;
	}

	/**
	 * Main PixelgradeCare Instance
	 *
	 * Ensures only one instance of PixelgradeCare is loaded or can be loaded.
	 *
	 * @since  1.3.0
	 * @static
	 * @see    PixelgradeCare()
	 *
	 * @param string $version Version.
	 *
	 * @param string $file    File.
	 *
	 * @return PixelgradeCare Main PixelgradeCare instance
	 */
	public static function instance( $file = '', $version = '1.0.0' ) {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), esc_html( $this->version ) );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.3.0
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), esc_html( $this->version ) );
	}
}
