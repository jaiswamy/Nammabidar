<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    PixelgradeCare
 * @subpackage PixelgradeCare/admin
 * @author     Pixelgrade <help@pixelgrade.com>
 */
class PixelgradeCare_Admin {
	/**
	 * The main plugin object (the parent).
	 * @since     1.3.0
	 * @var     PixelgradeCare
	 */
	public $parent = null;

	/**
	 * The config for the active theme.
	 * If this is false it means the current theme hasn't declared support for pixelgrade_care.
	 *
	 * @since    1.0.0
	 * @var      array|boolean    $theme_support
	 */
	public static $theme_support;

	/**
	 * The plugin's options
	 *
	 * @var array
	 */
	protected static $options = null;

	/**
	 * The option key where we store the plugin's options.
	 *
	 * @var string
	 */
	protected static $options_key = 'pixcare_options';

	/**
	 * The WordPress API nonce.
	 *
	 * @var string
	 */
	protected $wp_nonce;

	/**
	 * Our extra API nonce.
	 * @var string
	 */
	protected $pixcare_nonce;

	/**
	 * Admin REST controller class object
	 *
	 * @var PixelgradeCare_AdminRestInterface
	 * @access  protected
	 */
	protected $rest_controller = null;

	/**
	 * Admin experience class object
	 *
	 * @var PixelgradeCare_AdminExperience
	 * @access  protected
	 */
	protected $admin_experience = null;

	/**
	 * Required plugins class object
	 *
	 * @var PixelgradeCare_RequiredPlugins
	 * @access  protected
	 */
	protected $required_plugins = null;

	/**
	 * The Pixelgrade Care Manager API version we currently use.
	 *
	 * @var string
	 */
	protected static $pixelgrade_care_manager_api_version = 'v2';

	/**
	 * Internal REST API endpoints used for housekeeping.
	 * @since    1.3.7
	 * @var array
	 * @access   public
	 */
	public static $internalApiEndpoints;

	/**
	 * External REST API endpoints used for communicating with the shop.
	 * @since    1.3.7
	 * @var array
	 * @access   public
	 */
	public static $externalApiEndpoints;

	/**
	 * Cache for the wupdates identification data to avoid firing the filter multiple times.
	 * @since    1.5.0
	 * @var array
	 * @access   protected
	 */
	protected static $wupdates_ids = [];

	/**
	 * Cache for the current license details.
	 *
	 * We need this to avoid infinite loops when dealing with altering the theme_mods DB entry for LT themes.
	 *
	 * @since    1.9.3
	 *
	 * @var array
	 * @access   protected
	 */
	protected static $license_details = null;

	/**
	 * Cache for the current theme config.
	 *
	 * @since    1.9.3
	 *
	 * @var array
	 * @access   protected
	 */
	protected static $theme_config = null;

	/**
	 * The only instance.
	 * @since   1.3.0
	 * @var     PixelgradeCare_Admin
	 * @access  protected
	 */
	protected static $_instance = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param $parent
	 *
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		// We need priority 1 here so it can come before the Classic Editor that is hooked at priority 2 (our modification).
		add_action( 'after_setup_theme', [ $this, 'init' ], 1 );

		// Initialize the REST API admin endpoints
		require_once plugin_dir_path( $this->parent->file ) . 'admin/class-pixelgrade_care-admin_rest_interface.php';
		$this->rest_controller = new PixelgradeCare_AdminRestInterface();

		// Logic to enhance the admin experience.
		require_once plugin_dir_path( $this->parent->file ) . 'admin/class-pixelgrade_care-admin_experience.php';
		$this->admin_experience = PixelgradeCare_AdminExperience::instance( $this );

		// Logic to handle required plugins.
		require_once plugin_dir_path( $this->parent->file ) . 'includes/modules/required-plugins/class-pixelgrade_care-required_plugins.php';
		$this->required_plugins = PixelgradeCare_RequiredPlugins::instance( $this->parent );

		// Register the admin REST API routes
		add_action( 'rest_api_init', [ $this->rest_controller, 'register_routes' ] );

		// Make sure the Gutenberg vs Classic Editor logic is loaded.
		require_once plugin_dir_path( $this->parent->file ) . 'vendor/classic-editor/classic-editor.php';

		// Fill up the WUpdates identification data for missing entities that we can deduce through other means.
		// This mostly addresses WordPress.org themes that don't have the WUpdates identification data.
		// This needs to be hooked up this early since we can't know for sure when the filter will be fired.
		add_filter( 'wupdates_gather_ids', [
			'PixelgradeCare_Admin',
			'maybe_fill_up_wupdates_identification_data',
		], 1000, 1 );
	}

	/**
	 * Initialize our class.
	 */
	public function init() {
		$this->wp_nonce      = wp_create_nonce( 'wp_rest' );
		$this->pixcare_nonce = wp_create_nonce( 'pixelgrade_care_rest' );

		// Save the internal API endpoints in an easy to get property.
		self::$internalApiEndpoints = apply_filters( 'pixcare_internal_api_endpoints', [
			'globalState' => [
				'get' => [
					'method' => 'GET',
					'url'    => esc_url_raw( rest_url() . 'pixcare/v1/global_state' ),
				],
				'set' => [
					'method' => 'POST',
					'url'    => esc_url_raw( rest_url() . 'pixcare/v1/global_state' ),
				],
			],
			'localized'   => [
				'get' => [
					'method' => 'GET',
					'url'    => esc_url_raw( rest_url() . 'pixcare/v1/localized' ),
				],
			],

			'cleanup'             => [
				'method' => 'POST',
				'url'    => esc_url_raw( rest_url() . 'pixcare/v1/cleanup' ),
			],
			'disconnect'      => [
				'method' => 'POST',
				'url'    => esc_url_raw( rest_url() . 'pixcare/v1/disconnect' ),
			],

			// Installing and activating themes.
			'installTheme'        => [
				'method' => 'POST',
				'url'    => esc_url_raw( rest_url() . 'pixcare/v1/install_theme' ),
			],
			'activateTheme'       => [
				'method' => 'POST',
				'url'    => esc_url_raw( rest_url() . 'pixcare/v1/activate_theme' ),
			],
			'refreshThemeLicense' => [
				'method' => 'POST',
				'url'    => esc_url_raw( rest_url() . 'pixcare/v1/refresh_theme_license' ),
			],

			// Starter content needed endpoints.
			'import'              => [
				'method' => 'POST',
				'url'    => esc_url_raw( rest_url() . 'pixcare/v1/import' ),
			],
			'uploadMedia'         => [
				'method' => 'POST',
				'url'    => esc_url_raw( rest_url() . 'pixcare/v1/upload_media' ),
			],

			// WUpdates and Pixelgrade.com needed endpoints.
			'updateLicense'       => [
				'method' => 'POST',
				'url'    => esc_url_raw( rest_url() . 'pixcare/v1/update_license' ),
			],
			'dataCollect'         => [
				'get' => [
					'method' => 'GET',
					'url'    => esc_url_raw( rest_url() . 'pixcare/v1/data_collect' ),
				],
				'set' => [
					'method' => 'POST',
					'url'    => esc_url_raw( rest_url() . 'pixcare/v1/data_collect' ),
				],
			],
			'licenseInfo'         => [
				'method' => 'GET',
				'url'    => esc_url_raw( rest_url() . 'pixcare/v1/license_info' ),
			],
		] );

		// Save the external API endpoints in a easy to get property.
		self::$externalApiEndpoints = apply_filters( 'pixcare_external_api_endpoints', [
			'pxm'  => [
				'getConfig'         => [
					'method' => 'GET',
					'url'    => PIXELGRADE_CARE__API_BASE . 'wp-json/pxm/v2/front/get_config',
				],
				'createTicket'      => [
					'method' => 'POST',
					'url'    => PIXELGRADE_CARE__API_BASE . 'wp-json/pxm/v2/front/create_ticket',
				],
				'demoContent'       => [
					'method' => 'GET',
					'url'    => PIXELGRADE_CARE__API_BASE . 'wp-json/pxm/v2/front/get_demo_content',
				],
				'getHTKBCategories' => [
					'method' => 'GET',
					'url'    => PIXELGRADE_CARE__API_BASE . 'wp-json/pxm/v2/front/get_htkb_categories',
				],
				'htVoting'          => [
					'method' => 'POST',
					'url'    => PIXELGRADE_CARE__API_BASE . 'wp-json/pxm/v2/front/ht_voting',
				],
				'htVotingFeedback'  => [
					'method' => 'POST',
					'url'    => PIXELGRADE_CARE__API_BASE . 'wp-json/pxm/v2/front/ht_voting_feedback',
				],
				'htViews'           => [
					'method' => 'POST',
					'url'    => PIXELGRADE_CARE__API_BASE . 'wp-json/pxm/v2/front/ht_views',
				],
			],
			'wupl' => [
				'customerProducts' => [
					'method' => 'POST',
					'url'    => PIXELGRADE_CARE__API_BASE . 'wp-json/wupl/v2/front/get_customer_products',
				],
				'licenses'         => [
					'method' => 'POST',
					'url'    => PIXELGRADE_CARE__API_BASE . 'wp-json/wupl/v2/front/get_licenses',
				],
				'licenseAction'    => [
					'method' => 'POST',
					'url'    => PIXELGRADE_CARE__API_BASE . 'wp-json/wupl/v2/front/license_action',
				],
				'licenseProducts'  => [
					'method' => 'POST',
					'url'    => PIXELGRADE_CARE__API_BASE . 'wp-json/wupl/v2/front/get_license_products',
				],
			],
		] );

		$this->register_hooks();
	}

	/**
	 * Register the hooks related to this module.
	 */
	public function register_hooks() {
		if ( ! wp_doing_ajax() ) {
			add_action( 'admin_init', [ 'PixelgradeCare_Admin', 'set_theme_support' ], 11 );
		}

		add_action( 'admin_init', [ $this, 'admin_redirects' ], 15 );
		add_filter( 'wupdates_call_data_request', [ $this, 'add_license_to_wupdates_data' ], 10, 2 );
		add_filter( 'pre_set_site_transient_update_themes', [ $this, 'force_updates_through_wupdates' ], 2, 1 );
		add_filter( 'pre_set_site_transient_update_themes', [ $this, 'check_if_update_is_valid' ], 999, 1 );
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );

		if ( ! wp_doing_ajax() ) {
			add_action( 'admin_menu', [ $this, 'add_pixelgrade_care_menu' ] );
		}

		add_action( 'admin_init', [ $this, 'settings_init' ] );

		add_action( 'current_screen', [ $this, 'add_tabs' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// We will remember the theme version when the transient is updated.
		add_filter( 'pre_set_site_transient_update_themes', [
			$this,
			'transient_update_theme_version',
		], 11 );
		// We will remove the info when the transient is deleted (maybe after a successful update?).
		add_action( 'delete_site_transient_update_themes', [
			$this,
			'transient_remove_theme_version',
		], 10 );
		add_filter( 'pre_set_site_transient_update_themes', [
			$this,
			'transient_update_remote_config',
		], 12 );
		add_filter( 'pre_set_site_transient_update_themes', [
			$this,
			'transient_maybe_cleanup_oauth_token',
		], 14 );
		add_filter( 'pre_set_site_transient_update_themes', [
			$this,
			'transient_update_license_data',
		], 15 );
		// Hook to update the Pixelgrade themes a customer has access to.
		add_filter( 'pre_set_site_transient_update_themes', [
			$this,
			'transient_update_customer_products',
		], 20 );

		// The first things we will do after_theme_switch is to handle license mods between parent and child themes.
		add_action( 'after_switch_theme', [ 'PixelgradeCare_Admin', 'handle_license_mods_on_theme_switch' ], 10, 2 );

		// On theme switch clear the cache for the remote config.
		add_action( 'after_switch_theme', [ 'PixelgradeCare_Admin', 'clear_remote_config_cache' ], 11 );

		// Also, on theme switch refresh the products the connected user has access to.
		add_action( 'after_switch_theme', [ 'PixelgradeCare_Admin', 'update_customer_products' ], 15 );

		// Also, on theme switch remember this, so we can take action somewhere (like in JS).
		add_action( 'after_switch_theme', [ 'PixelgradeCare_Admin', 'remember_theme_switch' ], 20 );

		// Also, on theme switch try and get a license and activate it, if the user is connected.
		add_action( 'after_switch_theme', [ 'PixelgradeCare_Admin', 'do_fetch_and_activate_theme_license' ], 30 );

		// Auto-update Pixelgrade Care by default.
		add_filter( 'auto_update_plugin', [ $this, 'handle_plugin_autoupdate' ], 10, 2 );
		// Auto-update Anima theme by default.
		// Not for now!
		// add_filter( 'auto_update_theme', [ $this, 'handle_theme_autoupdate' ], 10, 2 );

		/**
		 * LT Themes Related
		 */

		// Modify Appearance > Themes data to better represent the LT "theme" active.
		add_filter( 'wp_prepare_themes_for_js', [ $this, 'alter_themes_data_for_list_table' ], 99, 1 );

		// Modify At a Glance widget text to better represent the LT "theme" active.
		add_filter( 'update_right_now_text', [ $this, 'alter_update_right_now_text' ], 99, 1 );
	}

	/**
	 * The first access to PixCare needs to be redirected to the setup wizard.
	 */
	function admin_redirects() {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$plugin_version     = get_option( 'pixelgrade_care_version' );
		$redirect_transient = get_site_transient( '_pixcare_activation_redirect' );

		if ( false !== $redirect_transient || empty( $plugin_version ) ) {
			// Yay! This is a fresh install and we are not on a setup page, just go there already.
			wp_redirect( PixelgradeCare_SetupWizard::get_setup_wizard_url() );
			exit;
		}

		// If the user that is installing Pixelgrade Care is a member of Pixelgrade Club (has been given the plugin and no theme)
		// check if the plugin version is empty and has no other Pixelgrade theme installed.
		if ( empty( $plugin_version ) && ! self::has_pixelgrade_theme() ) {
			wp_redirect( PixelgradeCare_SetupWizard::get_setup_wizard_url() );
			exit;
		}
	}

	/**
	 * Determine if there are any Pixelgrade themes currently installed.
	 *
	 * @return bool
	 */
	public static function has_pixelgrade_theme() {
		$themes = wp_get_themes();
		// Loop through the themes.
		// If we find a theme from pixelgrade return true.
		/** @var WP_Theme $theme */
		foreach ( $themes as $theme ) {
			$theme_author = $theme->get( 'Author' );

			if ( ! empty( $theme_author ) && strtolower( $theme_author ) == 'pixelgrade' ) {
				return true;
			}
		}

		// No themes from pixelgrade found, return false.
		return false;
	}

	/**
	 * Pass data to WUpdates which should help validate our theme license and give access to updates.
	 *
	 * @param array  $data The optional data that is being passed to WUpdates.
	 * @param string $slug The product's slug.
	 *
	 * @return array
	 */
	function add_license_to_wupdates_data( $data, $slug ) {
		// We need to make sure that we are adding the license hash to the proper update check.
		// Each product fires this filter when it checks for updates; including this very own Pixelgrade Care plugin.
		// For now we will only allow it to work for the current theme (we assume only themes require licenses).
		// @todo This DOES NOT WORK if we have plugins with licenses!
		if ( $slug == basename( get_template_directory() ) ) {
			$data['license_hash'] = 'pixcare_no_license';
			$license_hash         = self::get_license_mod_entry( 'license_hash' );
			if ( $license_hash ) {
				$data['license_hash'] = $license_hash;
			}

			// Add the main product SKU to properly identify the product in use (important for LT themes).
			$data['main_product_sku'] = self::get_theme_main_product_sku();
		}

		return $data;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		if ( self::is_pixelgrade_care_dashboard() ) {
			$rtl_suffix = is_rtl() ? '-rtl' : '';
			wp_enqueue_style( $this->parent->get_plugin_name(), plugin_dir_url( $this->parent->file ) . 'admin/css/pixelgrade_care-admin' . $rtl_suffix . '.css', [ 'dashicons' ], $this->parent->get_version(), 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		if ( self::is_pixelgrade_care_dashboard() ) {
			wp_enqueue_script( 'plugin-install' );
			wp_enqueue_script( 'updates' );
			wp_enqueue_script( $this->parent->get_plugin_name() . '-dashboard', plugin_dir_url( $this->parent->file ) . 'admin/js/dashboard' . $suffix . '.js', [
				'jquery',
				'wp-util',
				'wp-a11y',
				'updates',
				'plugin-install',
			], $this->parent->get_version(), true );

			self::localize_js_data( 'pixelgrade_care-dashboard', true, 'dashboard' );
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'pixelgrade_themes' ) {
			wp_enqueue_script( $this->parent->get_plugin_name() . '-club-themes', plugin_dir_url( $this->parent->file ) . 'admin/js/club-themes' . $suffix . '.js', [
				'jquery',
				'wp-util',
			], $this->parent->get_version(), true );
		}

		// If we are in a block editor page, we need to localize our data since NovaBlocks might make use of it.
		// We don't have our scripts in those pages, so we localize on the editor script.
		if ( pixelgrade_is_block_editor() ) {
			self::localize_js_data( 'wp-block-editor', true, 'editor' );
		}
	}

	/**
	 * Check if everything is in order with the theme's support for Pixelgrade Care.
	 *
	 * @return bool
	 */
	public static function check_theme_support() {
		if ( ! current_theme_supports( 'pixelgrade_care' ) ) {
			return false;
		}

		if ( ! is_array( get_theme_support( 'pixelgrade_care' ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Set the data about the current theme theme_supports.
	 *
	 * @return array
	 */
	public static function set_theme_support() {
		$config = get_theme_support( 'pixelgrade_care' );
		// If this is not a theme that declares proper support for PixCare,
		// we will still fill some of the data about the current theme as it might be used in places.
		if ( ! self::check_theme_support() || ! is_array( $config ) ) {
			$config = [];
		} else {
			// Get the first entry since that is how WordPress saves array theme_supports.
			$config = reset( $config );
		}

		$config = self::standardize_theme_supports( $config );
		if ( empty( $config ) ) {
			$config = [];
		}

		// Update the current theme_support.
		self::$theme_support = apply_filters( 'pixcare_update_theme_supports', $config );

		return self::$theme_support;
	}

	/**
	 * Get the data about the current theme theme_supports.
	 *
	 * @return array
	 */
	public static function get_theme_support( $regenerate = false ) {
		// Fill it if we don't have it yet, or we've been instructed to regenerate it.
		if ( empty( self::$theme_support ) || $regenerate ) {
			self::set_theme_support();
		}

		return self::$theme_support;
	}

	/**
	 * Adds the WP Admin menus.
	 */
	public function add_pixelgrade_care_menu() {
		// First determine if we should show a "Heads Up" bubble next to the main  admin menu item.
		// We will show it when the license is expired, not connected or activated.
		$show_bubble = false;
		// If the theme directory has been changed, show bubble.
		$theme_checks = self::get_theme_checks();
		if ( $theme_checks['has_tampered_wupdates_code'] || ! $theme_checks['has_original_name'] || ! $theme_checks['has_original_directory'] ) {
			$show_bubble = true;
		}

		$activation_user_id = self::get_theme_activation_user_id();
		if ( empty( $activation_user_id ) ) {
			$show_bubble = true;
		} else {
			// Check if we are not connected.
			$pixelgrade_user_login = get_user_meta( $activation_user_id, 'pixelgrade_user_login', true );
			if ( empty( $pixelgrade_user_login ) ) {
				$show_bubble = true;
			} else {
				// We are connected.
				// Show bubble if the license is expired.
				$license_status = self::get_license_mod_entry( 'license_status' );
				if ( empty( $license_status ) || in_array( $license_status, [ 'expired' ] ) ) {
					$show_bubble = true;
				}
			}
		}

		// Show bubble if we have an update notification.
		$new_theme_version = get_theme_mod( 'pixcare_new_theme_version' );
		$theme_support     = self::get_theme_support();
		if ( ! empty( $new_theme_version['new_version'] ) && ! empty( $theme_support['theme_version'] ) && version_compare( $theme_support['theme_version'], $new_theme_version['new_version'], '<' ) ) {
			$show_bubble = true;
		}

		// Show bubble if there are required plugins not activated.
		/** @var TGM_Plugin_Activation $tgmpa */
		global $tgmpa;
		// Bail if we have nothing to work with.
		if ( ! empty( $tgmpa ) && ! empty( $tgmpa->plugins ) ) {
			foreach ( $tgmpa->plugins as $slug => $plugin ) {
				if ( $tgmpa->is_plugin_active( $slug ) && false === $tgmpa->does_plugin_have_update( $slug ) ) {
					continue;
				}

				if ( ! $tgmpa->is_plugin_installed( $slug ) ) {
					if ( true === $plugin['required'] ) {
						$show_bubble = true;
						break;
					}
				} else {
					if ( ! $tgmpa->is_plugin_active( $slug ) && $tgmpa->can_plugin_activate( $slug ) ) {
						if ( true === $plugin['required'] ) {
							$show_bubble = true;
							break;
						}
					}

					if ( $tgmpa->does_plugin_require_update( $slug ) || false !== $tgmpa->does_plugin_have_update( $slug ) ) {
						if ( true === $plugin['required'] ) {
							$show_bubble = true;
							break;
						}
					}
				}
			}
		}

		// Allow others to force or prevent the bubble from showing.
		$show_bubble = apply_filters( 'pixcare_show_menu_notification_bubble', $show_bubble );

		$bubble_markup = '';
		if ( $show_bubble ) {
			$bubble_markup = ' <span class="awaiting-mod"><span class="pending-count">!!︎</span></span>';
		}
		add_menu_page( 'Pixelgrade Care Dashboard', 'Pixelgrade' . $bubble_markup, 'install_themes', 'pixelgrade_care', [
			$this,
			'pixelgrade_care_options_page',
		], plugin_dir_url( $this->parent->file ) . 'admin/images/pixelgrade-menu-image.svg', 2 );
		add_submenu_page( 'pixelgrade_care', 'Dashboard', 'Dashboard', 'manage_options', 'pixelgrade_care', [
			$this,
			'pixelgrade_care_options_page',
		] );

		// Add the themes page.
		$show_themes_page = ! empty( $pixelgrade_user_login );
		if ( true === apply_filters( 'pixcare_allow_themes_page', $show_themes_page ) ) {
			add_submenu_page( 'pixelgrade_care', 'Pixelgrade Themes', 'Themes', 'manage_options', 'pixelgrade_themes', [
				$this,
				'club_themes_template',
			] );
		}
	}

	public static function get_dashboard_url() {
		return admin_url( 'admin.php?page=pixelgrade_care' );
	}

	public static function get_themes_url() {
		return admin_url( 'admin.php?page=pixelgrade_themes' );
	}

	/**
	 * This function will render the layout for the Pixelgrade Theme pages.
	 * Renders the club-page template - defined in the /templates folder
	 */
	public function club_themes_template() {
		require_once plugin_dir_path( $this->parent->file ) . 'admin/templates/pixelgrade-club-page.php';

		get_pixelgrade_club_page_layout();
	}

	/**
	 * Localize a script with or just return the `pixcare` data.
	 *
	 * @param string $script_id
	 * @param bool   $localize_script
	 * @param string $context The context that we are asked to provide the information. We can use this to limit the information.
	 *
	 * @return array
	 */
	public static function localize_js_data( $script_id = 'pixelgrade_care-dashboard', $localize_script = true, $context = 'dashboard', $skip_cache = false ) {
		// If we should skip the cache, make sure that the local cached data gets emptied.
		if ( $skip_cache ) {
			self::$wupdates_ids = null;
			self::$license_details = null;
			self::$theme_config = null;
		}
		// Since license data may have changed during the request, make sure that theme features get a chance to activate.
		pixelgrade_maybe_enable_theme_features();

		$current_user = self::get_theme_activation_user();
		$theme_config = self::get_theme_config( $skip_cache );

		// Use camelCase since this is going to JS!!!
		$localized_data = [
			'apiBase'        => trailingslashit( PIXELGRADE_CARE__API_BASE ),
			'apiBaseDomain'  => PIXELGRADE_CARE__API_BASE_DOMAIN,
			'apiEndpoints'   => self::$externalApiEndpoints,
			'shopBase'       => trailingslashit( PIXELGRADE_CARE__SHOP_BASE ),
			'shopBaseDomain' => PIXELGRADE_CARE__SHOP_BASE_DOMAIN,
			'supportEmail'   => PIXELGRADE_CARE__SUPPORT_EMAIL,
			'devMode'        => pixcare_is_devmode(),
			'themeSupports'  => self::get_theme_support( $skip_cache ),
			'themeConfig'    => $theme_config,
			'themeHeaders'   => self::get_theme_headers(),
			'themeMod'       => [
				'licenseHash'       => false,
				'licenseStatus'     => false,
				'licenseType'       => false,
				'licenseExpiryDate' => false,
				'themeNewVersion'   => false,
				'productTitle'      => false,
				'mainProductTitle'  => false,
				'mainProductSku'    => false,
			],
			'user'           => [
				'name'   => ( empty( $current_user->display_name ) ? $current_user->user_login : $current_user->display_name ),
				'id'     => $current_user->ID,
				'email'  => $current_user->user_email,
				// This will be filled by JS fetched from the Pixelgrade server.
				// @todo Maybe we could fetch them in PHP also and cache them shortly
				'themes' => [],
			],
			'wpRest'         => [
				'root'          => esc_url_raw( rest_url() ),
				'base'          => esc_url_raw( rest_url() . 'pixcare/v1/' ),
				'endpoint'      => self::$internalApiEndpoints,
				'nonce'         => PixelgradeCare()->plugin_admin->wp_nonce,
				'pixcare_nonce' => PixelgradeCare()->plugin_admin->pixcare_nonce,
			],
			'systemStatus'   => PixelgradeCare_DataCollector::get_system_status_data(),
			'knowledgeBase'  => PixelgradeCare_Support::get_knowledgeBase_data( $skip_cache ),
			'siteUrl'        => home_url( '/' ),
			'dashboardUrl'   => PixelgradeCare_Admin::get_dashboard_url(),
			'adminUrl'       => admin_url(),
			'themesUrl'      => admin_url( 'themes.php' ),
			'customizerUrl'  => admin_url( 'customize.php' ),
			'version'        => PixelgradeCare()->get_version(),
			// This is a place to hold contextual hints that may need to be transmitted to JS.
			'context'        => [],
			// The place to give some instructions to the JS part.
			'todos'          => [],
		];

		/*
		 * Fill user data.
		 */
		$oauth_token = get_user_meta( $current_user->ID, 'pixcare_oauth_token', true );
		if ( ! empty( $oauth_token ) ) {
			$localized_data['user']['oauth_token'] = $oauth_token;
		}
		$oauth_token_secret = get_user_meta( $current_user->ID, 'pixcare_oauth_token_secret', true );
		if ( ! empty( $oauth_token_secret ) ) {
			$localized_data['user']['oauth_token_secret'] = $oauth_token_secret;
		}
		$oauth_verifier = get_user_meta( $current_user->ID, 'pixcare_oauth_verifier', true );
		if ( ! empty( $oauth_verifier ) ) {
			$localized_data['user']['oauth_verifier'] = $oauth_verifier;
		}
		$pixcare_user_ID = get_user_meta( $current_user->ID, 'pixcare_user_ID', true );
		if ( ! empty( $pixcare_user_ID ) ) {
			$localized_data['user']['pixcare_user_ID'] = $pixcare_user_ID;
		}
		$pixelgrade_user_login = get_user_meta( $current_user->ID, 'pixelgrade_user_login', true );
		if ( ! empty( $pixelgrade_user_login ) ) {
			$localized_data['user']['pixelgrade_user_login'] = $pixelgrade_user_login;
		}
		$pixelgrade_user_email = get_user_meta( $current_user->ID, 'pixelgrade_user_email', true );
		if ( ! empty( $pixelgrade_user_email ) ) {
			$localized_data['user']['pixelgrade_user_email'] = $pixelgrade_user_email;
		}
		$pixelgrade_display_name = get_user_meta( $current_user->ID, 'pixelgrade_display_name', true );
		if ( ! empty( $pixelgrade_user_email ) ) {
			$localized_data['user']['pixelgrade_display_name'] = $pixelgrade_display_name;
		}
		$user_force_disconnected = get_user_meta( $current_user->ID, 'pixcare_force_disconnected', true );
		if ( ! empty( $user_force_disconnected ) ) {
			$localized_data['user']['force_disconnected'] = true;
			// Delete the user meta so we don't nag the user forever.
			delete_user_meta( $current_user->ID, 'pixcare_force_disconnected' );
		} else {
			$localized_data['user']['force_disconnected'] = false;
		}

		/*
		 * Fill theme data.
		 */

		// We will only put the license details if the current active theme is one of ours.
		if ( self::is_pixelgrade_theme() ) {
			$license_hash = self::get_license_mod_entry( 'license_hash' );
			if ( ! empty( $license_hash ) ) {
				$localized_data['themeMod']['licenseHash'] = $license_hash;
			}
			$license_status = self::get_license_mod_entry( 'license_status' );
			if ( ! empty( $license_status ) ) {
				$localized_data['themeMod']['licenseStatus'] = $license_status;
			}
			// Localize the license type - can be either shop, shop_bundle, envato or free.
			$license_type = self::get_license_mod_entry( 'license_type' );
			if ( ! empty( $license_type ) ) {
				$localized_data['themeMod']['licenseType'] = $license_type;
			}
			// Localize the license expiry date.
			$license_exp = self::get_license_mod_entry( 'license_expiry_date' );
			if ( ! empty( $license_exp ) ) {
				$localized_data['themeMod']['licenseExpiryDate'] = $license_exp;
			}

			// Localize the license product title.
			$license_product_title = self::get_license_mod_entry( 'product_title' );
			if ( ! empty( $license_product_title ) ) {
				$localized_data['themeMod']['productTitle'] = $license_product_title;
			}

			// Localize the license main product title
			// (it may be different from the product title since this is about the main product, not the actual product purchased).
			$license_main_product_title = self::get_license_mod_entry( 'main_product_title' );
			if ( ! empty( $license_main_product_title ) ) {
				$localized_data['themeMod']['mainProductTitle'] = $license_main_product_title;
			}

			// Localize the license main product WUpdates Hashid.
			$license_main_product_hashid = self::get_license_mod_entry( 'wupdates_product_hashid' );
			if ( ! empty( $license_main_product_hashid ) ) {
				$localized_data['themeMod']['mainProductHashid'] = $license_main_product_hashid;
			}

			// Localize the license main product SKU.
			// We use this to distinguish between products using the same hashid (like LT themes).
			$license_main_product_sku = self::get_theme_main_product_sku();
			if ( ! empty( $license_main_product_sku ) ) {
				$localized_data['themeMod']['mainProductSku'] = $license_main_product_sku;
			}
		}
		$new_theme_version = get_theme_mod( 'pixcare_new_theme_version' );
		if ( ! empty( $new_theme_version ) ) {
			$localized_data['themeMod']['themeNewVersion'] = $new_theme_version;
		}

		/*
		 * Give some instructions to the JS part.
		 * Stuff to do like clear the local storage because things have changed.
		 */
		if ( PixelgradeCare_Admin::get_option( 'theme_switched', false ) ) {
			$localized_data['todos']['clearLocalStorage'] = true;

			PixelgradeCare_Admin::set_option( 'theme_switched', false );
			PixelgradeCare_Admin::save_options();
		}

		/*
		 * Gather contextual data that may be used in JS.
		 */
		// If the current request is one that is callback redirect as part of the OAuth process, we will let JS know.
		if ( ! empty( $_GET['oauth_token'] ) && ! empty( $_GET['oauth_verifier'] ) && ! empty( $_GET['oauth_cbr'] ) ) {
			$localized_data['context']['oauth_token'] = sanitize_text_field( $_GET['oauth_token'] );
			$localized_data['context']['oauth_verifier'] = sanitize_text_field( $_GET['oauth_verifier'] );
			$localized_data['context']['oauth_errors'] = ! empty( $_GET['errors'] ) ? sanitize_text_field( $_GET['errors'] ) : '';
			$localized_data['context']['oauth_cbr'] = true;
		}

		$localized_data = apply_filters( 'pixcare_localized_data', $localized_data, $script_id, $skip_cache );

		// We can also skip the script localization, and only return the data.
		if ( $localize_script ) {
			wp_localize_script( $script_id, 'pixcare', $localized_data );
		}

		return $localized_data;
	}

	/**
	 * Add Contextual help tabs.
	 */
	public function add_tabs() {
		$screen = get_current_screen();
		$screen->add_help_tab( [
			'id'      => 'pixelgrade_care_setup_wizard_tab',
			'title'   => esc_html__( 'Setup Wizard', 'pixelgrade_care' ),
			'content' =>
				'<h2>' . esc_html__( 'Site Setup Wizard', 'pixelgrade_care' ) . '</h2>' .
				'<p><a href="' . esc_url( PixelgradeCare_SetupWizard::get_setup_wizard_url() ) . '" class="button button-primary">' . esc_html__( 'Go to setup wizard (via Pixelgrade Care)', 'pixelgrade_care' ) . '</a></p>',
		] );
	}

	public function settings_init() {
		register_setting( 'pixelgrade_care', 'pixelgrade_care_settings' );
		add_settings_section(
			'pixelgrade_care_section',
			esc_html__( 'Pixelgrade Care description', 'pixelgrade_care' ),
			null,
			'pixelgrade_care'
		);
	}

	public function pixelgrade_care_settings_section_callback() {
		echo esc_html__( 'This section description', 'pixelgrade_care' );
	}

	public function pixelgrade_care_options_page() { ?>
		<div class="pixelgrade_care-wrapper">
			<div id="pixelgrade_care_dashboard"></div>
		</div>
		<?php
	}

	/**
	 * Prepare the theme mods which should hold content
	 *
	 * @since    1.2.5
	 *
	 * @param array $oldvalue The last known value for this theme mod
	 *
	 * @param array $value    The current value being set up in theme mod
	 *
	 * @return array
	 */
	public static function sanitize_theme_mods_holding_content( $value, $oldvalue ) {
		// Make sure that $value is an array
		if ( ! is_array( $value ) ) {
			$value = [ $value ];
		}
		$value = array_map( [ 'PixelgradeCare_Admin', 'sanitize_array_items_for_emojies' ], $value );

		return $value;
	}

	/**
	 * If $content is a string the function will convert any 4 byte emoji in a string to their equivalent HTML entity.
	 * In case that $content is array, it will apply the same rule recursively on each array item
	 *
	 * @since 1.2.5
	 *
	 * @param array|string $content
	 *
	 * @return array|string
	 */
	protected static function sanitize_array_items_for_emojies( $content ) {
		if ( is_string( $content ) ) {
			return wp_encode_emoji( $content );
		} elseif ( is_array( $content ) ) {
			foreach ( $content as $key => $item ) {
				$content[ $key ] = self::sanitize_array_items_for_emojies( $item );
			}

			return $content;
		}

		return $content;
	}

	/* === HELPERS=== */

	/**
	 * @param array $config
	 *
	 * @return array
	 */
	public static function standardize_theme_supports( $config ) {
		if ( ! empty( $config['support_url'] ) && ! wp_http_validate_url( $config['support_url'] ) ) {
			unset( $config['support_url'] );
		}
		if ( empty( $config['ock'] ) ) {
			$config['ock'] = 'Lm12n034gL19';
		}
		if ( empty( $config['ocs'] ) ) {
			$config['ocs'] = '6AU8WKBK1yZRDerL57ObzDPM7SGWRp21Csi5Ti5LdVNG9MbP';
		}
		if ( ! empty( $config['support_url'] ) && ! wp_http_validate_url( $config['support_url'] ) ) {
			unset( $config['support_url'] );
		}
		if ( empty( $config['onboarding'] ) ) {
			$config['onboarding'] = 1;
		}
		if ( empty( $config['market'] ) ) {
			$config['market'] = 'pixelgrade';
		}
		// Detect whether the current active theme is one of ours.
		if ( empty( $config['is_pixelgrade_theme'] ) ) {
			$config['is_pixelgrade_theme'] = self::is_pixelgrade_theme();
		}
		// Complete the config with theme details.
		/** @var WP_Theme $theme */
		$theme = wp_get_theme( get_template() );

		if ( empty( $config['theme_name'] ) ) {
			$config['theme_name'] = $theme->get( 'Name' );
		}
		if ( empty( $config['theme_title'] ) ) {
			$config['theme_title'] = $theme->get( 'Name' );
		}
		// The theme name should be the one from the wupdates array.
		$wupdates_theme_name = self::get_original_theme_name();
		if ( ! empty( $wupdates_theme_name ) ) {
			$config['theme_name']  = $wupdates_theme_name;
			$config['theme_title'] = $wupdates_theme_name;
		}
		// We will also remember this since there might be times when we wish to refer to how the theme is actually named in style.css.
		$config['stylecss_theme_name'] = $theme->get( 'Name' );

		if ( empty( $config['theme_uri'] ) ) {
			$config['theme_uri'] = $theme->get( 'ThemeURI' );
		}
		if ( empty( $config['theme_desc'] ) ) {
			$config['theme_desc'] = $theme->get( 'Description' );
		}
		if ( empty( $config['theme_version'] ) ) {
			$config['theme_version'] = $theme->get( 'Version' );
		}
		// This might not be needed anymore since we have apiBase and the like.
		if ( empty( $config['shop_url'] ) ) {
			// The url of the mother shop, trailing slash is required.
			$config['shop_url'] = trailingslashit( apply_filters( 'pixelgrade_care_shop_url', PIXELGRADE_CARE__API_BASE ) );
		}
		$config['is_child'] = is_child_theme();
		$config['template'] = $theme->get_template();

		// This tells us if there is a Pixelgrade theme installed, not necessarily activated.
		$config['hasPxgTheme'] = self::has_pixelgrade_theme();

		// First get the wupdates theme id (hash ID).
		if ( empty( $config['theme_id'] ) ) {
			$config['theme_id'] = self::get_theme_hash_id();
		}
		// The theme type as given by the WUpdates code.
		if ( empty( $config['theme_type'] ) ) {
			$config['theme_type'] = self::get_theme_type();
		}
		// Details about the WUpdates code integrity and the safeness with which we can identify the theme.
		if ( empty( $config['theme_integrity'] ) ) {
			$config['theme_integrity'] = self::get_theme_checks();
		}
		// Get the original theme slug.
		if ( empty( $config['original_slug'] ) ) {
			$config['original_slug'] = self::get_original_theme_slug();
		}
		// Get the theme's main product SKU.
		if ( empty( $config['main_product_sku'] ) ) {
			$config['main_product_sku'] = self::get_theme_main_product_sku();
		}

		if ( ! isset( $config['is_lt_theme'] ) ) {
			$config['is_lt_theme'] = in_array( $config['theme_type'], [ 'theme_lt', 'theme_lt_wporg', ] );
		}

		return apply_filters( 'pixcare_validate_theme_supports', $config );
	}

	public static function get_theme_headers() {
		/** @var WP_Theme $theme */
		$theme = wp_get_theme( get_template() );

		$headers = self::get_theme_header_data( $theme );

		// Check if a child theme is in use.
		// is_child_theme() will not always work due to the fact that the TEMPLATEPATH and STYLESHEETPATH
		// are available too late.
		if ( get_template_directory() !== get_stylesheet_directory() ) {
			$theme                  = wp_get_theme();
			$headers['child_theme'] = self::get_theme_header_data( $theme );
		}

		return $headers;
	}

	/**
	 * Get a list of all available theme headers.
	 *
	 * @param WP_Theme $theme
	 *
	 * @return array
	 */
	public static function get_theme_header_data( $theme ) {
		$headers = [];

		if ( empty( $theme ) || ! $theme instanceof WP_Theme ) {
			return $headers;
		}

		$headers = [
			'Name'        => $theme->get( 'Name' ),
			'ThemeURI'    => $theme->get( 'ThemeURI' ),
			'Description' => $theme->get( 'Description' ),
			'Author'      => $theme->get( 'Author' ),
			'AuthorURI'   => $theme->get( 'AuthorURI' ),
			'Version'     => $theme->get( 'Version' ),
			'Template'    => $theme->get( 'Template' ),
			'Status'      => $theme->get( 'Status' ),
			'TextDomain'  => $theme->get( 'TextDomain' ),
			'DomainPath'  => $theme->get( 'DomainPath' ),
		];

		return $headers;
	}

	/**
	 * Determine if we are looking at the Pixelgrade Care dashboard WP Admin page.
	 *
	 * @return bool
	 */
	public static function is_pixelgrade_care_dashboard() {
		if ( ! empty( $_GET['page'] ) && 'pixelgrade_care' === $_GET['page'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the plugin options either from the static property or the DB.
	 *
	 * @param bool $force_refresh If true, it will grab new data from the DB.
	 *
	 * @return array
	 */
	public static function get_options( $force_refresh = false ) {
		// If the value is an empty array do not attempt to get data from the DB as it is a valid value.
		if ( true === $force_refresh || ( empty( self::$options ) && ! is_array( self::$options ) ) ) {
			// Retrieve the plugin options from the DB
			self::$options = get_option( self::$options_key );
		}

		// We need to make sure that we have an array to work with
		// (maybe the option doesn't exist in the DB and we get back false).
		if ( ! is_array( self::$options ) ) {
			self::$options = [];
		}

		return self::$options;
	}

	/**
	 * Saves the plugin options.
	 *
	 * @return bool True if the options were saved, false it they haven't been saved.
	 */
	public static function save_options() {
		// First save the options in the DB.
		$saved = update_option( self::$options_key, self::$options );

		// Now grab the options again to account for saving errors or other issues (maybe filters),
		// thus having a level playing field.
		self::get_options( true );

		return $saved;
	}

	/**
	 * Deletes the plugin options.
	 *
	 * @return bool True if option is successfully deleted. False on failure.
	 */
	public static function delete_options() {
		return delete_option( self::$options_key );
	}

	/**
	 * Get a single option entry from the plugin's options.
	 *
	 * @param string $option
	 * @param mixed  $default
	 * @param bool   $force_refresh If true, it will grab new data from the DB.
	 *
	 * @return mixed|null
	 */
	public static function get_option( $option, $default = null, $force_refresh = false ) {
		$options = self::get_options( $force_refresh );
		if ( isset( $options[ $option ] ) ) {
			return $options[ $option ];
		}

		// If we couldn't find the entry, we will return the default value.
		return $default;
	}

	/**
	 * Set a single option entry in the plugin's options.
	 *
	 * It doesn't save in the DB - you need to call PixelgradeCareAdmin::save_options() for that.
	 *
	 * @param string $option The option key
	 * @param mixed  $value  The option value
	 *
	 * @return bool
	 */
	public static function set_option( $option, $value ) {
		// First, make sure that the options are setup properly.
		self::get_options();

		// Modify/add the value in the array.
		self::$options[ $option ] = $value;

		return true;
	}

	public static function sanitize_bool( $value ) {
		if ( empty( $value ) ) {
			return false;
		}

		// See this for more info: http://stackoverflow.com/questions/7336861/how-to-convert-string-to-boolean-php
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Update the new version available for the current theme.
	 *
	 * Hooked into pre_set_site_transient_update_themes.
	 *
	 * @param object $transient
	 *
	 * @return object
	 */
	public function transient_update_theme_version( $transient ) {
		// Nothing to do here if the checked transient entry is empty.
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		// Let's start gathering data about the theme.
		// First get the theme directory name (the theme slug - unique).
		$slug       = basename( get_template_directory() );
		$theme_data = [
			'new_version' => '0.0.1',
			'package'     => '',
			'url'         => '',
		];
		// If we have received an update response with a version, save it.
		if ( ! empty( $transient->response[ $slug ]['new_version'] ) && ! empty( $transient->response[ $slug ]['package'] ) ) {
			$theme_data['new_version'] = $transient->response[ $slug ]['new_version'];
			$theme_data['package']     = $transient->response[ $slug ]['package'];
			if ( ! empty( $transient->response[ $slug ]['url'] ) ) {
				$theme_data['url'] = $transient->response[ $slug ]['url'];
			}
		}

		set_theme_mod( 'pixcare_new_theme_version', $theme_data );

		return $transient;
	}

	public function transient_remove_theme_version( $transient ) {
		remove_theme_mod( 'pixcare_new_theme_version' );
	}

	/**
	 * Update the remote plugin config for the current theme.
	 *
	 * Hooked into pre_set_site_transient_update_themes.
	 *
	 * @param object $transient
	 *
	 * @return object
	 */
	public function transient_update_remote_config( $transient ) {
		// Nothing to do here if the checked transient entry is empty.
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		$this->get_remote_theme_config();

		return $transient;
	}

	/**
	 * Update the license data on theme update check.
	 *
	 * Hooked into pre_set_site_transient_update_themes.
	 *
	 * @param object $transient
	 *
	 * @return object
	 */
	public function transient_update_license_data( $transient ) {
		// Nothing to do here if the checked transient entry is empty.
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		// Check and update the user's license details.
		self::update_theme_license_details();

		return $transient;
	}

	protected static function _get_user_product_licenses_cache_key( $user_id, $hash_id = '', $sku = '' ) {
		return 'pixcare_user_product_licenses_' . md5( $user_id . '_' . $hash_id . '_' . $sku );
	}

	/**
	 * A helper function that returns the licenses available for a user and maybe a certain product hash ID.
	 *
	 * @param int    $user_id    The connected user ID.
	 * @param string $hash_id    Optional. The product hash ID.
	 * @param string $sku        Optional. The product SKU.
	 * @param bool   $skip_cache Optional. Whether to skip the cache and fetch new data.
	 *
	 * @return array|false
	 */
	public static function get_user_product_licenses( $user_id, $hash_id = '', $sku = '', $skip_cache = false ) {
		$data = false;

		if ( defined( 'PIXELGRADE_CARE__SKIP_INTERNAL_CACHE' ) && PIXELGRADE_CARE__SKIP_INTERNAL_CACHE === true ) {
			$skip_cache = true;
		}

		// First try and get the cached data.
		if ( ! $skip_cache ) {
			$data = get_site_transient( self::_get_user_product_licenses_cache_key( $user_id, $hash_id, $sku ) );
		}

		// The transient isn't set, is expired, or we're supposed to skip the cache; we need to fetch fresh data.
		if ( false === $data || true === $skip_cache ) {
			$request_args = [
				'method'    => PixelgradeCare_Admin::$externalApiEndpoints['wupl']['licenses']['method'],
				'timeout'   => 5,
				'blocking'  => true,
				'body'      => [
					'user_id'       => $user_id,
					'hash_id'       => $hash_id,
					'sku'           => $sku,
					'type'          => self::get_theme_type(),
					'theme_headers' => self::get_theme_headers(),
				],
				'sslverify' => false,
			];

			// Increase timeout if the target URL is a development one so we can account for slow local (development) installations.
			if ( self::is_development_url( PixelgradeCare_Admin::$externalApiEndpoints['wupl']['licenses']['url'] ) ) {
				$request_args['timeout'] = 10;
			}

			// Get the user's licenses from the server.
			$response = wp_remote_request( PixelgradeCare_Admin::$externalApiEndpoints['wupl']['licenses']['url'], $request_args );
			if ( is_wp_error( $response ) ) {
				return false;
			}
			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
			// Bail in case of decode error or failure to retrieve data.
			if ( null === $response_data || empty( $response_data['data']['licenses'] ) || 'success' !== $response_data['code'] ) {
				return false;
			}

			$data = $response_data['data']['licenses'];

			// Cache the data in a transient for 1 hour.
			set_site_transient( self::_get_user_product_licenses_cache_key( $user_id, $hash_id, $sku ), $data, 1 * HOUR_IN_SECONDS );
		}

		return $data;
	}

	/**
	 * Update the details of the current theme's license.
	 *
	 * @param bool $skip_cache Optional. Whether to skip the cache and fetch new data.
	 *
	 * @return bool
	 */
	public static function update_theme_license_details( $skip_cache = false ) {
		$theme_hash_id = self::get_theme_hash_id();
		if ( empty( $theme_hash_id ) ) {
			// Something is wrong with the theme or is not one of our themes.
			return false;
		}

		$theme_type = self::get_theme_type();
		if ( empty( $theme_type ) ) {
			// Something is wrong with the theme or is not one of our themes.
			return false;
		}

		// Get the connected pixelgrade user id.
		$connection_user = self::get_theme_activation_user();
		if ( empty( $connection_user ) || empty( $connection_user->ID ) ) {
			return false;
		}

		$user_id = get_user_meta( $connection_user->ID, 'pixcare_user_ID', true );
		if ( empty( $user_id ) ) {
			// Not authenticated.
			return false;
		}

		// Get the current license hash used to uniquely identify a license.
		$current_license_hash = self::get_license_mod_entry( 'license_hash' );
		// If we have no license hash, we have nothing to update.
		if ( empty( $current_license_hash ) ) {
			return false;
		}

		// For LT Themes, we want to provide the SKU of the active main product
		// to be able to disambiguate between the products since they use the same hashid.
		$subscriptions = self::get_user_product_licenses( $user_id, $theme_hash_id, self::get_theme_main_product_sku(), $skip_cache );
		if ( ! empty( $subscriptions ) ) {
			foreach ( $subscriptions as $key => $value ) {
				if ( ! isset( $value['licenses'] ) || empty( $value['licenses'] ) ) {
					// No licenses found in this subscription or marketplace.
					continue;
				}
				foreach ( $value['licenses'] as $license ) {
					if ( ! empty( $license['license_hash'] )
					     && $current_license_hash == $license['license_hash']
					     && ! empty( $license['license_type'] )
					     && ! empty( $license['license_status'] ) ) {

						// Update the license details.
						self::set_license_mod( $license );

						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Handle the logic related to theme license mods on theme switch, mainly related to parent and child themes.
	 *
	 * @param string   $old_name  Old theme name.
	 * @param WP_Theme $old_theme WP_Theme instance of the old theme.
	 */
	public static function handle_license_mods_on_theme_switch( $old_name, $old_theme ) {
		// We will not do this if we are in the Setup Wizard or if doing some AJAX/REST API call.
		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST === true )
		     || 0 === strpos( $_SERVER['REQUEST_URI'], '/wp-json' )
		     || PixelgradeCare_SetupWizard::is_pixelgrade_care_setup_wizard()
		     || wp_is_json_request() ) {

			return;
		}

		// If the current theme is a child theme and the previously active, parent theme was one of ours,
		// we will copy the license details from the parent to the child.
		if ( is_child_theme()
		     && get_template() === $old_theme->get_stylesheet()
			 && ( ! empty( $old_theme->get('Author') ) && 'pixelgrade' === strtolower( $old_theme->get('Author') ) )
		) {
			$old_theme_mods = get_option( 'theme_mods_' . $old_theme->get_stylesheet() );
			if ( ! empty( $old_theme_mods['pixcare_license'] ) ) {
				self::set_license_mod( $old_theme_mods['pixcare_license'] );

				// Remove the hook to force license refresh since there is no need for it.
				remove_action( 'after_switch_theme', [ 'PixelgradeCare_Admin', 'do_fetch_and_activate_theme_license' ], 30 );

				return;
			}
		}

		// If the current theme is a parent theme and the previously active, child theme was one of its children,
		// we will copy the license details from the child to the parent if they are not empty and different.
		$current_theme = wp_get_theme( get_template() );
		if ( ! is_child_theme()
		     && $old_theme->get_template() !== $old_theme->get_stylesheet()
		     && get_stylesheet() === $old_theme->get_template()
		     && ( ! empty( $current_theme->get('Author') ) && 'pixelgrade' === strtolower( $current_theme->get('Author') ) )
		) {
			$current_theme_mods = get_theme_mods();
			$old_theme_mods = get_option( 'theme_mods_' . $old_theme->get_stylesheet() );
			if ( ! empty( $old_theme_mods['pixcare_license'] ) && $old_theme_mods['pixcare_license'] !== $current_theme_mods['pixcare_license'] ) {
				self::set_license_mod( $old_theme_mods['pixcare_license'] );
			}

			// Remove the hook to force license refresh since there is no need for it.
			// We want to do this regardless of license mod update.
			remove_action( 'after_switch_theme', [ 'PixelgradeCare_Admin', 'do_fetch_and_activate_theme_license' ], 30 );

			return;
		}
	}

	/**
	 * Just a passthrough to be used in hooks.
	 */
	public static function do_fetch_and_activate_theme_license() {
		// We will not do this if we are in the Setup Wizard or if doing some AJAX/REST API call.
		if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST === true )
		     || 0 === strpos( $_SERVER['REQUEST_URI'], '/wp-json' )
			 || PixelgradeCare_SetupWizard::is_pixelgrade_care_setup_wizard()
		     || wp_is_json_request() ) {

			return;
		}

		$result = self::fetch_and_activate_theme_license();

		// If something went wrong, we will redirect the user to the Pixelgrade Care dashboard
		// to have a look and maybe do something.
		if ( false === $result ) {
			wp_redirect( PixelgradeCare_Admin::get_dashboard_url() );
		}
	}

	/**
	 * Get the user's licenses, select the best one and activate it.
	 *
	 * @param string $sku Optional. The product SKU to narrow the license list by.
	 *                    This is only used for LT Themes to disambiguate between licenses since all LT products use the same hashid.
	 *
	 * @return bool True when we have successfully fetched and activated a license, false otherwise.
	 */
	public static function fetch_and_activate_theme_license( $sku = '' ) {
		$current_user = self::get_theme_activation_user();
		if ( empty( $current_user ) || empty( $current_user->ID ) ) {
			return false;
		}

		// First we will delete any previous license mods. Start fresh.
		$old_license_mod = self::get_license_mods();
		self::delete_license_mod();

		// If they modified anything in the wupdates_gather_ids function - exit.  Cannot activate the theme.
		if ( ! self::is_wupdates_filter_unchanged() ) {
			return false;
		}

		// Determine whether the user is logged in or not. If not logged in - don't bother trying to activate the theme license
		$pixelgrade_user_id = get_user_meta( $current_user->ID, 'pixcare_user_ID', true );
		if ( empty( $pixelgrade_user_id ) ) {
			return false;
		}

		$wupdates_identification = self::get_wupdates_identification_data();
		if ( empty( $wupdates_identification ) ) {
			return false;
		}

		// Get the user's licenses from the server (grouped by orders or marketplace - like 'envato').
		$customer_orders = self::get_user_product_licenses( $pixelgrade_user_id, $wupdates_identification['id'], $sku, true );
		if ( empty( $customer_orders ) || is_wp_error( $customer_orders ) ) {
			// Put back the old license data in case of error.
			if ( ! empty( $old_license_mod ) ) {
				self::set_license_mod( $old_license_mod );
			}
			return false;
		}

		// Try to select the best license.
		$license_to_activate = self::select_best_customer_license( $customer_orders );
		if ( empty( $license_to_activate ) ) {
			return false;
		}

		// If we have exactly one license - go ahead and activate it.
		if ( ! empty( $license_to_activate['license_hash'] ) ) {
			// Get all kind of details about the active theme.
			$theme_details = self::get_theme_support();
			$data          = [
				'action'       => 'activate',
				'license_hash' => $license_to_activate['license_hash'],
				'site_url'     => home_url( '/' ),
				'is_ssl'       => is_ssl(),
				'hash_id'      => $wupdates_identification['id'],
			];

			if ( isset( $theme_details['theme_version'] ) ) {
				$data['current_version'] = $theme_details['theme_version'];
			}
			$request_args = [
				'method'    => PixelgradeCare_Admin::$externalApiEndpoints['wupl']['licenseAction']['method'],
				'timeout'   => 6,
				'blocking'  => true,
				'body'      => $data,
				'sslverify' => false,
			];

			// Increase timeout if the target URL is a development one so we can account for slow local (development) installations.
			if ( self::is_development_url( PixelgradeCare_Admin::$externalApiEndpoints['wupl']['licenseAction']['url'] ) ) {
				$request_args['timeout'] = 10;
			}

			// Activate the license.
			$response = wp_remote_request( PixelgradeCare_Admin::$externalApiEndpoints['wupl']['licenseAction']['url'], $request_args );
			if ( is_wp_error( $response ) ) {
				// Put back the old license data in case of error.
				if ( ! empty( $old_license_mod ) ) {
					self::set_license_mod( $old_license_mod );
				}
				return false;
			}

			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
			// Bail in case of decode error or failure.
			if ( null === $response_data || 'success' !== $response_data['code'] ) {
				// Put back the old license data in case of error.
				if ( ! empty( $old_license_mod ) ) {
					self::set_license_mod( $old_license_mod );
				}
				return false;
			}

			// The license has been successfully activated.
			// Save its details in the theme mods.
			self::set_license_mod( $license_to_activate );

			// Let others know about it.
			do_action( 'pixelgrade_care/license_activated', $license_to_activate, $old_license_mod );
		} else {
			// We have multiple licenses for different products (probably an LT theme).
			// We need to have the user select what product to activate.
			return false;
		}

		// All went well.
		return true;
	}

	/**
	 * Select the best license to use from a customer's list of licenses grouped by orders (or marketplace).
	 *
	 * Mirrors the JS logic in Helpers::getLicense() (in admin/src/helpers.js).
	 *
	 * @param array $customer_orders
	 * @return array|false A single license details or an array of licenses if multiple licenses are best candidates (like for LT themes).
	 */
	protected static function select_best_customer_license( $customer_orders ) {
		$best_license = false;
		// We will gather licenses under their license.main_product_sku
		// so we can determine if a single product corresponds to all licenses
		// or there are multiple products and we need to let the user choose.
		$licenses = [];

		foreach ( $customer_orders as $order ) {
			if ( empty( $order['licenses'] ) ) {
				// No licenses found in this order or marketplace.
				continue;
			}

			foreach ( $order['licenses'] as $license ) {
				// Exclude invalid licenses.
				if ( ! isset( $license['license_status_code'] ) || intval( $license['license_status_code'] ) === 0 ) {
					continue;
				}

				if ( false === $best_license ) {
					$best_license = $license;
				}

				// License statuses: 0 (invalid), 1 (valid), 2 (active), 3 (expired), 4 (overused)
				// If we encounter a lower status, it is better.
				if ( intval( $license['license_status_code'] ) < intval( $best_license['license_status_code'] ) ) {
					$best_license = $license;
				}

				// Also add the license to the list keyed by the main_product_sku.
				// This way we will know if we have licenses for multiple products.
				if ( ! isset( $licenses[ $license['main_product_sku'] ] )
				     || intval( $license['license_status_code'] ) < intval( $licenses[ $license['main_product_sku'] ]['license_status_code'] ) ) {

					$licenses[ $license['main_product_sku'] ] = $license;
				}
			}
		}

		// If we have licenses for more than one product, return the list.
		if ( count( $licenses ) > 1 ) {
			return $licenses;
		}

		return $best_license;
	}

	public static function get_customer_products_cache_key( $user_id ) {
		return 'pixcare_license_products_' . md5( $user_id );
	}

	/**
	 * A helper function that returns and maybe 'refreshes' the products available for the customer.
	 *
	 * @param int  $pixelgrade_user_id Optional. Defaults to current activation user.
	 * @param bool $skip_cache         Optional. Force to skip the cache and get new data from the server.
	 *
	 * @return array|false
	 */
	public static function get_customer_products( $pixelgrade_user_id = null, $skip_cache = false ) {
		if ( empty( $pixelgrade_user_id ) ) {
			// Get the activation user.
			$current_user = PixelgradeCare_Admin::get_theme_activation_user();
			if ( ! empty( $current_user->ID ) ) {
				$pixelgrade_user_id = get_user_meta( $current_user->ID, 'pixcare_user_ID', true );
			}
		}

		if ( empty( $pixelgrade_user_id ) ) {
			return false;
		}

		$data = [];

		if ( defined( 'PIXELGRADE_CARE__SKIP_INTERNAL_CACHE' ) && PIXELGRADE_CARE__SKIP_INTERNAL_CACHE === true ) {
			$skip_cache = true;
		}

		// First try and get the cached data.
		if ( ! $skip_cache ) {
			$data = get_site_transient( self::get_customer_products_cache_key( $pixelgrade_user_id ) );
		}

		// The transient isn't set or is expired; we need to fetch fresh data.
		if ( $skip_cache || false === $data ) {
			$request_args = [
				'method'    => PixelgradeCare_Admin::$externalApiEndpoints['wupl']['customerProducts']['method'],
				'timeout'   => 5,
				'blocking'  => true,
				'body'      => [
					'user_id' => $pixelgrade_user_id,
				],
				'sslverify' => false,
			];

			// Increase timeout if the target URL is a development one so we can account for slow local (development) installations.
			if ( self::is_development_url( PixelgradeCare_Admin::$externalApiEndpoints['wupl']['customerProducts']['url'] ) ) {
				$request_args['timeout'] = 10;
			}

			// Get the user license's available products from the server.
			$response = wp_remote_request( PixelgradeCare_Admin::$externalApiEndpoints['wupl']['customerProducts']['url'], $request_args );
			if ( is_wp_error( $response ) ) {
				return false;
			}
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			// Bail in case of decode error.
			if ( null === $data ) {
				return false;
			}

			// In case we receive a new format API response, handle it correctly.
			// @todo Should refactor this some time after 01.02.2022 since WUpdates Licensing responds in the proper format
			if ( isset( $data['code'] ) && isset( $data['message'] ) && isset( $data['data'] ) ) {
				if ( empty( $data['data']['products'] ) || 'success' !== $data['code'] ) {
					return false;
				}

				$data = $data['data']['products'];
			}

			// We need to make sure that the product information is properly formatted.
			$data = PixelgradeCare_Admin::format_products( $data );

			// Cache the data in a transient for 12 hours.
			set_site_transient( self::get_customer_products_cache_key( $pixelgrade_user_id ), $data, 12 * HOUR_IN_SECONDS );
		}

		return $data;
	}

	/**
	 * Force and update of the customer available products.
	 *
	 * @param int $pixelgrade_user_id Optional. Defaults to current activation user.
	 *
	 * @return array|false Returns the new products array or false on failure.
	 */
	public static function update_customer_products( $pixelgrade_user_id = null ) {
		return self::get_customer_products( $pixelgrade_user_id, true );
	}

	public static function remember_theme_switch() {
		PixelgradeCare_Admin::set_option( 'theme_switched', true );
		PixelgradeCare_Admin::save_options();

		// Let others know that Pixelgrade Care considers a theme switch has happened (regardless if WP thinks so).
		do_action( 'pixelgrade_care/theme_switched' );
	}

	/**
	 * Clear the cached customer available products.
	 *
	 * @param int $pixelgrade_user_id Optional. Defaults to current activation user.
	 *
	 * @return bool
	 */
	public static function clear_customer_products_cache( $pixelgrade_user_id = null ) {
		if ( empty( $pixelgrade_user_id ) ) {
			// Get the activation user.
			$current_user = PixelgradeCare_Admin::get_theme_activation_user();
			if ( ! empty( $current_user->ID ) ) {
				$pixelgrade_user_id = get_user_meta( $current_user->ID, 'pixcare_user_ID', true );
			}
		}

		if ( empty( $pixelgrade_user_id ) ) {
			return false;
		}

		return delete_site_transient( self::get_customer_products_cache_key( $pixelgrade_user_id ) );
	}

	/**
	 * Update the customer available products on theme update check.
	 *
	 * Hooked into pre_set_site_transient_update_themes.
	 *
	 * @param object $transient
	 *
	 * @return object
	 */
	public function transient_update_customer_products( $transient ) {
		// Nothing to do here if the checked transient entry is empty.
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		// Check and update the user's license details.
		self::update_customer_products();

		return $transient;
	}

	/**
	 * A helper functions that builds a specific array of all the products the user has access to.
	 *
	 * For now, we assume all products are themes.
	 *
	 * @param array $products
	 *
	 * @return array
	 */
	public static function format_products( $products ) {
		if ( ! is_array( $products ) ) {
			return [];
		}

		$themes = [];
		// Loop through the club themes and create wp theme objects for each of them.
		foreach ( $products as $key => $product ) {
			$themes[ $key ]['id']                   = isset( $product['slug'] ) ? $product['slug'] : null;
			$themes[ $key ]['active']               = false;
			$themes[ $key ]['name']                 = isset( $product['title'] ) ? $product['title'] : null;
			$themes[ $key ]['screenshot']           = isset( $product['image_html'] ) ? $product['image_html'] : null;
			$themes[ $key ]['hasUpdate']            = false;
			$themes[ $key ]['hasPackage']           = false;
			$themes[ $key ]['author']               = 'pixelgrade';
			$themes[ $key ]['actions']['customize'] = false;
			$themes[ $key ]['installed']            = false;
			$themes[ $key ]['slug']                 = isset( $product['slug'] ) ? $product['slug'] : null;
			$themes[ $key ]['download_url']         = isset( $product['download_url'] ) ? $product['download_url'] : null;
			$themes[ $key ]['demo_url']             = isset( $product['demo_url'] ) ? $product['demo_url'] : null;
			$themes[ $key ]['image_url']            = isset( $product['image_url'] ) ? $product['image_url'] : null;
			$themes[ $key ]['hash_id']              = isset( $product['hash_id'] ) ? $product['hash_id'] : null;
			$themes[ $key ]['sku']                  = isset( $product['sku'] ) ? $product['sku'] : null;
			$themes[ $key ]['is_lt_theme']          = ! empty( $product['is_lt_theme'] );
		}

		return $themes;
	}

	/**
	 * Returns the theme config resulted from merging the default config with the remote one.
	 *
	 * @param bool $skip_cache
	 *
	 * @return array|bool|mixed|object|string
	 */
	public static function get_theme_config( $skip_cache = false ) {
		if ( ! empty( self::$theme_config ) ) {
			return self::$theme_config;
		}

		// Get the Pixelgrade Care theme config provided by the shop.
		$remote_config = self::get_remote_theme_config( $skip_cache );
		// Get the default config.
		$default_config = self::get_default_theme_config();
		// If the config contains the Setup Wizard -> Start step remove it.
		if ( isset( $remote_config['setupWizard']['steps']['start'] ) ) {
			unset( $remote_config['setupWizard']['steps']['start'] );
		}

		if ( ! is_array( $remote_config ) ) {
			self::$theme_config = $default_config;

			return self::$theme_config;
		}

		// Merge the default config with the remote config.
		$final_config = self::array_merge_recursive_ex( $default_config, $remote_config );

		// Filter the starterContent demos list by applicableType, if provided.
		if ( ! empty( $final_config['starterContent']['demos'] ) && is_array( $final_config['starterContent']['demos'] ) ) {
			foreach ( $final_config['starterContent']['demos'] as $key => $demo_config ) {

				// By default, all demos are applicable to our premium theme types.
				if ( empty( $demo_config['applicableTypes'] ) ) {
					$final_config['starterContent']['demos'][ $key ]['applicableTypes'] = $demo_config['applicableTypes'] = [
						'theme',
						'theme_modular',
						'theme_lt',
					];
				}

				if ( ! self::isApplicableToCurrentThemeType( $demo_config ) ) {
					unset( $final_config['starterContent']['demos'][ $key ] );
				}
			}
		}

		// Allow others to have a say in it.
		self::$theme_config = apply_filters( 'pixcare_config', $final_config, $remote_config, $default_config );

		return self::$theme_config;
	}

	public static function clear_config_cache() {
		$result = self::clear_remote_config_cache();

		// Delete the local cache after the clearing of the DB cache.
		self::$theme_config = null;

		return $result;
	}

	/**
	 * Retrieve the remote theme config for the current theme.
	 *
	 * @param bool $skip_cache
	 *
	 * @return array|false
	 */
	public static function get_remote_theme_config( $skip_cache = false ) {

		if ( defined( 'PIXELGRADE_CARE__SKIP_CONFIG_CACHE' ) && PIXELGRADE_CARE__SKIP_CONFIG_CACHE === true ) {
			$skip_cache = true;
		}

		// Get the theme hash ID.
		$theme_id = self::get_theme_hash_id();
		// If we have no hash ID present, bail.
		if ( empty( $theme_id ) ) {
			return false;
		}

		$config = false;
		if ( false === $skip_cache ) {
			$config = get_transient( self::_get_remote_config_cache_key( $theme_id ) );
		}

		if ( true === $skip_cache || false === $config ) {
			// Retrieve the config from the remote server.
			$request_args = [
				'method'    => PixelgradeCare_Admin::$externalApiEndpoints['pxm']['getConfig']['method'],
				'timeout'   => 4,
				'blocking'  => true,
				'body'      => [
					'hash_id' => $theme_id,
					'sku' => self::get_theme_main_product_sku(),
					// This is the Pixelgrade Care Manager configuration version, not the API version
					// @todo this parameter naming is quite confusing
					'version' => self::$pixelgrade_care_manager_api_version,
				],
				'sslverify' => false,
			];

			// Increase timeout when using the PIXELGRADE_CARE__SKIP_CONFIG_CACHE constant so we can account for slow local (development) installations.
			// Also do this if the target URL is a development one.
			if ( ( defined( 'PIXELGRADE_CARE__SKIP_CONFIG_CACHE' ) && PIXELGRADE_CARE__SKIP_CONFIG_CACHE === true )
			     || self::is_development_url( PixelgradeCare_Admin::$externalApiEndpoints['pxm']['getConfig']['url'] ) ) {

				$request_args['timeout'] = 10;
			}

			$response = wp_remote_request( PixelgradeCare_Admin::$externalApiEndpoints['pxm']['getConfig']['url'], $request_args );
			if ( is_wp_error( $response ) ) {
				// In case of errors we return whatever config we may have had previously cached.
				return $config;
			}
			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( null === $response_data || empty( $response_data['data']['config'] ) || 'success' !== $response_data['code'] ) {
				// This means the json_decode has failed.
				// In case of errors we return whatever config we may have had previously cached.
				return $config;
			}
			$config = $response_data['data']['config'];

			// For now, we don't need anything related to dashboard or setup wizard. We will just use the plugin defaults.
			if ( isset( $config['dashboard'] ) ) {
				unset( $config['dashboard'] );
			}
			if ( isset( $config['setupWizard'] ) ) {
				unset( $config['setupWizard'] );
			}

			// Sanitize it.
			$config = self::sanitize_theme_mods_holding_content( $config, [] );
			// Cache it.
			set_transient( self::_get_remote_config_cache_key( $theme_id ), $config, 6 * HOUR_IN_SECONDS );
		}

		return $config;
	}

	protected static function _get_remote_config_cache_key( $theme_id ) {
		return 'pixcare_theme_config_' . $theme_id;
	}

	public static function clear_remote_config_cache() {

		// Get the theme hash ID.
		$theme_id = self::get_theme_hash_id();
		// If we have no hash ID present, bail.
		if ( empty( $theme_id ) ) {
			return false;
		}

		return delete_transient( self::_get_remote_config_cache_key( $theme_id ) );
	}

	/**
	 * Gets the default, hardcoded theme config.
	 *
	 * @return array
	 */
	public static function get_default_theme_config() {
		// Make sure the config function is loaded.
		if ( ! function_exists( 'pixcare_get_default_config' ) ) {
			require_once plugin_dir_path( PixelgradeCare()->file ) . 'includes/default-plugin-config.php';
		}

		return pixcare_get_default_config( self::get_original_theme_slug() );
	}

	public static function is_development_url( $url ) {
		$url = self::cleanup_url( $url );

		$matchers = [
			'^10.0.',
			'^127.0.',
			'^localhost',
			'^192.168.',
			':8080$',
			':8888$',
			'.example$',
			'.invalid$',
			'.localhost',
			'~',
			'.myftpupload.com$',
			'.myraidbox.de$',
			'.cafe24.com$',
			'.no-ip.org$',
			'.pressdns.com$',
			'.home.pl$',
			'.xip.io$',
			'.tw1.ru$',
			'.pantheonsite.io$',
			'.wpengine.com$',
			'.accessdomain.com$',
			'.atwebpages.com$',
			'.testpagejack.com$',
			'.hosting-test.net$',
			'webhostbox.net',
			'amazonaws.com',
			'ovh.net$',
			'.rhcloud.com$',
			'tempurl.com$',
			'x10host.com$',
			'^www.test.',
			'^test.',
			'^dev.',
			'^staging.',
			'no.web.ip',
			'^[^\.]*$',
			//this matches urls not containing any dot in it like "stest" or "localhost"
			'^[[:digit:]]+\.[[:digit:]]+\.[[:digit:]]+\.[[:digit:]]+',
			//this matches urls starting with an IPv4
			'^[[:alnum:]-]+\.dev',
			//this matches any url with the .dev domain - i.e test.dev, pixelgrade.dev/test
			'^[[:alnum:]-]+\.local',
			//this matches any url with the .local domain - i.e test.local, pixelgrade.local/test
			'^[[:alnum:]-]+\.test',
			//this matches any url with the .local domain - i.e test.local, pixelgrade.local/test
			'^[[:alnum:]-]+\.invalid',
			//this matches any url with the .local domain - i.e test.local, pixelgrade.local/test
			'^[[:alnum:]-]+\.localhost',
			//this matches any url with the .local domain - i.e test.local, pixelgrade.local/test
			'^[[:alnum:]-]+\.example',
			//this matches any url with the .local domain - i.e test.local, pixelgrade.local/test
		];

		foreach ( $matchers as $regex ) {
			if ( preg_match( '#' . $regex . '#i', $url ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Cleans a URL (remove protocol and such) - this is done the same way as in WUpdates.
	 *
	 * @see wupdates_prepare_url_for_stats()
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function cleanup_url( $url ) {

		// Safety first.
		if ( is_array( $url ) ) {
			$url = reset( $url );
		}

		// Trim whitespaces.
		$url = trim( $url );

		// Remove the get parameters.
		$url = strtok( $url, '?' );

		// In case scheme relative URI is passed, e.g., //www.google.com/
		$url = trim( $url, '/' );

		// Remove the protocol.
		$url = preg_replace( "(^https?://)", "", $url );

		// Remove www. from the front.
		// Trim it one more time just in case.
		$url = trim( $url, '/' );
		$url = preg_replace( '/^(www\.)/i', '', $url );

		return $url;
	}

	/**
	 * Merge two arrays recursively first by key.
	 *
	 * An entry can be specifically removed if in the same key entry in the second array parameter has a value of  null|`null`.
	 *
	 * @param array $array1
	 * @param array $array2
	 *
	 * @return array
	 */
	protected static function array_merge_recursive_ex( array &$array1, array &$array2 ) {
		$merged = $array1;
		foreach ( $array2 as $key => & $value ) {
			if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
				$merged[ $key ] = self::array_merge_recursive_ex( $merged[ $key ], $value );
			} else if ( is_numeric( $key ) ) {
				if ( ! in_array( $value, $merged ) ) {
					$merged[] = $value;
				}
			} else if ( null === $value || 'null' === $value ) {
				unset( $merged[ $key ] );
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

	/**
	 * Merge arrays recursively and distinct.
	 *
	 * Merges any number of arrays / parameters recursively, replacing
	 * entries with string keys with values from latter arrays.
	 * If the entry or the next value to be assigned is an array, then it
	 * automagically treats both arguments as an array.
	 * Numeric entries are appended, not replaced, but only if they are
	 * unique
	 *
	 * An entry can be specifically removed if in the same key entry in the right-hand arrays has a value of  null|`null`.
	 *
	 * @link   http://www.php.net/manual/en/function.array-merge-recursive.php#96201
	 *
	 * @param array ...     Variable list of arrays to recursively merge.
	 *
	 * @param array $base Initial array to merge.
	 *
	 * @return array
	 *
	 * @author Mark Roduner <mark.roduner@gmail.com>
	 */
	public static function array_merge_recursive_distinct() {
		$arrays = func_get_args();
		$base   = array_shift( $arrays );
		if ( ! is_array( $base ) ) {
			$base = empty( $base ) ? [] : [ $base ];
		}
		foreach ( $arrays as $append ) {
			if ( ! is_array( $append ) ) {
				$append = [ $append ];
			}
			foreach ( $append as $key => $value ) {
				if ( ! array_key_exists( $key, $base ) && ! is_numeric( $key ) ) {
					$base[ $key ] = $value;
					continue;
				}

				if ( array_key_exists( $key, $base ) && ( null === $value || 'null' === $value ) ) {
					unset( $base[ $key ] );
					continue;
				}

				if ( is_array( $value ) || ( array_key_exists( $key, $base ) && is_array( $base[ $key ] ) ) ) {
					if ( ! isset( $base[ $key ] ) ) {
						$base[ $key ] = [];
					}
					$base[ $key ] = self::array_merge_recursive_distinct( $base[ $key ], $append[ $key ] );
				} else if ( is_numeric( $key ) ) {
					if ( ! in_array( $value, $base ) ) {
						$base[] = $value;
					}
				} else {
					$base[ $key ] = $value;
				}
			}
		}

		return $base;
	}

	/**
	 * Cleanup the OAuth saved details if the current user doesn't have the connection details.
	 *
	 * @param object $transient
	 *
	 * @return object
	 */
	public static function transient_maybe_cleanup_oauth_token( $transient ) {
		$activation_user_id = self::get_theme_activation_user_id();
		if ( ! empty( $activation_user_id ) ) {
			$user_token_meta = get_user_meta( $activation_user_id, 'pixcare_oauth_token' );
			$user_pixcare_id = get_user_meta( $activation_user_id, 'pixcare_user_ID' );

			// If the user ID is missing, clear everything.
			if ( $user_token_meta && empty( $user_pixcare_id ) ) {
				self::cleanup_connection_oauth_token( $activation_user_id );
			}
		}

		return $transient;
	}

	/**
	 * @param int $wp_user_id
	 *
	 * @return void
	 */
	public static function cleanup_connection_oauth_token( $wp_user_id = null ) {
		if ( empty( $wp_user_id ) ) {
			$wp_user_id = self::get_theme_activation_user_id();
		} else {
			$wp_user_id = absint( $wp_user_id );
		}

		if ( empty( $wp_user_id ) ) {
			return;
		}

		delete_user_meta( $wp_user_id, 'pixcare_oauth_token' );
		delete_user_meta( $wp_user_id, 'pixcare_oauth_token_secret' );
		delete_user_meta( $wp_user_id, 'pixcare_oauth_verifier' );

	}

	/**
	 * @param int $wp_user_id
	 *
	 * @return void
	 */
	public static function cleanup_connected_user_details( $wp_user_id = null ) {
		if ( empty( $wp_user_id ) ) {
			$wp_user_id = self::get_theme_activation_user_id();
		} else {
			$wp_user_id = absint( $wp_user_id );
		}

		if ( empty( $wp_user_id ) ) {
			return;
		}

		delete_user_meta( $wp_user_id, 'pixcare_user_ID' );
		delete_user_meta( $wp_user_id, 'pixelgrade_user_login' );
		delete_user_meta( $wp_user_id, 'pixelgrade_user_email' );
		delete_user_meta( $wp_user_id, 'pixelgrade_display_name' );
	}

	/**
	 * @return void
	 */
	public static function disconnect_current_connection() {

		// We will ping pixelgrade.com to deactivate the activation.
		$license_hash = self::get_license_mod_entry( 'license_hash' );
		if ( ! empty( $license_hash ) ) {
			$data = [
				'action'       => 'deactivate',
				'license_hash' => $license_hash,
				'site_url'     => home_url( '/' ),
				'is_ssl'       => is_ssl(),
			];

			// Get all kind of details about the active theme.
			$theme_details = self::get_theme_support();

			// Add the theme version.
			if ( isset( $theme_details['theme_version'] ) ) {
				$data['current_version'] = $theme_details['theme_version'];
			}

			$request_args = [
				'method'    => self::$externalApiEndpoints['wupl']['licenseAction']['method'],
				'timeout'   => 5,
				'blocking'  => true,
				'body'      => $data,
				'sslverify' => false,
			];

			wp_remote_request( self::$externalApiEndpoints['wupl']['licenseAction']['url'], $request_args );
		}

		$activation_user_id = self::get_theme_activation_user_id();
		if ( ! empty( $activation_user_id ) ) {
			// Delete the cached customer products.
			$pixelgrade_user_id = get_user_meta( $activation_user_id, 'pixcare_user_ID', true );
			self::clear_customer_products_cache( $pixelgrade_user_id );

			// Delete user OAuth connection details.
			self::cleanup_connection_oauth_token( $activation_user_id );

			// Delete connection user details.
			self::cleanup_connected_user_details();
		}

		remove_theme_mod( 'pixcare_new_theme_version' );

		// We will also clear the theme update transient because when one reconnects it might use a different license
		// and that license might allow for updates.
		// Right now we prevent the update package URL to be saved in the transient (via the WUpdates code).
		delete_site_transient( 'update_themes' );

		// Delete the KB cached data.
		PixelgradeCare_Support::clear_knowledgeBase_data_cache();

		// Clear the cache theme config.
		self::clear_config_cache();

		// Delete the license details.
		self::delete_license_mod();
	}

	/**
	 * The theme `hash_id` property holds a big responsibility in getting the theme license, so we need to dig for it.
	 * - Priority will have the `theme_support` array if it is there then it is declarative, and it stands.
	 * - The second try will be by getting the style.css main comment and get the template name from there. This is not
	 * reliable since the user can change it.
	 * - The last try will be the theme directory name; also not secure because the user can change it.
	 *
	 * @param mixed $fallback
	 *
	 * @return string|false
	 */
	public static function get_theme_hash_id( $fallback = false ) {
		// Get the id of the current theme.
		$wupdates_ids  = self::get_all_wupdates_identification_data();
		$theme_support = get_theme_support( 'pixelgrade_care' );
		// Try to identify the theme by its name in the theme_supports array.
		if ( ! empty( $theme_support['theme_name'] ) && ! empty( $wupdates_ids[ $theme_support['theme_name'] ]['id'] ) ) {
			return $wupdates_ids[ $theme_support['theme_name'] ]['id'];
		}
		// Try to identify the theme by its name in the style.css comment.
		$theme      = wp_get_theme( get_template() );
		$theme_name = strtolower( $theme->get( 'Name' ) );
		if ( ! empty( $wupdates_ids[ $theme_name ]['id'] ) ) {
			return $wupdates_ids[ $theme_name ]['id'];
		}
		// Try to identify the theme by the theme folder name.
		$theme_name = strtolower( basename( get_template_directory() ) );
		if ( ! empty( $wupdates_ids[ $theme_name ]['id'] ) ) {
			return $wupdates_ids[ $theme_name ]['id'];
		}

		// No luck. Inform the user.
		return $fallback;
	}

	/**
	 * Get the current theme type from the WUpdates code.
	 *
	 * Generally, this is a 'theme', but it could also be 'plugin', 'theme_modular', 'theme_wporg' or other markers we wish to use.
	 *
	 * @return string
	 */
	public static function get_theme_type() {
		$wupdates_identification = self::get_wupdates_identification_data();
		if ( empty( $wupdates_identification['type'] ) ) {
			return 'theme';
		}

		return sanitize_title( $wupdates_identification['type'] );
	}

	/**
	 * Get the current theme original slug from the WUpdates code.
	 *
	 * Fallback on the template directory name.
	 *
	 * @return string
	 */
	public static function get_original_theme_slug() {
		$wupdates_identification = self::get_wupdates_identification_data();
		if ( empty( $wupdates_identification['slug'] ) ) {
			return basename( get_template_directory() );
		}

		return sanitize_title( $wupdates_identification['slug'] );
	}

	/**
	 * Get the current theme original name from the WUpdates code.
	 *
	 * @return string
	 */
	public static function get_original_theme_name() {
		// First, we rely on the main product title as saved in the license details.
		$license_main_product_title  = self::get_license_mod_entry( 'main_product_title' );
		if ( ! empty( $license_main_product_title ) ) {
			return $license_main_product_title;
		}

		$wupdates_identification = self::get_wupdates_identification_data();
		if ( empty( $wupdates_identification['name'] ) ) {
			return ucfirst( basename( str_replace( [ '-', '_' ], ' ', get_template_directory() ) ) );
		}

		return $wupdates_identification['name'];
	}

	/**
	 * Get the current theme main product (without variations related to purchases - like onetime) SKU from the active license.
	 *
	 * @return string The theme main product SKU.
	 */
	public static function get_theme_main_product_sku() {
		// Add the main product SKU to properly identify the product in use (important for LT themes).
		$main_product_sku = self::get_license_mod_entry( 'main_product_sku' );
		if ( $main_product_sku ) {
			return $main_product_sku;
		}

		// We will fall back on the original theme slug since we matched that with product SKU prior to LT themes.
		return self::get_original_theme_slug();
	}

	/**
	 * Checks if the wupdates_gather_ids filter has been tempered with.
	 *
	 * This should also be used to block theme updates.
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	public static function is_wupdates_filter_unchanged() {
		$wupdates_identification = self::get_wupdates_identification_data();

		// Check if the wupdates_ids array is missing either of this properties.
		if ( empty( $wupdates_identification ) || ! isset( $wupdates_identification['name'] ) || ! isset( $wupdates_identification['slug'] ) || ! isset( $wupdates_identification['id'] ) || ! isset( $wupdates_identification['type'] ) || ! isset( $wupdates_identification['digest'] ) ) {
			return false;
		}
		// Create the md5 hash from the properties of wupdates_ids and compare it to the digest from that array.
		$md5 = md5( 'name-' . $wupdates_identification['name'] . ';slug-' . $wupdates_identification['slug'] . ';id-' . $wupdates_identification['id'] . ';type-' . $wupdates_identification['type'] );
		// The md5 hash should be the same one as the digest hash.
		if ( $md5 !== $wupdates_identification['digest'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine if the current theme is one of ours.
	 *
	 * @return bool
	 */
	public static function is_pixelgrade_theme() {
		$wupdates_identification = self::get_wupdates_identification_data();
		// If we have the WUpdates information tied to the current theme slug, then we are good.
		if ( ! empty( $wupdates_identification ) ) {
			return true;
		}

		// Next we will test for the author in the theme header.
		$theme        = wp_get_theme( get_template_directory() );
		$theme_author = $theme->get( 'Author' );
		if ( ! empty( $theme_author ) && 'pixelgrade' === strtolower( $theme_author ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the theme name's or directory have been changed.
	 *
	 * @return array|bool
	 */
	public static function get_theme_checks() {
		// We start with paranoid default values.
		$has_original_name            = false;
		$has_original_directory       = false;
		$has_tampered_wupdates_code   = true;
		$missing_wupdates_update_code = true;
		// If the user hasn't got any pixelgrade themes - return true. They don't need this filter.
		if ( ! self::has_pixelgrade_theme() ) {
			return [
				'has_original_name'            => true,
				'has_original_directory'       => true,
				'has_tampered_wupdates_code'   => false,
				'missing_wupdates_update_code' => false,
			];
		}

		$slug                    = basename( get_template_directory() );
		$wupdates_identification = self::get_wupdates_identification_data( $slug );
		if ( empty( $wupdates_identification ) ) {
			return [
				'has_original_name'            => $has_original_name,
				'has_original_directory'       => $has_original_directory,
				'has_tampered_wupdates_code'   => $has_tampered_wupdates_code,
				'missing_wupdates_update_code' => $missing_wupdates_update_code,
			];
		}
		// At this point, we assume they are using the WUpdates old code, so no tampering.
		$has_tampered_wupdates_code = false;
		$hash_id                    = $wupdates_identification['id'];

		// Themes from WordPress.org don't need the WUpdates update code.
		if ( self::is_wporg_theme( $slug )
		     || self::has_wupdates_update_code( $hash_id )
			 || (
				 // Modular (or maybe LT) themes may not have the WUpdates code loaded when the license is first saved.
				// We let this pass to avoid refreshes.
				 in_array( PixelgradeCare_Admin::get_license_mod_entry('license_type' ), ['shop', 'shop_bundle',] )
				&& in_array( $wupdates_identification['type'], [
					 'theme_modular',
					 'theme_lt',
				 ] )
		     ) ) {
			$missing_wupdates_update_code = false;
		}

		// Theme name as is in style.css.
		$current_theme         = wp_get_theme( get_template() );
		$theme_stylesheet_name = $current_theme->get( 'Name' );
		// Check if the WUpdates has the newer properties and do the additional checks.
		if ( isset( $wupdates_identification['name'] ) || isset( $wupdates_identification['slug'] ) || isset( $wupdates_identification['digest'] ) ) {
			if ( isset( $wupdates_identification['digest'] ) ) {
				// Compare this theme's digest with the one from wupdates. If they're the same all is good.
				$md5 = md5( 'name-' . $wupdates_identification['name'] . ';slug-' . $wupdates_identification['slug'] . ';id-' . $wupdates_identification['id'] . ';type-' . $wupdates_identification['type'] );
				if ( $md5 !== $wupdates_identification['digest'] ) {
					$has_tampered_wupdates_code = true;
				}
			} else {
				$has_tampered_wupdates_code = true;
			}
			// Check to see if the Theme Name has been changed (ignore for non-modular WP.org themes).
			if ( $wupdates_identification['type'] === 'theme_wporg'
			     || ( isset( $wupdates_identification['name'] ) && $wupdates_identification['name'] === $current_theme->get( 'Name' ) ) ) {

				$has_original_name = true;
			}
			// Check to see if the Theme Directory has been changed (ignore for non-modular WP.org themes).
			if ( $wupdates_identification['type'] === 'theme_wporg'
			     || ( isset( $wupdates_identification['slug'] ) && $wupdates_identification['slug'] === $slug ) ) {

				$has_original_directory = true;
			}
			// Check that at least the theme directory (slug) and the theme name from style.css match
			// We use the same function (sanitize_title) that the core uses to generate slugs.
		} elseif ( $slug == sanitize_title( $theme_stylesheet_name ) ) {
			$has_original_name      = true;
			$has_original_directory = true;
		}

		return [
			'has_original_name'            => $has_original_name,
			'has_original_directory'       => $has_original_directory,
			'has_tampered_wupdates_code'   => $has_tampered_wupdates_code,
			'missing_wupdates_update_code' => $missing_wupdates_update_code,
		];
	}

	public static function get_wupdates_identification_data( $slug = '' ) {
		if ( empty( $slug ) ) {
			$slug = basename( get_template_directory() );
		}

		$wupdates_ids = self::get_all_wupdates_identification_data();

		// We really want an id (hash_id) and a type.
		if ( empty( $slug ) || empty( $wupdates_ids[ $slug ] ) || ! isset( $wupdates_ids[ $slug ]['id'] ) || ! isset( $wupdates_ids[ $slug ]['type'] ) ) {
			return false;
		}

		return $wupdates_ids[ $slug ];
	}

	public static function get_all_wupdates_identification_data() {
		if ( empty( self::$wupdates_ids ) ) {
			self::$wupdates_ids = apply_filters( 'wupdates_gather_ids', [] );

			// Allow others to have a say in it.
			self::$wupdates_ids = apply_filters( 'pixelgrade_care_wupdates_identification_data', self::$wupdates_ids );
		}

		return self::$wupdates_ids;
	}

	public static function maybe_fill_up_wupdates_identification_data( $wupdates_data ) {

		// Maybe tackle the current active theme.
		$theme_slug = basename( get_template_directory() );

		$theme_headers = self::get_theme_headers();
		// We only want to do this for themes that are ours and for themes that have a slug matching their text-domain.
		// Also consider the development text-domain.
		if ( ! empty( $theme_headers['Author'] ) && 'pixelgrade' === strtolower( $theme_headers['Author'] ) &&
		     ! empty( $theme_headers['TextDomain'] ) && ( '__theme_txtd' === $theme_headers['TextDomain'] || $theme_slug === $theme_headers['TextDomain'] ) ) {
			// We need to know if we have made changes to the data.
			$theme_data_changed = false;

			if ( ! isset( $wupdates_data[ $theme_slug ] ) ) {
				$wupdates_data[ $theme_slug ] = [];
			}

			if ( empty( $wupdates_data[ $theme_slug ]['name'] ) ) {
				$wupdates_data[ $theme_slug ]['name'] = $theme_headers['Name'];
				$theme_data_changed                   = true;
			}

			if ( empty( $wupdates_data[ $theme_slug ]['slug'] ) ) {
				$wupdates_data[ $theme_slug ]['slug'] = $theme_slug;
				$theme_data_changed                   = true;
			}

			if ( empty( $wupdates_data[ $theme_slug ]['type'] ) ) {
				// We will assume it is a WordPress.org theme.
				$wupdates_data[ $theme_slug ]['type'] = 'theme_wporg';
				if ( file_exists( trailingslashit( get_template_directory() ) . 'components/components-autoload.php' ) ) {
					// We will assume this is a modular WordPress.org theme.
					$wupdates_data[ $theme_slug ]['type'] = 'theme_modular_wporg';
				}
				$theme_data_changed = true;
			}

			if ( $wupdates_data[ $theme_slug ]['type'] === 'theme_modular_wporg'
			            && in_array( PixelgradeCare_Admin::get_license_mod_entry('license_type' ), ['shop', 'shop_bundle',] ) ) {
				// If the base theme is of the theme_modular_wporg type and we have a premium license,
				// change the type theme type to theme_modular.

				$wupdates_data[ $theme_slug ]['type'] = 'theme_modular';
				$theme_data_changed = true;
			}

			if ( $wupdates_data[ $theme_slug ]['type'] === 'theme_lt_wporg'
				&& in_array( PixelgradeCare_Admin::get_license_mod_entry('license_type' ), ['shop', 'shop_bundle',] ) ) {
				// If the base theme is of the theme_lt_wporg type (like Anima) and we have a premium license,
				// change the type theme type to theme_lt.

				$wupdates_data[ $theme_slug ]['type'] = 'theme_lt';
				$theme_data_changed = true;
			}

			if ( empty( $wupdates_data[ $theme_slug ]['id'] ) ) {
				// We will use this hardcoded list of slugs and matching hash IDs.
				$slug_to_hashid_map = [
					'gema'          => 'ML4Gm',
					'gema-lite'     => 'ML4Gm',
					'hive'          => 'PMAGv',
					'hive-lite'     => 'PMAGv',
					'patch'         => 'JlplJ',
					'patch-lite'    => 'JlplJ',
					'silk'          => 'J6l3r',
					'silk-lite'     => 'J6l3r',
					'jason'         => 'MA1wM',
					'jason-lite'    => 'MA1wM',
					'julia'         => 'JDbdQ',
					'julia-lite'    => 'JDbdQ',
					'noto'          => 'JDKZB',
					'noto-lite'     => 'JDKZB',
					'felt'          => 'M2lXe',
					'felt-lite'     => 'M2lXe',
					'noah'          => 'JyzqR',
					'noah-lite'     => 'JyzqR',
					'osteria'       => 'J3oRl',
					'osteria-lite'  => 'J3oRl',
					'vasco'         => 'v7zV3',
					'vasco-lite'    => 'v7zV3',
					'fargo'         => 'vjzlK',
					'fargo-lite'    => 'vjzlK',
					'pile'          => 'JDeVM',
					'pile-lite'     => 'JDeVM',
					'timber'        => 'JkElr',
					'timber-lite'   => 'JkElr',
					'rosa'          => 'vexXr',
					'rosa-lite'     => 'vexXr',
					'rosa2'         => 'JxLn7',
					'rosa2-lite'    => 'JxLn7',
					'border'        => 'M1a0M',
					'border-lite'   => 'M1a0M',
					'mies'          => 'MXBzv',
					'mies-lite'     => 'MXBzv',
					'lens'          => 'vpz6M',
					'lens-lite'     => 'vpz6M',
					'listable'      => 'Kv7Br',
					'listable-lite' => 'Kv7Br',
					'bucket'        => 'MXD0M',
					'bucket-lite'   => 'MXD0M',
					'heap'          => 'MAYEM',
					'heap-lite'     => 'MAYEM',
					'anima'         => 'QBAXY',
				];

				if ( isset( $slug_to_hashid_map[ $theme_slug ] ) ) {
					$wupdates_data[ $theme_slug ]['id'] = $slug_to_hashid_map[ $theme_slug ];
					$theme_data_changed                 = true;
				}
			}

			if ( $theme_data_changed && ! empty( $wupdates_data[ $theme_slug ]['id'] ) ) {
				// Regenerate the digest.
				$wupdates_data[ $theme_slug ]['digest'] = md5( 'name-' . $wupdates_data[ $theme_slug ]['name'] . ';slug-' . $wupdates_data[ $theme_slug ]['slug'] . ';id-' . $wupdates_data[ $theme_slug ]['id'] . ';type-' . $wupdates_data[ $theme_slug ]['type'] );
			}
		}

		return $wupdates_data;
	}

	public static function is_wporg_theme( $slug = '' ) {
		$wupdates_identification = self::get_wupdates_identification_data( $slug );

		if ( ! empty( $wupdates_identification ) && in_array( $wupdates_identification['type'], [
				'theme_wporg',
				'theme_modular_wporg',
				'theme_lt_wporg',
			] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the WUpdates update code is present by checking the presence of the callback filters.
	 *
	 * @param string $hash_id   A WUpdates hash_id to make the check specific to this one.
	 *
	 * @return bool
	 * @global array $wp_filter Stores all the filters.
	 *
	 */
	public static function has_wupdates_update_code( $hash_id = '' ) {
		global $wp_filter;
		$tag = 'pre_set_site_transient_update_themes';
		if ( ! isset( $wp_filter[ $tag ] ) ) {
			return false;
		}
		$hook = $wp_filter[ $tag ];
		foreach ( $hook->callbacks as $priority => $callbacks ) {
			if ( ! empty( $callbacks ) ) {
				foreach ( $callbacks as $key => $callback ) {
					if ( ! empty( $callback['function'] ) && is_string( $callback['function'] ) && false !== strpos( $callback['function'], 'wupdates_check_' . $hash_id ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Hook to pre_set_site_transient_update_themes and force theme updates with WUpdates if the code is present.
	 *
	 * @todo Remove this when the WUpdates theme update code can handle the conflict between the same slug with a WordPress.org theme.
	 *
	 * @param object $transient
	 *
	 * @return object
	 */
	public function force_updates_through_wupdates( $transient ) {
		// First, we handle things for the active theme.
		$slug    = basename( get_template_directory() );
		$hash_id = PixelgradeCare_Admin::get_theme_hash_id();
		if ( false !== $hash_id && PixelgradeCare_Admin::has_wupdates_update_code( $hash_id ) ) {
			// This is kind of hackish because this should be handled in the WUpdates code.
			unset( $transient->response[ $slug ] );
		}

		// Now we handle things for inactive themes that have the same slug for free and pro versions (like Felt).
		// @todo This is just a temporary hack as it will break things if we introduce licensed free themes.
		$themes = [
			'felt',
			'vasco',
			'osteria',
			'noto',
		];

		foreach ( $themes as $theme ) {
			if ( ! empty( $transient->response[ $theme ]['package'] ) && false !== strpos( $transient->response[ $theme ]['package'], 'wordpress.org' ) ) {
				unset( $transient->response[ $theme ] );
			}
		}


		return $transient;
	}

	/**
	 * Hook to pre_set_site_transient_update_themes and block theme update if directory has been tampered with
	 *
	 * @param object $transient
	 *
	 * @return object
	 */
	public function check_if_update_is_valid( $transient ) {
		$slug = basename( get_template_directory() );
		// If the wupdates_gather_ids filter has been changed - do NOT give access to the update.
		if ( ! self::is_wupdates_filter_unchanged() && property_exists( $transient, 'response' ) && isset( $transient->response[ $slug ] ) ) {
			unset( $transient->response[ $slug ] );
		}

		return $transient;
	}

	/**
	 * Get the license details as saved in theme mods.
	 *
	 * @return array
	 */
	public static function get_license_mods() {
		if ( self::has_cached_license_details() ) {
			if ( empty( self::$license_details ) ) {
				return [];
			}

			return self::$license_details;
		}

		$license = get_theme_mod( 'pixcare_license' );
		if ( empty( $license ) ) {
			$license = [];
		}

		self::$license_details = $license;

		return $license;
	}

	/**
	 * Determine if we have cached data of license details.
	 *
	 * This is so we know if we need to read them from theme_mods or not.
	 *
	 * @return bool
	 */
	public static function has_cached_license_details() {
		return null !== self::$license_details;
	}

	/**
	 * Get a license entry as saved in theme mods.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public static function get_license_mod_entry( $key ) {
		// First we grab all the license details.
		$license = self::get_license_mods();

		if ( ! is_array( $license ) ) {
			return false;
		}

		if ( isset( $license[ $key ] ) ) {
			return $license[ $key ];
		}

		return false;
	}

	/**
	 * A helper function that sets the license theme mod, to avoid duplicate code.
	 *
	 * @param array $license
	 */
	public static function set_license_mod( $license ) {
		// Overwrite the cached data.
		self::$license_details = $license;

		// Save in the DB.
		set_theme_mod( 'pixcare_license', $license );
	}

	/**
	 * A helper function that sets a single license theme mod entry, to avoid duplicate code.
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public static function set_license_mod_entry( $key, $value ) {
		// First we grab all the license details.
		$license = self::get_license_mods();

		// Change the value.
		$license[ $key ] = $value;

		// Write it back.
		self::set_license_mod( $license );
	}

	/**
	 * A helper function that deletes the license theme mods.
	 */
	public static function delete_license_mod() {
		remove_theme_mod( 'pixcare_license' );

		// Delete the cached data after the removal of the DB theme_mods.
		// Don't do this before since the filtering related to LT themes won't work.
		// @see PixelgradeCare::handle_update_theme_mods_for_lt_themes()
		self::$license_details = null;
	}

	public function admin_notices() {
		global $pagenow;

		// We only show the update notice on the dashboard.
		if ( true === apply_filters( 'pixcare_allow_dashboard_update_notice', true ) && 'index.php' === $pagenow && current_user_can( 'update_themes' ) ) {
			$new_theme_version = get_theme_mod( 'pixcare_new_theme_version' );
			$theme_name        = self::get_original_theme_name();
			$theme_support     = self::get_theme_support();
			if ( ! empty( $new_theme_version['new_version'] )
			     && ! empty( $theme_name )
			     && ! empty( $theme_support['theme_version'] )
			     && true === version_compare( $theme_support['theme_version'], $new_theme_version['new_version'], '<' )
			) {
				?>
				<div class="notice notice-warning is-dismissible">
					<h3><?php esc_html_e( 'New Theme Update is Available!', 'pixelgrade_care' ); ?></h3>
					<hr>
					<p><?php printf( wp_kses_post( __( 'Great news! A new theme update is available for your <strong>%s</strong> theme, version <strong>%s</strong>. To update go to your <a href="%s">Theme Dashboard</a>.', 'pixelgrade_care' ) ), esc_html( $theme_name ), esc_html( $new_theme_version['new_version'] ), esc_url( PixelgradeCare_Admin::get_dashboard_url() ) ); ?></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Get the user that activated the theme.
	 *
	 * It might be a different one than the current logged-in user.
	 *
	 * @return \WP_User
	 */
	public static function get_theme_activation_user() {
		// Find a user that has the pixelgrade.com connection metas.
		$user_query = new WP_User_Query(
			[
				'meta_query' => [
					'relation' => 'AND',
					[
						[
							'key'     => 'pixelgrade_user_login',
							'compare' => 'EXISTS',
						],
						[
							'key'     => 'pixelgrade_user_login',
							'value'   => '',
							'compare' => '!=',
						],
					],
				],
			]
		);
		// Get the results from the query, returning the first user.
		$users = $user_query->get_results();
		if ( empty( $users ) ) {
			return _wp_get_current_user();
		}

		return reset( $users );
	}

	/**
	 * Get the user ID that activated the theme.
	 *
	 * It might be a different one than the current logged-in user.
	 *
	 * @return int
	 */
	public static function get_theme_activation_user_id() {
		$wp_user = self::get_theme_activation_user();

		$wp_user_id = 0;
		if ( ! empty( $wp_user ) && ! empty( $wp_user->ID ) ) {
			$wp_user_id = absint( $wp_user->ID );
		}

		return $wp_user_id;
	}

	public static function isApplicableToCurrentThemeType( $item ) {
		if ( empty( $item['applicableTypes'] ) ) {
			return true;
		}

		if ( is_string( $item['applicableTypes'] ) ) {
			$item['applicableTypes'] = [ $item['applicableTypes'] ];
		}

		$current_theme_type = self::get_theme_type();

		if ( in_array( $current_theme_type, $item['applicableTypes'] ) ) {
			return true;
		}

		return false;
	}

	public function handle_plugin_autoupdate( $update, $item ) {
		// We want to force enable the auto-update feature for Pixelgrade Care.
		if ( ! empty( $item->plugin ) && strrpos( $item->plugin, 'pixelgrade-care.php' ) === ( strlen( $item->plugin ) - strlen( 'pixelgrade-care.php' ) ) ) {
			$update = true;
		}

		return $update;
	}

	public function handle_theme_autoupdate( $update, $item ) {
		// We want to force enable the auto-update feature for Anima.
		if ( ! empty( $item->theme ) && 'anima' === $item->theme ) {
			$update = true;
		}

		return $update;
	}

	/**
	 * Alter composition themes data for use in the list table.
	 *
	 * @since 0.9.0
	 *
	 * @param array $prepared_themes Array of theme data.
	 *
	 * @return array
	 */
	public function alter_themes_data_for_list_table( $prepared_themes ) {
		foreach ( $prepared_themes as $theme_slug => $theme_data ) {
			$prepared_themes[ $theme_slug ]['isLTTheme'] = false;

			$wupdates_identification = self::get_wupdates_identification_data( $theme_slug );
			// We are only interested in LT Themes.
			if ( empty( $wupdates_identification['type'] ) || 'theme_lt' !== $wupdates_identification['type'] ) {
				continue;
			}

			// Get the license details.
			$license_main_product_title  = self::get_license_mod_entry( 'main_product_title' );
			$license_main_product_hashid = self::get_license_mod_entry( 'wupdates_product_hashid' );
			if ( empty( $license_main_product_hashid ) || $license_main_product_hashid !== $wupdates_identification['id'] ) {
				continue;
			}

			$prepared_themes[ $theme_slug ]['isLTTheme'] = true;

			if ( ! empty( $license_main_product_title )
			     && $license_main_product_title !== $prepared_themes[ $theme_slug ]['name'] ) {

				$prepared_themes[ $theme_slug ]['name'] = $license_main_product_title . '  <em>(' . $prepared_themes[ $theme_slug ]['name'] . ')</em>';
			}

			// Search for all child themes of this LT theme and modify their details accordingly.
			foreach ( $prepared_themes as $child_theme_slug => $child_theme_data ) {
				if ( empty( $child_theme_data['parent'] ) || $child_theme_data['parent'] !== $theme_data['name'] ) {
					continue;
				}

				$prepared_themes[ $child_theme_slug ]['isLTThemeChild'] = true;

				if ( ! empty( $license_main_product_title )
				     && $license_main_product_title !== $prepared_themes[ $child_theme_slug ]['name'] ) {

					$prepared_themes[ $child_theme_slug ]['name'] = $license_main_product_title . ' Child  <em>(' . $prepared_themes[ $child_theme_slug ]['name'] . ')</em>';
				}
			}
		}

		return $prepared_themes;
	}

	public function alter_update_right_now_text( $content ) {
		$wupdates_identification = self::get_wupdates_identification_data();
		// We are only interested in LT Themes.
		if ( empty( $wupdates_identification['type'] ) || 'theme_lt' !== $wupdates_identification['type'] ) {
			return $content;
		}

		// Get the license details.
		$license_main_product_title  = self::get_license_mod_entry( 'main_product_title' );
		$license_main_product_hashid = self::get_license_mod_entry( 'wupdates_product_hashid' );
		if ( empty( $license_main_product_hashid ) || $license_main_product_hashid !== $wupdates_identification['id'] ) {
			return $content;
		}

		return str_replace( '%2$s', $license_main_product_title . ' (%2$s)', $content );
	}

	/**
	 * Main PixelgradeCareAdmin Instance
	 *
	 * Ensures only one instance of PixelgradeCareAdmin is loaded or can be loaded.
	 *
	 * @since  1.3.0
	 * @static
	 *
	 * @param object $parent Main PixelgradeCare instance.
	 *
	 * @return PixelgradeCare_Admin Main PixelgradeCareAdmin instance
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
