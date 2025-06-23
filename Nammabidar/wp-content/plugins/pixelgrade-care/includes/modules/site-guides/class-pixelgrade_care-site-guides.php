<?php
/**
 * This is the class that handles the overall logic for handling site guides.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PixelgradeCare_Site_Guides' ) ) :

	class PixelgradeCare_Site_Guides {

		/**
		 * Holds the only instance of this class.
		 * @since   1.17.0
		 * @var     null|PixelgradeCare_Site_Guides
		 * @access  protected
		 */
		protected static $_instance = null;

		/**
		 * The main plugin object (the parent).
		 * @since     1.17.0
		 * @var     PixelgradeCare
		 * @access    public
		 */
		public $parent = null;

		/**
		 * Site guides to be processed and maybe registered.
		 *
		 * @access  protected
		 * @since   1.17.0
		 * @var     array|null
		 */
		protected $site_guides = null;

		/**
		 * Constructor.
		 *
		 * @since 1.17.0
		 *
		 * @param PixelgradeCare $parent
		 * @param array          $args
		 */
		protected function __construct( $parent, $args = [] ) {
			$this->parent = $parent;

			require_once 'class-pixelgrade_care-site_guides-conditions.php';

			$this->init( $args );
		}

		/**
		 * Initialize the site guides manager.
		 *
		 * @since  1.17.0
		 *
		 * @param array   $args                      {
		 *
		 * @type    array $site_guides            Array of site guides config.
		 *  }
		 */
		public function init( $args ) {
			if ( isset( $args['site_guides'] ) && is_array( $args['site_guides'] ) ) {
				$this->site_guides = $args['site_guides'];
			}

			// Add hooks, but only if we are not uninstalling the plugin.
			if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
				$this->add_hooks();
			}
		}

		/**
		 * Initiate our hooks.
		 *
		 * @since 1.17.0
		 * @return void
		 */
		public function add_hooks() {
			// Add action to load remote site guides.
			add_action( 'init', [ $this, 'maybe_load_remote_site_guides' ], 7 );

			// Add actions to register site guides.
			add_action( 'init', [ $this, 'register_assets' ], 10 );
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ], 10 );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ], 10 );

			add_action( 'admin_head', [ $this, 'hide_default_core_welcome_guide' ] );

			add_action( 'current_screen', [ $this, 'add_tabs' ] );
		}

		public function maybe_load_remote_site_guides() {
			// By default, we want Nova Blocks to be active. But we let others have a say as well.
			if ( ! apply_filters( 'pixelgrade_care/use_remote_site_guides', function_exists( 'novablocks_plugin_setup' ) ) ) {
				return;
			}

			if ( $this->site_guides === null ) {
				$remote_site_guides_config = $this->get_remote_site_guides_config();

				if ( empty( $this->site_guides ) ) {
					$this->site_guides = $this->convert_remote_site_guides_config( $remote_site_guides_config );
				}
			}
		}

		/**
		 * Registers site guides assets.
		 *
		 * @since 1.17.0
		 * @return void
		 */
		public function register_assets() {
			$script_asset = require plugin_dir_path( __FILE__ ) . 'build/index.asset.php';
			wp_register_script( 'pixelgrade_care/site_guides/editor', plugins_url( 'build/index.js', __FILE__ ), $script_asset['dependencies'], $script_asset['version'] );

			wp_register_style( 'pixelgrade_care/site_guides/editor', plugins_url( 'build/style-index.css', __FILE__ ), [], $this->parent->get_version(), 'all' );

			$admin_script_asset = require plugin_dir_path( __FILE__ ) . 'build/admin.asset.php';
			wp_register_script( 'pixelgrade_care/site_guides/admin', plugins_url( 'build/admin.js', __FILE__ ), $admin_script_asset['dependencies'], $admin_script_asset['version'], true );

			wp_register_style( 'pixelgrade_care/site_guides/admin', plugins_url( 'build/admin.css', __FILE__ ), [ 'wp-components', 'pixelgrade_care/site_guides/editor' ], $this->parent->get_version(), 'all' );
		}

		/**
		 * Registers site guides editor assets.
		 *
		 * @since 1.17.0
		 * @return void
		 */
		public function enqueue_editor_assets() {
			wp_enqueue_style( 'pixelgrade_care/site_guides/editor' );
			wp_enqueue_script( 'pixelgrade_care/site_guides/editor' );
			$script_params = [
				'siteUrl' => esc_url( get_site_url() ),
				'guides' => $this->get_editor_site_guides(),
			];
			wp_localize_script( 'pixelgrade_care/site_guides/editor', 'pixcareSiteGuides', $script_params );
		}

		/**
		 * Registers site guides admin assets.
		 *
		 * @since 1.17.0
		 * @return void
		 */
		public function enqueue_admin_assets() {
			wp_enqueue_script( 'pixelgrade_care/site_guides/admin' );
			$script_params = [
				'siteUrl' => esc_url( get_site_url() ),
				'guides' => $this->get_admin_site_guides(),
			];
			wp_localize_script( 'pixelgrade_care/site_guides/admin', 'pixcareSiteGuides', $script_params );

			wp_enqueue_style( 'pixelgrade_care/site_guides/admin' );
		}

		/**
		 * Get the site guides to be displayed in the current block editor page.
		 *
		 * @since 1.17.0
		 * @return array
		 */
		public function get_editor_site_guides() {
			$site_guides = $this->site_guides;
			if ( empty( $site_guides ) ) {
				$site_guides = [];
			}

			/**
			 * Filters the Pixelgrade Care site guides.
			 *
			 * @param array  $site_guides List of site guides keyed by their id.
			 */
			$site_guides = apply_filters( 'pixelgrade_care/site_guides', $site_guides );

			// If an empty or falsy value was received, bail.
			if ( empty( $site_guides ) || ! is_array( $site_guides ) ) {
				return [];
			}

			$processed_site_guides = [];
			foreach ( $site_guides as $id => $site_guide ) {
				if ( empty( $site_guide['label'] ) || empty( $site_guide['pages'] ) ) {
					continue;
				}

				// If the site guide has local conditions, process them.
				if ( ! empty( $site_guide['local_conditions'] ) && ! PixelgradeCare_SiteGuides_Conditions::process( $site_guide['local_conditions'] ) ) {
					continue;
				}

				unset( $site_guide['local_conditions'] );

				$site_guide['_uid'] = $id;

				$processed_site_guides[ $id ] = $site_guide;
			}

			return apply_filters( 'pixelgrade_care/site_guides/editor', $processed_site_guides, $site_guides );
		}

		/**
		 * Get the site guides to be displayed in the current admin page.
		 *
		 * @since 1.17.0
		 * @return array
		 */
		public function get_admin_site_guides() {
			$site_guides = $this->site_guides;
			if ( empty( $site_guides ) ) {
				$site_guides = [];
			}

			/**
			 * Filters the Pixelgrade Care site guides.
			 *
			 * @param array  $site_guides List of site guides keyed by their id.
			 */
			$site_guides = apply_filters( 'pixelgrade_care/site_guides', $site_guides );

			// If an empty or falsy value was received, bail.
			if ( empty( $site_guides ) || ! is_array( $site_guides ) ) {
				return [];
			}

			$processed_site_guides = [];
			foreach ( $site_guides as $id => $site_guide ) {
				if ( empty( $site_guide['label'] ) || empty( $site_guide['pages'] ) ) {
					continue;
				}

				// If the site guide has local conditions, process them.
				if ( ! empty( $site_guide['local_conditions'] ) && ! PixelgradeCare_SiteGuides_Conditions::process( $site_guide['local_conditions'] ) ) {
					continue;
				}

				unset( $site_guide['local_conditions'] );

				$site_guide['_uid'] = $id;

				$processed_site_guides[ $id ] = $site_guide;
			}

			return apply_filters( 'pixelgrade_care/site_guides/admin', $processed_site_guides, $site_guides );
		}

		/**
		 * Get the remote site guides configuration.
		 *
		 * @since 1.17.0
		 *
		 * @param bool $skip_cache Optional. Whether to use the cached config or fetch a new one.
		 *
		 * @return array
		 */
		protected function get_remote_site_guides_config( $skip_cache = false ) {
			// Make sure that the Remote Site Guides class is loaded.
			require_once 'class-pixelgrade_care-remote-site-guides.php';

			// Get the site guides data.
			$remote_data = PixelgradeCare_Remote_Site_Guides::instance()->get( $skip_cache );
			if ( false === $remote_data || empty( $remote_data['items'] ) ) {
				$site_guides_config = [];
			} else {
				$site_guides_config = $remote_data['items'];
			}

			return apply_filters( 'pixelgrade_care/site_guides/get_remote_config', $site_guides_config );
		}

		/**
		 * Identify all site guides from the config and return them in a standard, ready-to-register format.
		 *
		 * @since   1.17.0
		 *
		 * @param array $site_guides_config
		 *
		 * @return array The site guides configurations keyed by their name, including namespace.
		 */
		protected function convert_remote_site_guides_config( $site_guides_config ) {
			$site_guides = [];
			if ( empty( $site_guides_config ) || ! is_array( $site_guides_config ) ) {
				return $site_guides;
			}

			foreach ( $site_guides_config as $site_guide_id => $site_guide_config ) {
				// Make sure we have something to work with.
				if ( ! is_array( $site_guide_config )
				     || empty( $site_guide_config['pages'] )
				     || empty( $site_guide_config['label'] ) ) {

					continue;
				}

				// Parse the config.
				$site_guide_config = $this->parse_site_guide_config( $site_guide_config );
				if ( empty( $site_guide_config ) ) {
					continue;
				}

				$site_guides[ $site_guide_id ] = $site_guide_config;
			}

			return $site_guides;
		}

		/**
		 * Parse site guide config.
		 *
		 * @access  protected
		 * @since   1.17.0
		 *
		 * @param array $site_guide_config
		 *
		 * @return  array|false Block pattern config with defaults validated and set as necessary, or false if values not validated.
		 */
		protected function parse_site_guide_config( $site_guide_config = [] ) {
			if ( empty( $site_guide_config ) || ! is_array( $site_guide_config ) ) {
				return false;
			}

			// Set the site guide config using defaults where necessary.
			$defaults = [
				'label'     => '',
				'finish_button_label' => '',
				'pages'     => [],
				'container_classes' => [],
				'custom_css' => false,
				'auto_open' => false,
			];

			$site_guide_config = wp_parse_args( $site_guide_config, $defaults );

			// Sanitize and validate all values.
			foreach ( $site_guide_config as $key => $value ) {

				switch ( $key ) {
					case 'label' :
						if ( ! is_string( $value ) ) {
							return false;
						}

						$site_guide_config[ $key ] = wp_kses( wp_unslash( $value ), wp_kses_allowed_html( 'data' ) );
						break;
					case 'finish_button_label' :
						if ( ! is_string( $value ) ) {
							return false;
						}

						$site_guide_config[ $key ] = wp_kses( wp_unslash( $value ), wp_kses_allowed_html( 'data' ) );
						break;
					case 'pages' :
						if ( ! is_array( $value ) ) {
							return false;
						}

						$page_defaults = [
							'_uid' => wp_unique_id( $key ),
							'_priority' => 10,
							'content' => '',
							'image' => '',
						];
						foreach ( $value as $page_key => $page_details ) {
							$page_details = wp_parse_args( $page_details, $page_defaults );

							$page_details['_priority'] = intval( $page_details['_priority'] );
							$page_details['content'] = wp_kses_post( $this->parse_content_tags( $page_details['content'] ) );
							$page_details['image'] = esc_url_raw( $page_details['image'] );

							$value[ $page_key ] = $page_details;
						}

						$site_guide_config[ $key ] = $value;
						break;
					case 'auto_open' :
						$site_guide_config[ $key ] = (bool) $value;
						break;
					default:
						break;
				}
			}

			return $site_guide_config;
		}

		/**
		 * Replace any content tags present in the content.
		 *
		 * @param string $content
		 *
		 * @return string
		 */
		protected function parse_content_tags( $content ) {
			$original_content = $content;

			// Allow others to alter the content before we do our work
			$content = apply_filters( 'pixelgrade_care/site_guides/parse_content_tags:before', $content );

			// Now we will replace all the supported tags with their value
			// %year%
			$content = str_replace( '%year%', date( 'Y' ), $content );

			// %site-title% or %site_title%
			$content = str_replace( '%site-title%', get_bloginfo( 'name' ), $content );
			$content = str_replace( '%site_title%', get_bloginfo( 'name' ), $content );

			// Handle the current user tags.
			if ( false !== strpos( $content, '%user_first_name%' ) ||
			     false !== strpos( $content, '%user_last_name%' ) ||
			     false !== strpos( $content, '%user_nickname%' ) ||
			     false !== strpos( $content, '%user_display_name%' ) ) {
				$user = wp_get_current_user();

				if ( ! empty( $user ) && ! is_wp_error( $user ) ) {
					// %first_name%
					if ( ! empty( $user->first_name ) ) {
						$content = str_replace( '%user_first_name%', $user->first_name, $content );
					} else {
						// Fallback to display_name.
						$content = str_replace( '%user_first_name%', $user->display_name, $content );
					}
					// %last_name%
					$content = str_replace( '%user_last_name%', $user->last_name, $content );
					// %display_name%
					$content = str_replace( '%user_nickname%', $user->display_name, $content );
					// %nickname%
					$content = str_replace( '%user_display_name%', $user->nickname, $content );
				}
			}

			// %active_theme%
			if ( false !== strpos( $content, '%active_theme%' ) ) {
				$content = str_replace( '%active_theme%', PixelgradeCare_SiteGuides_Conditions::get_active_theme_name(), $content );
			}

			// %active_theme_slug%
			if ( false !== strpos( $content, '%active_theme_slug%' ) ) {
				$content = str_replace( '%active_theme_slug%', PixelgradeCare_SiteGuides_Conditions::get_active_theme_slug(), $content );
			}

			// %active_product_sku%
			if ( false !== strpos( $content, '%active_product_sku%' ) ) {
				$content = str_replace( '%active_product_sku%', PixelgradeCare_SiteGuides_Conditions::get_active_license_main_product_sku(), $content );
			}

			// %customify_version%
			if ( false !== strpos( $content, '%customify_version%' ) ) {
				$content = str_replace( '%customify_version%', PixelgradeCare_SiteGuides_Conditions::get_customify_version(), $content );
			}

			// %style_manager_version%
			if ( false !== strpos( $content, '%style_manager_version%' ) ) {
				$content = str_replace( '%style_manager_version%', PixelgradeCare_SiteGuides_Conditions::get_style_manager_version(), $content );
			}

			// %current_color_palette%
			if ( false !== strpos( $content, '%current_color_palette%' ) ) {
				$content = str_replace( '%current_color_palette%', PixelgradeCare_SiteGuides_Conditions::get_current_color_palette_label(), $content );
			}

			/*
			 * URLs.
			 */
			// %home_url%
			$content = str_replace( '%home_url%', home_url(), $content );

			// %customizer_url%
			$content = str_replace( '%customizer_url%', wp_customize_url(), $content );
			// %customizer_style_manager_url%
			$section_link = add_query_arg( [ 'autofocus[panel]' => 'style_manager_panel' ], admin_url( 'customize.php' ) );
			$content = str_replace( '%customizer_style_manager_url%', $section_link, $content );
			// %customizer_style_manager_colors_url%
			$section_link = add_query_arg( [ 'autofocus[section]' => 'sm_color_palettes_section' ], admin_url( 'customize.php' ) );
			$content = str_replace( '%customizer_style_manager_colors_url%', $section_link, $content );
			// %customizer_style_manager_fonts_url%
			$section_link = add_query_arg( [ 'autofocus[section]' => 'sm_font_palettes_section' ], admin_url( 'customize.php' ) );
			$content = str_replace( '%customizer_style_manager_fonts_url%', $section_link, $content );
			// %customizer_theme_options_url%
			$section_link = add_query_arg( [ 'autofocus[panel]' => 'theme_options_panel' ], admin_url( 'customize.php' ) );
			$content = str_replace( '%customizer_theme_options_url%', $section_link, $content );
			// %customizer_menus_url%
			$section_link = add_query_arg( [ 'autofocus[panel]' => 'nav_menus' ], admin_url( 'customize.php' ) );
			$content = str_replace( '%customizer_menus_url%', $section_link, $content );
			// %customizer_widgets_url%
			$section_link = add_query_arg( [ 'autofocus[panel]' => 'widgets' ], admin_url( 'customize.php' ) );
			$content = str_replace( '%customizer_widgets_url%', $section_link, $content );
			// %customizer_homepage_settings_url%
			$section_link = add_query_arg( [ 'autofocus[section]' => 'static_front_page' ], admin_url( 'customize.php' ) );
			$content = str_replace( '%customizer_homepage_settings_url%', $section_link, $content );
			// %customizer_site_identity_url%
			$section_link = add_query_arg( [ 'autofocus[section]' => 'publish_settings' ], admin_url( 'customize.php' ) );
			$content = str_replace( '%customizer_site_identity_url%', $section_link, $content );

			// %pixelgrade_care_dashboard_url%
			$content = str_replace( '%pixelgrade_care_dashboard_url%', PixelgradeCare_Admin::get_dashboard_url(), $content );
			// %pixelgrade_care_themes_url%
			$content = str_replace( '%pixelgrade_care_themes_url%', PixelgradeCare_Admin::get_themes_url(), $content );

			// Allow others to alter the content after we did our work
			return apply_filters( 'pixelgrade_care/site_guides/parse_content_tags:after', $content, $original_content );
		}

		/**
		 * Disable the Default Welcome Guide Popup when we have a site guide to display.
		 *
		 * @see: https://wordpress.org/plugins/disable-welcome-messages-and-tips/
		 *
		 * @since  1.17.0
		 */
		public function hide_default_core_welcome_guide() {

			if ( get_current_screen()->base == 'post' && $this->get_editor_site_guides() ) {
				?>
				<script>
					window.onload = function(){
						wp.data && wp.data.select( 'core/edit-post' ).isFeatureActive( 'welcomeGuide' ) &&
						wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'welcomeGuide' )
					};
				</script>
			<?php
			}
		}

		/**
		 * Add Contextual help tabs for the rest of the WP dashboard pages.
		 *
		 * @since  1.17.2
		 */
		public function add_tabs() {
			$admin_site_guides = $this->get_admin_site_guides();

			$list_html = '<p>' . esc_html__( 'No guides available right now.', 'pixelgrade_care' ) . '</p>';
			if ( ! empty( $admin_site_guides ) ) {
				$list_html = '<ul>';
				foreach ( $admin_site_guides as $site_guide_id => $admin_site_guide ) {
					$list_html .= '<li><a href="#" class="pixcare-site-guide-link" data-siteguideid="' . esc_attr( $site_guide_id ) . '" >' . esc_html( $admin_site_guide['label'] ) . '</a></li>';
				}
				$list_html .= '</ul>';
			}

			$screen = get_current_screen();
			$screen->add_help_tab( [
				'id'      => 'pixelgrade_care_site_guides_tab',
				'title'   => esc_html__( 'Help Guides', 'pixelgrade_care' ),
				'content' =>
					'<h2>' . esc_html__( 'Help Guides', 'pixelgrade_care' ) . '</h2>' .
					'<p>' . esc_html__( 'A list of quick guides for this context.', 'pixelgrade_care' ) . '</p>' .
					$list_html ,
			] );
		}

		/**
		 * Main PixelgradeCare_Site_Guides Instance
		 *
		 * Ensures only one instance of PixelgradeCare_Site_Guides is loaded or can be loaded.
		 *
		 * @since  1.17.0
		 * @static
		 *
		 * @param PixelgradeCare $parent The main plugin object (the parent).
		 * @param array $args The arguments to initialize the site guides manager.
		 *
		 * @return PixelgradeCare_Site_Guides Main PixelgradeCare_Site_Guides instance
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
		 * @since 1.17.0
		 */
		public function __clone() {

			_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), null );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @since 1.17.0
		 */
		public function __wakeup() {

			_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), null );
		}

	}
endif;
