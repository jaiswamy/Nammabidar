<?php
/**
 * This is the base class for handling a custom post type.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

abstract class PixelgradeCare_CPT {
	const THEME_SUPPORTS = '';

	const CUSTOM_POST_TYPE = '';

	const OPTION_NAME = '';
	public $OPTION_TITLE = '';
	const OPTION_READING_SETTING = '';
	const OPTION_ARCHIVE_PAGE_SETTING = '';

	/**
	 * Holds and array of extending class instances (Singleton Factory).
	 * @since   1.12.0
	 * @var     PixelgradeCare_CPT[]
	 * @access  protected
	 */
	protected static $_instances = [];

	/**
	 * The main plugin object (the parent).
	 * @since     1.12.0
	 * @var     PixelgradeCare
	 * @access    public
	 */
	public $parent = null;

	public $version = '0.1';

	/**
	 * Constructor.
	 *
	 * @since 1.12.0
	 *
	 * @param PixelgradeCare $parent
	 */
	protected function __construct( $parent ) {
		$this->parent = $parent;

		$this->register_hooks();
	}

	/**
	 * Register hooks needed to fire up the logic.
	 *
	 * @since 1.12.0
	 * @return void
	 */
	public function register_hooks() {
		// Determine if we should activate theme support.
		// Go with a 12 priority to come after themes, but not too late.
		add_action( 'after_setup_theme', [ $this, 'setup' ], 12 );

		// Initialize the logic.
		add_action( 'init', [ $this, 'init' ] );
	}

	/**
	 * Given the contextual info available (the Pixelgrade Care theme config, license, etc.),
	 * activate the theme support for this CPT.
	 *
	 * @since 1.12.0
	 * @return void
	 */
	public function setup() {
		// Fist, check if the Pixelgrade Care theme config instructs us to activate this CPT.
		$pixcare_config = PixelgradeCare_Admin::get_theme_config();
		if ( ! empty( $pixcare_config['customPostTypes'][ static::CUSTOM_POST_TYPE ] ) ) {
			if ( true === $pixcare_config['customPostTypes'][ static::CUSTOM_POST_TYPE ] ) {
				add_theme_support( static::THEME_SUPPORTS );
			} else if ( ! empty( $pixcare_config['customPostTypes'][ static::CUSTOM_POST_TYPE ]['enable'] ) ) {
				// We have been given a more explicit instruction.
				// Attach the data to the theme support.
				add_theme_support( static::THEME_SUPPORTS, $pixcare_config['customPostTypes'][ static::CUSTOM_POST_TYPE ] );
			}
		}

		// Second, check if the current license say something about this CPT, or CPTs in general.
		// This will overwrite the theme config instructions.
		if ( current_theme_supports( static::THEME_SUPPORTS ) && ! pixelgrade_user_has_access( 'pro-features' ) ) {
			// If the user doesn't have access to pro-features, remove the support.
			remove_theme_support( static::THEME_SUPPORTS );
		}
	}

	/**
	 * Conditionally hook into WordPress.
	 *
	 * Setup user option for enabling CPT.
	 * If user has CPT enabled, show in admin.
	 *
	 * @since 1.12.0
	 * @return bool False if the custom post type is not supported or active. True otherwise.
	 */
	public function init() {
		// Add an option to enable the CPT.
		add_action( 'admin_init', [ $this, 'settings_api_init' ] );

		// Check on theme switch if theme supports CPT and setting is disabled (after the license refresh hook).
		add_action( 'after_switch_theme', [ $this, 'activation_post_type_support' ], 40 );

		// Make sure the post types are loaded for imports.
		add_action( 'import_start', [ $this, 'register_post_types' ] );

		// Add to REST API post type allowed list.
		add_filter( 'rest_api_allowed_post_types', [ $this, 'allow_type_in_rest_api' ] );

		// Bail early if Portfolio option is not set and the theme doesn't declare support.
		$setting = static::get_option_and_ensure_autoload( static::OPTION_NAME, '0' );
		if ( empty( $setting ) && ! $this->site_supports_custom_post_type() ) {
			return false;
		}

		// CPT magic.
		$this->register_post_types();
		add_action( sprintf( 'add_option_%s', static::OPTION_NAME ), [ $this, 'flush_rules_on_enable' ], 10 );
		add_action( sprintf( 'update_option_%s', static::OPTION_NAME ), [ $this, 'flush_rules_on_enable' ], 10 );
		add_action( sprintf( 'publish_%s', static::CUSTOM_POST_TYPE ), [
			$this,
			'flush_rules_on_first_post',
		] );
		// Run after the post type support activation in self::activation_post_type_support().
		add_action( 'after_switch_theme', [ $this, 'flush_rules_on_switch' ], 50 );

		// Adjust CPT archive and custom taxonomies to obey CPT reading setting.
		add_filter( 'pre_get_posts', [ $this, 'query_reading_setting' ] );

		// If CPT was enabled programmatically and no CPT items exist when user switches away, disable.
		if ( $setting && $this->site_supports_custom_post_type() ) {
			add_action( 'switch_theme', [ $this, 'deactivation_post_type_support' ] );
		}

		// Handle nav_menu current active item.
		add_filter( 'wp_nav_menu_objects', [ $this, 'nav_menu_item_classes' ], 10 );
		// Also filter this option on starter content import to make sure it has the right value.
		add_filter( 'pixcare_sce_import_post_option_' . static::OPTION_ARCHIVE_PAGE_SETTING, [
			$this,
			'starter_content_filter_post_option_archive_page',
		], 10, 2 );

		return true;
	}

	/**
	 * Add a checkbox field in 'Settings' > 'Writing' for enabling CPT functionality.
	 *
	 * @since 1.12.0
	 * @return void
	 */
	public function settings_api_init() {
		global $wp_settings_sections;

		// Add the CPTs settings section if it is not already added.
		if ( empty( $wp_settings_sections['writing']['pixcare_cpt_section'] ) ) {
			add_settings_section(
				'pixcare_cpt_section',
				'<span id="cpts-options">' . __( 'Your Custom Content Types', 'pixelgrade_care' ) . '</span>',
				[ $this, 'cpts_section_callback' ],
				'writing'
			);
		}

		add_settings_field(
			static::OPTION_NAME,
			'<span class="cpt-options">' . $this->OPTION_TITLE . '</span>',
			[ $this, 'setting_html' ],
			'writing',
			'pixcare_cpt_section'
		);
		register_setting(
			'writing',
			static::OPTION_NAME,
			'intval'
		);

		// Check if CPT is enabled first so that intval doesn't get set to NULL on re-registering
		if ( get_option( static::OPTION_NAME, '0' ) || current_theme_supports( static::THEME_SUPPORTS ) ) {
			register_setting(
				'writing',
				static::OPTION_READING_SETTING,
				'intval'
			);

			register_setting(
				'writing',
				static::OPTION_ARCHIVE_PAGE_SETTING,
				'intval'
			);
		}

		do_action( 'pixelgrade_care/cpt_setting_api_init', static::CUSTOM_POST_TYPE, $this );
	}

	/**
	 * CPTs Writing Settings Description.
	 *
	 * @since 1.12.0
	 * @return void
	 */
	public function cpts_section_callback() {
		?>
		<p>
			<?php esc_html_e( 'Use these settings to display different types of content on your site.', 'pixelgrade_care' ); ?>
			<a target="_blank" rel="noopener noreferrer"
			   href="#"><?php esc_html_e( 'Learn More', 'pixelgrade_care' ); ?></a>
		</p>
		<?php
	}

	/**
	 * HTML code to display a checkbox true/false option for the Portfolio CPT setting.
	 *
	 * @since 1.12.0
	 * @return void
	 */
	public function setting_html() {
		if ( current_theme_supports( static::THEME_SUPPORTS ) ) { ?>
			<p><?php
				/* translators: %s is the name of a custom post type such as "portfolio" */
				printf( __( 'Your theme supports <strong>%s</strong>', 'pixelgrade_care' ), static::CUSTOM_POST_TYPE ); ?></p>
		<?php } else { ?>
			<label for="<?php echo esc_attr( static::OPTION_NAME ); ?>">
				<input name="<?php echo esc_attr( static::OPTION_NAME ); ?>"
				       id="<?php echo esc_attr( static::OPTION_NAME ); ?>"
					<?php echo checked( get_option( static::OPTION_NAME, '0' ), true, false ); ?>
					   type="checkbox" value="1"/>
				<?php printf( esc_html__( 'Enable %s for this site.', 'pixelgrade_care' ), ucwords( static::CUSTOM_POST_TYPE, " \t\r\n\f\v-_" ) ); ?>
			</label>
		<?php }
		if ( get_option( static::OPTION_NAME, '0' ) || current_theme_supports( static::THEME_SUPPORTS ) ) {
			printf( '<p><label for="%1$s">%2$s</label></p>',
				esc_attr( static::OPTION_READING_SETTING ),
				/* translators: %1$s is the CPT name, %2$s is replaced with an input field for numbers */
				sprintf( __( '%1$s pages display at most %2$s posts', 'pixelgrade_care' ),
					ucwords( static::CUSTOM_POST_TYPE, " \t\r\n\f\v-_" ),
					sprintf( '<input name="%1$s" id="%1$s" type="number" step="1" min="1" value="%2$s" class="small-text" />',
						esc_attr( static::OPTION_READING_SETTING ),
						esc_attr( get_option( static::OPTION_READING_SETTING, '10' ) )
					)
				)
			);

			$current_selected_page = get_option( static::OPTION_ARCHIVE_PAGE_SETTING, 0 );
			$page_select = '<select name="' . static::OPTION_ARCHIVE_PAGE_SETTING . '" id="' . static::OPTION_ARCHIVE_PAGE_SETTING . '" >';
			$page_select .= '<option value="0" ' . selected( $current_selected_page, 0, false ) . '>' . esc_html__( 'None', 'pixelgrade_care' ) . '</option>';
			foreach ( get_pages() as $page ) {
				$page_select .= '<option value="' . esc_attr( $page->ID ) . '" ' . selected( $current_selected_page, $page->ID, false ) . '>' . esc_html( $page->post_title ) . '</option>';
			}
			$page_select .= '</select>';

			printf( '<p><label for="%1$s">%2$s %3$s</label><br><span class="description">%4$s</span><br><br></p>',
				esc_attr( static::OPTION_ARCHIVE_PAGE_SETTING ),
				wp_kses_data( __( 'Optionally, set a <strong>static page</strong> that you use as the main post type archive:', 'pixelgrade_care' ) ),
				$page_select,
				wp_kses_data( __( '<strong>We will not automatically output posts</strong> on the selected page, but use it as <strong>a hint</strong> to properly mark the current menu item.', 'pixelgrade_care' ) )
			);
		}

		do_action( 'pixelgrade_care/cpt_setting_html/' . static::CUSTOM_POST_TYPE, $this );
		do_action( 'pixelgrade_care/cpt_setting_html', static::CUSTOM_POST_TYPE, $this );
	}

	/**
	 * Should this Custom Post Type be made available?
	 *
	 * @since 1.12.0
	 * @return bool
	 */
	public function site_supports_custom_post_type() {
		// If the current theme requests it.
		if ( current_theme_supports( static::THEME_SUPPORTS ) || get_option( static::OPTION_NAME, '0' ) ) {
			return true;
		}

		// Otherwise, say no unless something wants to filter us to say yes.
		return (bool) apply_filters( 'pixelgrade_care/enable_cpt', false, static::CUSTOM_POST_TYPE );
	}

	/*
	 * Flush permalinks when CPT option is turned on/off.
	 *
	 * @since 1.12.0
	 */
	public function flush_rules_on_enable() {
		flush_rewrite_rules();
	}

	/*
	 * Count published posts and flush permalinks when the first post is published.
	 *
	 * @since 1.12.0
	 */
	public function flush_rules_on_first_post() {
		$transient_name = 'pixcare-' . static::CUSTOM_POST_TYPE . '-count-cache';
		$projects       = get_transient( $transient_name );

		if ( false === $projects ) {
			flush_rewrite_rules();
			$projects = (int) wp_count_posts( static::CUSTOM_POST_TYPE )->publish;

			if ( ! empty( $projects ) ) {
				set_transient( $transient_name, $projects, HOUR_IN_SECONDS * 12 );
			}
		}
	}

	/*
	 * Flush permalinks when CPT supported theme is activated.
	 *
	 * @since 1.12.0
	 */
	public function flush_rules_on_switch() {
		if ( current_theme_supports( static::THEME_SUPPORTS ) ) {
			flush_rewrite_rules();
		}
	}

	/**
	 * On plugin/theme activation, check if current theme supports CPT.
	 *
	 * @since 1.12.0
	 */
	public static function activation_post_type_support() {
		if ( current_theme_supports( static::THEME_SUPPORTS ) ) {
			update_option( static::OPTION_NAME, '1' );
		}
	}

	/**
	 * On theme switch, check if CPT item exists and disable if not.
	 *
	 * @since 1.12.0
	 */
	public function deactivation_post_type_support() {
		$portfolios = get_posts( [
			'fields'           => 'ids',
			'posts_per_page'   => 1,
			'post_type'        => static::CUSTOM_POST_TYPE,
			'suppress_filters' => false,
		] );

		if ( empty( $portfolios ) ) {
			update_option( static::OPTION_NAME, '0' );
		}
	}

	/**
	 * Fix active class in nav for post type archive static page.
	 *
	 * @param array $menu_items Menu items.
	 * @return array
	 */
	public function nav_menu_item_classes( $menu_items ) {

		$archive_static_page = (int) get_option( static::OPTION_ARCHIVE_PAGE_SETTING, 0 );
		if ( empty( $archive_static_page ) ) {
			return $menu_items;
		}

		$page_for_posts = (int) get_option( 'page_for_posts' );

		if ( ! empty( $menu_items ) && is_array( $menu_items ) ) {
			foreach ( $menu_items as $key => $menu_item ) {
				$classes = (array) $menu_item->classes;
				$menu_id = (int) $menu_item->object_id;

				// Unset active class for blog page.
				if ( $page_for_posts === $menu_id ) {
					$menu_items[ $key ]->current = false;

					if ( in_array( 'current_page_parent', $classes, true ) ) {
						unset( $classes[ array_search( 'current_page_parent', $classes, true ) ] );
					}

					if ( in_array( 'current-menu-item', $classes, true ) ) {
						unset( $classes[ array_search( 'current-menu-item', $classes, true ) ] );
					}
				} elseif ( ( is_post_type_archive( static::CUSTOM_POST_TYPE ) || is_page( $archive_static_page ) )
				           && $archive_static_page === $menu_id && 'page' === $menu_item->object ) {
					// Set active state if this is the post type archive page link.
					$menu_items[ $key ]->current = true;
					$classes[]                   = 'current-menu-item';
					$classes[]                   = 'current_page_item';

				} elseif ( is_singular( static::CUSTOM_POST_TYPE ) && $archive_static_page === $menu_id ) {
					// Set parent state if this is a post type page.
					$classes[] = 'current_page_parent';
				}

				$menu_items[ $key ]->classes = array_unique( $classes );
			}
		}

		return $menu_items;
	}

	/**
	 * Replace the value of the archive static page option with the id of the local page.
	 *
	 * @param string|int $value
	 * @param string $demo_key
	 *
	 * @return string|int
	 */
	public function starter_content_filter_post_option_archive_page( $value, $demo_key ) {
		$starter_content = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );
		if ( isset( $starter_content[ $demo_key ]['post_types']['page'][ $value ] ) ) {
			return $starter_content[ $demo_key ]['post_types']['page'][ $value ];
		}

		return $value;
	}

	/**
	 * Register Post Type.
	 *
	 * @since 1.12.0
	 */
	abstract protected function register_post_types();

	/**
	 * Follow CPT reading setting on CPT archive and taxonomy pages.
	 *
	 * @since 1.12.0
	 */
	public function query_reading_setting( $query ) {
		if ( ( ! is_admin() || ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX ) )
		     && $query->is_main_query()
		     && $query->is_post_type_archive( static::CUSTOM_POST_TYPE )
		) {
			$query->set( 'posts_per_page', get_option( static::OPTION_READING_SETTING, '10' ) );
		}
	}

	/**
	 * Add to REST API post type allowed list.
	 *
	 * @since 1.12.0
	 */
	public function allow_type_in_rest_api( $post_types ) {
		$post_types[] = static::CUSTOM_POST_TYPE;

		return $post_types;
	}

	/**
	 * Returns the requested option, and ensures it's autoloaded in the future.
	 * This does _not_ adjust the prefix in any way (does not prefix jetpack_%)
	 *
	 * @since 1.12.0
	 * @static
	 *
	 * @param string $name    Option name.
	 * @param mixed  $default (optional).
	 *
	 * @return mixed
	 */
	public static function get_option_and_ensure_autoload( $name, $default ) {
		$value = get_option( $name );

		if ( false === $value && false !== $default ) {
			add_option( $name, $default );
			$value = $default;
		}

		return $value;
	}

	/**
	 * Main instance
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @since  1.12.0
	 * @static
	 *
	 * @param PixelgradeCare $parent The main plugin object (the parent).
	 *
	 * @return PixelgradeCare_CPT Main instance
	 */
	public static function instance( $parent ) {

		if ( ! isset( self::$_instances[ static::class ] ) ) {
			self::$_instances[ static::class ] = new static( $parent );
		}

		return self::$_instances[ static::class ];
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.12.0
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), null );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.12.0
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), null );
	}
}
