<?php
/**
 * This is the base class for handling the registration of required plugins configured through the theme config.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class PixelgradeCare_RequiredPlugins {

	/**
	 * Holds the only instance of this class.
	 * @since   1.13.0
	 * @var     null|PixelgradeCare_RequiredPlugins
	 * @access  protected
	 */
	protected static $_instance = null;

	/**
	 * The main plugin object (the parent).
	 * @since     1.13.0
	 * @var     PixelgradeCare
	 * @access    public
	 */
	public $parent = null;

	public $version = '0.1';

	/**
	 * Constructor.
	 *
	 * @since 1.13.0
	 *
	 * @param PixelgradeCare $parent
	 */
	protected function __construct( $parent ) {
		$this->parent = $parent;

		$this->init();
	}

	/**
	 * Initialize the required plugins manager.
	 *
	 * @since  1.13.0
	 */
	public function init() {
		// Make sure our extended TGMPA is loaded.
		require_once plugin_dir_path( $this->parent->file ) . 'includes/modules/required-plugins/class-pxg-plugin-activation.php';

		// Add hooks, but only if we are not uninstalling the plugin.
		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			$this->add_hooks();
		}
	}


	/**
	 * Initiate our hooks.
	 *
	 * @since 1.13.0
	 * @return void
	 */
	public function add_hooks() {
		// Make sure that TGMPA gets loaded when it's needed, mainly in AJAX requests
		// We need to hook this early because the action is fired in the TGMPA constructor.
		add_action( 'tgmpa_init', [ $this, 'force_load_tgmpa' ] );

		// If the remove config contains recommend plugins, register them with TGMPA.
		add_action( 'tgmpa_register', [ $this, 'register_required_plugins' ], 1000 );

		// Prevent TGMPA admin notices (except plugins page) since we manage plugins in the Pixelgrade Care dashboard.
		add_filter( 'tgmpa_admin_notices', [ $this, 'prevent_tgmpa_notices' ], 10, 2 );
		// Only allow required TGMPA notices, not recommended ones.
		add_filter( 'tgmpa_admin_notices', [ $this, 'filter_tgmpa_notices' ], 15, 2 );
		// Replace the links at the bottom of the notice with a link to the Pixelgrade Care dashboard.
		add_filter( 'tgmpa_notice_action_links', [ $this, 'change_tgmpa_notice_action_links' ], 10, 1 );

		add_filter( 'plugins_api', [ $this, 'handle_external_required_plugins_ajax_install' ], 100, 3 );

		add_filter( 'pixcare_localized_data', [ $this, 'add_to_pixcare_localized_data' ], 10, 3 );
	}

	/**
	 * If we are in a request that "decided" to force load TGMPA, make it happen.
	 *
	 * We have chosen to expect the marker in the $_REQUEST because we need to know about it very early.
	 *
	 * @param array $tgmpa An array containing the TGM_Plugin_Activation instance.
	 */
	public function force_load_tgmpa( $tgmpa ) {
		if ( ! empty( $_REQUEST['force_tgmpa'] ) && 'load' === $_REQUEST['force_tgmpa'] ) {
			add_filter( 'tgmpa_load', '__return_true' );
		}
	}

	/**
	 * Register recommended or required plugins configured with the remote config.
	 *
	 * Please note that this will overwrite any previously required plugins (like the ones in the theme).
	 *
	 * @since 1.4.7
	 */
	public static function register_required_plugins() {
		// First get the config.
		$config = PixelgradeCare_Admin::get_theme_config();

		if ( empty( $config['requiredPlugins']['plugins'] ) || ! is_array( $config['requiredPlugins']['plugins'] ) ) {
			return;
		}

		$required_plugins = $config['requiredPlugins']['plugins'];

		// We can also change the TGMPA configuration if we have received it.
		$tgmpa_config = [];
		if ( ! empty( $config['requiredPlugins']['config'] ) && is_array( $config['requiredPlugins']['config'] ) ) {
			$tgmpa_config = $config['requiredPlugins']['config'];
		}

		$tgmpa = call_user_func( [ get_class( $GLOBALS['tgmpa'] ), 'get_instance' ] );

		$protocol = 'http:';
		if ( is_ssl() ) {
			$protocol = 'https:';
		}

		// Used to evaluate any conditions that may be present.
		require_once dirname( plugin_dir_path( __FILE__ ), 2 ) . '/lib/class-pixelgrade_care-conditions.php';

		// Filter plugins that do not apply to the current theme setup.
		foreach ( $required_plugins as $key => $value ) {
			// We need to make sure that the plugins are not previously registered.
			// The remote config has precedence.
			if ( ! empty( $value['slug'] ) && ! empty( $tgmpa->plugins[ $value['slug'] ] ) ) {
				$tgmpa->deregister( $value['slug'] );
			}

			if ( empty( $value['slug'] )
			     || ! PixelgradeCare_Admin::isApplicableToCurrentThemeType( $value )
				 || ( ! empty( $value['conditions'] ) && ! PixelgradeCare_Conditions::process( $value['conditions'] ) )
			) {
				unset( $required_plugins[ $key ] );
				continue;
			}

			// We also need to make sure that plugins not from the WordPress.org repo (that use an external source),
			// use a full URL for the source, not a protocol relative one.
			if ( ! empty( $value['source'] ) && is_string( $value['source'] ) && 0 === strpos( $value['source'], '//' ) ) {
				$required_plugins[ $key ]['source'] = $protocol . $value['source'];
			}
		}

		tgmpa( $required_plugins, $tgmpa_config );
	}

	public function prevent_tgmpa_notices( $notices, $total_required_action_count ) {
		// If there are no required plugins related actions, drop all the notices.
		if ( 0 === $total_required_action_count ) {
			return [];
		}

		// Current screen is not always available, most notably on the customizer screen.
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $notices;
		}

		$screen = get_current_screen();

		// If a required plugin must be installed/activated/updated to ensure theme compatibility, allow notices everywhere.
		if ( ! empty( $notices['notice_can_install_required'] )
		     || ! empty( $notices['notice_can_activate_required'] )
		     || ! empty( $notices['notice_ask_to_update'] )
		     || ! empty( $notices['notice_ask_to_update_maybe'] ) ) {

			// Make sure that this notice is not dismissible.
			TGM_Plugin_Activation::get_instance()->dismissable = false;

			return $notices;
		}

		// By this point, if the user has dismissed the notification, oblige.
		if ( get_user_meta( get_current_user_id(), 'tgmpa_dismissed_notice_' . TGM_Plugin_Activation::get_instance()->id, true ) ) { // WPCS: CSRF ok.
			return [];
		}

		// We will only allow notifications in the Plugins page.
		if ( 'plugins' !== $screen->base ) { // WPCS: CSRF ok.
			return [];
		}

		return $notices;
	}

	public function filter_tgmpa_notices( $notices, $total_required_action_count ) {
		// If we do have some required plugins related actions, make sure that only those are left.
		// We will delete the recommended groups.
		if ( isset( $notices['notice_can_install_recommended'] ) ) {
			unset( $notices['notice_can_install_recommended'] );
		}
		if ( isset( $notices['notice_can_activate_recommended'] ) ) {
			unset( $notices['notice_can_activate_recommended'] );
		}

		return $notices;
	}

	public function change_tgmpa_notice_action_links( $action_links ) {
		// Add our link to the Pixelgrade Care dashboard (in the front).
		$action_links = [
			                'pixelgrade_care' => sprintf(
				                '<a href="%2$s">%1$s</a>',
				                esc_html__( 'Manage my plugins', 'pixelgrade_care' ),
				                esc_url( PixelgradeCare_Admin::get_dashboard_url() )
			                ),
		                ] + $action_links;

		// Remove any links we don't want
		if ( isset( $action_links['install'] ) ) {
			unset( $action_links['install'] );
		}
		if ( isset( $action_links['update'] ) ) {
			unset( $action_links['update'] );
		}
		if ( isset( $action_links['activate'] ) ) {
			unset( $action_links['activate'] );
		}

		return $action_links;
	}

	/**
	 * Since the core AJAX function wp_ajax_install_plugin(), that handles the AJAX installing of plugins,
	 * only knows to install plugins from the WordPress.org repo, we need to handle the external plugins installation.
	 * Like from WUpdates.
	 *
	 * @param $res
	 * @param $action
	 * @param $args
	 *
	 * @return mixed
	 */
	public function handle_external_required_plugins_ajax_install( $res, $action, $args ) {
		// This is a key we only put from the Pixelgrade Care JS. So we know that the current request is one of ours.
		if ( empty( $_POST['pixcare_plugin_install'] ) ) {
			return $res;
		}

		// Do nothing if this is not an external plugin.
		if ( empty( $_POST['plugin_source_type'] ) || 'external' !== $_POST['plugin_source_type'] ) {
			return $res;
		}

		// Get the TGMPA instance.
		$tgmpa = call_user_func( [ get_class( $GLOBALS['tgmpa'] ), 'get_instance' ] );
		// If the slug doesn't correspond to a TGMPA registered plugin or it has no source URL, bail.
		if ( empty( $tgmpa->plugins[ $_POST['slug'] ] ) || empty( $tgmpa->plugins[ $_POST['slug'] ]['source'] ) ) {
			return $res;
		}

		// Manufacture a minimal response.
		$res = [
			'slug'          => $_POST['slug'],
			'name'          => ! empty( $tgmpa->plugins[ $_POST['slug'] ]['name'] ) ? $tgmpa->plugins[ $_POST['slug'] ]['name'] : $_POST['slug'],
			'version'       => '0.0.1', // We don't really know the plugin version.
			'download_link' => $tgmpa->plugins[ $_POST['slug'] ]['source'],
		];

		// The response must be an object.
		return (object) $res;
	}

	/**
	 * Add the required plugins to the localized themeConfig.
	 *
	 * @param array $localized_data
	 * @param string $script_id
	 * @param bool $skip_cache
	 *
	 * @return array
	 */
	public function add_to_pixcare_localized_data( $localized_data, $script_id, $skip_cache ) {
		if ( empty( $localized_data['themeConfig'] ) ) {
			return $localized_data;
		}

		if ( empty( $localized_data['themeConfig']['pluginManager'] ) ) {
			$localized_data['themeConfig']['pluginManager'] = [];
		}
		$localized_data['themeConfig']['pluginManager']['tgmpaPlugins'] = self::localize_tgmpa_data( $skip_cache );

		return $localized_data;
	}

	/**
	 * Returns the localized TGMPA plugins data.
	 *
	 * It is mainly used in the setup wizard.
	 *
	 * @param bool $skip_cache Whether we should reinitialize the TGMPA plugins list.
	 *
	 * @return array
	 */
	public static function localize_tgmpa_data( $skip_cache = false ) {
		/** @var \TGM_Plugin_Activation $tgmpa */
		global $tgmpa;

		// If we should skip cache, then we register again the required plugins from the Pixelgrade Care theme config
		// since they may have changed mid-request.
		if ( $skip_cache ) {
			self::register_required_plugins();
		}

		// Bail if we have nothing to work with.
		if ( empty( $tgmpa ) || empty( $tgmpa->plugins ) ) {
			return [];
		}

		foreach ( $tgmpa->plugins as $slug => $plugin ) {
			// Do not add Pixelgrade Care in the required plugins array.
			if ( $slug === 'pixelgrade-care' ) {
				unset( $tgmpa->plugins[ $slug ] );
				continue;
			}
			$tgmpa->plugins[ $slug ]['is_installed']       = false;
			$tgmpa->plugins[ $slug ]['is_active']          = false;
			$tgmpa->plugins[ $slug ]['is_up_to_date']      = true;
			$tgmpa->plugins[ $slug ]['is_update_required'] = false;
			// We need to test for method existence because older versions of TGMPA don't have it.
			if ( method_exists( $tgmpa, 'is_plugin_installed' ) && $tgmpa->is_plugin_installed( $slug ) ) {
				$tgmpa->plugins[ $slug ]['is_installed'] = true;
				if ( method_exists( $tgmpa, 'is_plugin_active' ) && $tgmpa->is_plugin_active( $slug ) ) {
					// One can't be active but not installed.
					$tgmpa->plugins[ $slug ]['is_installed'] = true;
					$tgmpa->plugins[ $slug ]['is_active']    = true;
				}

				if ( method_exists( $tgmpa, 'does_plugin_have_update' ) && $tgmpa->does_plugin_have_update( $slug ) && current_user_can( 'update_plugins' ) ) {
					$tgmpa->plugins[ $slug ]['is_up_to_date'] = false;

					if ( method_exists( $tgmpa, 'does_plugin_require_update' ) && $tgmpa->does_plugin_require_update( $slug ) ) {
						$tgmpa->plugins[ $slug ]['is_update_required'] = true;
					}
				}

				if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin['file_path'] ) ) {
					$data                                      = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin['file_path'], false );
					$tgmpa->plugins[ $slug ]['description']    = $data['Description'];
					$tgmpa->plugins[ $slug ]['author']         = $data['Author'];
					$tgmpa->plugins[ $slug ]['active_version'] = $data['Version'];
				}
			}

			// We use this to order plugins.
			$tgmpa->plugins[ $slug ]['order'] = 10;
			if ( ! empty( $plugin['order'] ) ) {
				$tgmpa->plugins[ $slug ]['order'] = intval( $plugin['order'] );
			}

			// If the plugin is already configured with details (maybe delivered remote), we will overwrite any existing one.
			if ( ! empty( $plugin['description'] ) ) {
				$tgmpa->plugins[ $slug ]['description'] = $plugin['description'];
			}

			if ( empty( $tgmpa->plugins[ $slug ]['description'] ) ) {
				$tgmpa->plugins[ $slug ]['description'] = '';
			}

			if ( ! empty( $plugin['author'] ) ) {
				$tgmpa->plugins[ $slug ]['author'] = $plugin['author'];
			}

			// Make sure that if we receive a selected attribute, it is a boolean.
			if ( isset( $tgmpa->plugins[ $slug ]['selected'] ) ) {
				$tgmpa->plugins[ $slug ]['selected'] = self::sanitize_bool( $tgmpa->plugins[ $slug ]['selected'] );
			}

			// Add the optional description link details.
			if ( ! empty( $plugin['descriptionLink']['url'] ) ) {
				$label                                  = esc_html__( 'Learn more', 'pixelgrade_care' );
				$tgmpa->plugins[ $slug ]['description'] .= ' <a class="description-link" href="' . esc_url( $plugin['descriptionLink']['url'] ) . '" target="_blank">' . esc_html( $label ) . '</a>';
			}

			if ( current_user_can( 'activate_plugins' ) && is_plugin_inactive( $plugin['file_path'] ) && method_exists( $tgmpa, 'get_tgmpa_url' ) ) {
				$tgmpa->plugins[ $slug ]['activate_url'] = wp_nonce_url(
					add_query_arg(
						[
							'plugin'         => urlencode( $slug ),
							'tgmpa-activate' => 'activate-plugin',
						],
						$tgmpa->get_tgmpa_url()
					),
					'tgmpa-activate',
					'tgmpa-nonce'
				);
				$tgmpa->plugins[ $slug ]['install_url']  = wp_nonce_url(
					add_query_arg(
						[
							'plugin'        => urlencode( $slug ),
							'tgmpa-install' => 'install-plugin',
						],
						$tgmpa->get_tgmpa_url()
					),
					'tgmpa-install',
					'tgmpa-nonce'
				);
			}
		}

		return $tgmpa->plugins;
	}

	public static function sanitize_bool( $value ) {
		if ( empty( $value ) ) {
			return false;
		}

		// See this for more info: http://stackoverflow.com/questions/7336861/how-to-convert-string-to-boolean-php
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Main PixelgradeCare_RequiredPlugins Instance
	 *
	 * Ensures only one instance of PixelgradeCare_RequiredPlugins is loaded or can be loaded.
	 *
	 * @since  1.13.0
	 * @static
	 *
	 * @param PixelgradeCare $parent The main plugin object (the parent).
	 * @param array          $args   The arguments to initialize the block patterns manager.
	 *
	 * @return PixelgradeCare_RequiredPlugins Main PixelgradeCare_RequiredPlugins instance
	 */
	public static function instance( $parent, $args = [] ) {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent, $args );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.13.0
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), null );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.13.0
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), null );
	}
}
