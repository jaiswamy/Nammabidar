<?php
/**
 * This is the base class for handling the registration of sidebars configured through the theme config.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class PixelgradeCare_Sidebars {

	/**
	 * Holds the only instance of this class.
	 * @since   1.13.0
	 * @var     null|PixelgradeCare_Sidebars
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

	/**
	 * Sidebars to be processed and maybe registered.
	 *
	 * @access  protected
	 * @since   1.13.0
	 * @var     array|null
	 */
	protected $sidebars = null;

	public $version = '0.1';

	/**
	 * Constructor.
	 *
	 * @since 1.13.0
	 *
	 * @param PixelgradeCare $parent
	 * @param array          $args
	 */
	protected function __construct( $parent, $args = [] ) {
		$this->parent = $parent;

		$this->init( $args );
	}

	/**
	 * Initialize the sidebars manager.
	 *
	 * @since  1.13.0
	 *
	 * @param array   $args     {
	 *
	 * @type    array $sidebars Array of array of sidebar arguments to be passed to register_sidebar().
	 *  }
	 */
	public function init( $args ) {
		if ( isset( $args['sidebars'] ) && is_array( $args['sidebars'] ) ) {
			$this->sidebars = $args['sidebars'];
		}

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
		// Add action to load remote block patterns.
		// We need this priority to come before wp_widgets_init() which is hooked at priority 1.
		add_action( 'init', [ $this, 'maybe_load_config_sidebars' ], 0 );

		// Add actions to register sidebars.
		add_action( 'widgets_init', [ $this, 'register_sidebars' ], 30 );
	}

	/**
	 * Load the sidebars from the theme config if they weren't provided on class instantiation.
	 *
	 * @since 1.13.0
	 * @return void
	 */
	public function maybe_load_config_sidebars() {
		// By default, we want Nova Blocks to be active. But we let others have a say as well.
		if ( ! apply_filters( 'pixelgrade_care/register_remote_sidebars', function_exists( 'novablocks_plugin_setup' ) ) ) {
			return;
		}

		if ( $this->sidebars === null ) {
			$this->sidebars = $this->get_remote_sidebars();
		}
	}

	/**
	 * Get the remote sidebars configuration.
	 *
	 * @since 1.13.0
	 *
	 * @param bool $skip_cache Optional. Whether to use the cached config or fetch a new one.
	 *
	 * @return array
	 */
	protected function get_remote_sidebars( $skip_cache = false ) {
		$remote_sidebars = [];
		// Fist, check if the Pixelgrade Care theme config instructs us to register sidebars.
		$pixcare_config = PixelgradeCare_Admin::get_theme_config( $skip_cache );
		if ( isset( $pixcare_config['sidebars'] ) && is_array( $pixcare_config['sidebars'] ) ) {
			$remote_sidebars = $pixcare_config['sidebars'];
		}

		return apply_filters( 'pixelgrade_care/get_remote_sidebars', $remote_sidebars );
	}

	public function register_sidebars() {
		if ( empty( $this->sidebars ) || ! is_array( $this->sidebars ) ) {
			return;
		}

		$default_args = [
			'name'           => '', // It is required. Leave it for the key.
			'id'             => '', // It is required. Leave it for the key.
			'description'    => '',
			'class'          => '',
			'before_widget'  => '<section id="%1$s" class="widget %2$s">',
			'after_widget'   => '</section>',
			'before_title'   => '<h2 class="widget-title">',
			'after_title'    => '</h2>',
			'before_sidebar' => '',
			'after_sidebar'  => '',
			'show_in_rest'   => true,
		];

		$default_args = apply_filters( 'pixelgrade_care/sidebars_default_args', $default_args );

		foreach ( $this->sidebars as $sidebar_args ) {
			// We want at least the name and id.
			if ( empty( $sidebar_args['name'] ) || empty( $sidebar_args['id'] ) ) {
				continue;
			}

			// Make sure that only allowed keys are present.
			$args = array_intersect_key( $sidebar_args, $default_args );

			// Fill with defaults, while allowing others to change them on a per-sidebar basis.
			$args = wp_parse_args( $args, apply_filters( 'pixelgrade_care/sidebar_default_args', $default_args, $args ) );
			// Allow others to change the sidebar args before register.
			$args = apply_filters( 'pixelgrade_care/sidebar_args', $args );

			register_sidebar( $args );
		}
	}

	/**
	 * Main PixelgradeCare_Sidebars Instance
	 *
	 * Ensures only one instance of PixelgradeCare_Sidebars is loaded or can be loaded.
	 *
	 * @since  1.13.0
	 * @static
	 *
	 * @param PixelgradeCare $parent The main plugin object (the parent).
	 * @param array          $args   The arguments to initialize the block patterns manager.
	 *
	 * @return PixelgradeCare_Sidebars Main PixelgradeCare_Sidebars instance
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
