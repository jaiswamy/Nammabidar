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
class PixelgradeCare_SetupWizard {

	/**
	 * The main plugin object (the parent).
	 * @var     PixelgradeCare
	 * @access  public
	 * @since     1.3.0
	 */
	public $parent = null;

	/**
	 * The only instance.
	 * @var     PixelgradeCare_SetupWizard
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
		// Allow others to disable this module
		if ( false === apply_filters( 'pixcare_allow_setup_wizard_module', true ) ) {
			return;
		}

		$this->register_hooks();
	}

	/**
	 * Register the hooks related to this module.
	 */
	public function register_hooks() {
		add_action( 'current_screen', [ $this, 'add_tabs' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'setup_wizard' ] );

		// Handle the previous URL for the setup wizard:
		// index.php?page=pixelgrade_care-setup-wizard
		// instead of the new
		// admin.php?page=pixelgrade_care-setup-wizard
		add_action( 'admin_page_access_denied', [ $this, 'redirect_to_correct_url' ], 0 );
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
				'<p><a href="' . esc_url( PixelgradeCare_SetupWizard::get_setup_wizard_url() ) . '" class="button button-primary">' . esc_html__( 'Go to setup wizard (via Pixelgrade Care)', 'pixelgrade_care' ) . '</a></p>'

		] );
	}

	public function add_admin_menu() {
		add_submenu_page( 'pixelgrade_care', '', '', 'manage_options', 'pixelgrade_care-setup-wizard', null );
	}

	public static function get_setup_wizard_url() {
		return admin_url( 'admin.php?page=pixelgrade_care-setup-wizard' );
	}

	public function setup_wizard() {
		$allow_setup_wizard = self::is_pixelgrade_care_setup_wizard() && current_user_can( 'manage_options' );
		if ( false === apply_filters( 'pixcare_allow_setup_wizard_module', $allow_setup_wizard ) ) {
			return;
		}

		$rtl_suffix = is_rtl() ? '-rtl' : '';
		wp_enqueue_style( $this->parent->get_plugin_name(), plugin_dir_url( $this->parent->file ) . 'admin/css/pixelgrade_care-admin' . $rtl_suffix . '.css', [ 'dashicons' ], $this->parent->get_version(), 'all' );

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_enqueue_script( 'plugin-install' );
		wp_enqueue_script( 'updates' );
		wp_enqueue_script( $this->parent->get_plugin_name(). '-setup-wizard', plugin_dir_url( $this->parent->file ) . 'admin/js/setup_wizard' . $suffix . '.js', [
			'jquery',
			'wp-util',
			'wp-a11y',
			'plugin-install',
			'updates',
		], $this->parent->get_version(), true );

		PixelgradeCare_Admin::localize_js_data( 'pixelgrade_care-setup-wizard' );

		update_option( 'pixelgrade_care_version', $this->parent->get_version() );
		// Delete redirect transient
		$this->delete_redirect_transient();

		ob_start();
		$this->setup_wizard_header();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;
	}

	public function redirect_to_correct_url() {
		if ( ! empty( $_GET['page'] ) && 'pixelgrade_care-setup-wizard' === $_GET['page'] && 0 === strpos( wp_unslash( $_SERVER['REQUEST_URI'] ), '/wp-admin/index.php' ) ) {
			wp_safe_redirect( PixelgradeCare_SetupWizard::get_setup_wizard_url() );
			die;
		}
	}

	/**
	 * Setup Wizard Header.
	 */
	public function setup_wizard_header() {
		global $hook_suffix, $current_screen;

		if ( empty( $current_screen ) ) {
			set_current_screen();
		} ?><!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width"/>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<title><?php esc_html_e( 'Pixelgrade Care &rsaquo; Setup Wizard', 'pixelgrade_care' ); ?></title>
			<script type="text/javascript">
				var ajaxurl = '<?php echo admin_url( 'admin-ajax.php', 'relative' ); ?>',
					pagenow = 'plugins';
			</script>
			<?php
			wp_enqueue_style( 'colors' );
			wp_enqueue_style( 'ie' );
			wp_enqueue_script( 'utils' );
			wp_enqueue_script( 'svg-painter' );

			/**
			 * Fires when styles are printed for a specific admin page based on $hook_suffix.
			 *
			 * @since 2.6.0
			 */
			do_action( "admin_print_styles-{$hook_suffix}" );

			/**
			 * Fires when styles are printed for all admin pages.
			 *
			 * @since 2.6.0
			 */
			do_action( 'admin_print_styles' );
			?>
		</head>
		<body class="pixelgrade_care-setup wp-core-ui">

		<?php
	}

	/**
	 * Output the content for the current step.
	 */
	public function setup_wizard_content() { ?>
		<div class="pixelgrade_care-wrapper">
			<div id="pixelgrade_care_setup_wizard"></div>
			<div id="valdationError"></div>
		</div>
	<?php }

	public function setup_wizard_footer() { ?>
		<?php
		wp_print_scripts( 'pixelgrade_care_wizard' );
		wp_print_footer_scripts();
		wp_print_update_row_templates();
		wp_print_admin_notice_templates(); ?>
		</body>
		</html>
		<?php
	}

	/** === HELPERS=== */

	public static function is_pixelgrade_care_setup_wizard() {
		if ( ! empty( $_GET['page'] ) && 'pixelgrade_care-setup-wizard' === $_GET['page'] ) {
			return true;
		}

		return false;
	}

	public function delete_redirect_transient() {
		$delete_transient = delete_site_transient( '_pixcare_activation_redirect' );

		return $delete_transient;
	}

	/**
	 * Main PixelgradeCareSetupWizard Instance
	 *
	 * Ensures only one instance of PixelgradeCareSetupWizard is loaded or can be loaded.
	 *
	 * @since  1.3.0
	 * @static
	 * @param  object $parent Main PixelgradeCare instance.
	 * @return object Main PixelgradeCareSetupWizard instance
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
