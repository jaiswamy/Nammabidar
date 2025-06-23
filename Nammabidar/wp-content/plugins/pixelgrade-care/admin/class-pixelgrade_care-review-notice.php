<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PixelgradeCare_ReviewNotification {
	/**
	 * The main plugin object (the parent).
	 * @var     PixelgradeCare
	 * @access  public
	 * @since   1.7.2
	 */
	public $parent = null;

	/**
	 * The markup for each notification, although usually it will be only one.
	 *
	 * @var array
	 * @access protected
	 * @since 1.7.2
	 */
	protected $reviewNotifications = null;

	/**
	 * The only instance of this class.
	 * @var     PixelgradeCare_ReviewNotification
	 * @access  protected
	 * @since   1.7.2
	 */
	protected static $_instance = null;

	public function __construct( $parent ) {
		$this->parent = $parent;

		add_action( 'current_screen', [ $this, 'init' ], 99 );
	}

	public function init() {
		if ( wp_doing_ajax() || ! is_admin() || ! $this->isOurTheme() ) {
			return;
		}

		// Now further restrict the logic to only specific admin pages, so we keep things lean.
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			// We will allow the notifications in the Themes page, Pixelgrade Care dashboard page, .
			if ( ! ( empty( $screen ) || in_array( $screen->base, [ 'themes', 'update-core', 'update' ] ) )
				&& ! PixelgradeCare_Admin::is_pixelgrade_care_dashboard() ) {

				return;
			}
		}

		$this->notificationsSetup();
	}

	public function notificationsSetup() {

		add_action( 'admin_head', [ $this, 'generateMarkup' ], 20 );
		add_action( 'admin_footer', [ $this, 'outputMarkup' ], 10 );

		add_action( 'admin_enqueue_scripts', [ $this, 'outputCSS' ], 10 );
		add_action( 'admin_enqueue_scripts', [ $this, 'outputJS' ], 10 );
	}

	public function generateMarkup() {
		if ( $review_notification_markup = $this->getReviewNotificationMarkup() ) {
			$this->reviewNotifications[] = $review_notification_markup;
		}

		$this->reviewNotifications = apply_filters( 'pixcare_review_notifications', $this->reviewNotifications );
	}

	protected function getReviewNotificationMarkup() {

		$transient = get_site_transient( 'update_themes' );
		if ( empty( $transient->response ) || ! is_array( $transient->response ) ) {
			return '';
		}

		$response = $transient->response;

		$theme_name = PixelgradeCare_Admin::get_original_theme_name();
		$theme_slug = PixelgradeCare_Admin::get_original_theme_slug();

		if ( empty( $response[ $theme_slug ]['new_version'] ) || empty( $response[ $theme_slug ]['structuredChangelog'] ) ) {
			return '';
		}

		$theme_version = $response[ $theme_slug ]['new_version'];
		$versionsChangelog = $response[ $theme_slug ]['structuredChangelog'];

		// Given these different versions, find the latest one that is a meaningful version change (as in it is 'minor' or 'major'; settle for the latest if none is meaningful).
		$meaningfulVersionChangelog = reset( $versionsChangelog );
		foreach ( $versionsChangelog as $versionChangelog ) {
			if ( in_array( $versionChangelog->versionType, [ 'minor', 'major' ] ) ) {
				// If we find a major version, stop and call it a day.
				if ( 'major' === $versionChangelog->versionType ) {
					$meaningfulVersionChangelog = $versionChangelog;
					break;
				}

				// If we come across a minor version and the current meaningful one is not already minor, we will remember it.
				if ( 'minor' !== $meaningfulVersionChangelog->versionType ) {
					$meaningfulVersionChangelog = $versionChangelog;
				}
			}
		}

		// We will ignore patch or other updates types of less importance.
		if ( empty( $meaningfulVersionChangelog->content ) || ! in_array( $meaningfulVersionChangelog->versionType, [ 'minor', 'major' ] ) ) {
			return '';
		}

		$changelog_content = $meaningfulVersionChangelog->content;
		// We need to do a little cleaning and standardizing the received content.
		$changelog_content = $this->cleanStandardizeContent( $changelog_content );

		if ( empty( $changelog_content ) ) {
			return '';
		}

		$review_link = 'https://pixelgrade.com/themes/' . $theme_slug . '/write-review/';

		ob_start(); ?>

        <div class="pxg-review-modal hidden">
            <div class="pxg-review-modal-container">
                <div class="pxg-review-modal__header">
                    <h3 class="pxg-review-modal__header-title section__title"><?php
						/* translators: 1: Theme Version, 2: Theme name */
						echo wp_kses( sprintf( __( 'What\'s new in version %1$s of %2$s?', 'pixelgrade_care' ), $theme_version, $theme_name ), wp_kses_allowed_html( 'post' ) ); ?></h3>
                </div>
                <div class="pxg-review-modal__body">
					<?php echo $changelog_content ?>
                </div>
                <div class="pxg-review-modal__footer">
                    <h3 class="pxg-review-modal__footer-title section__title"><?php
						/* translators: %s: Theme name  */
						echo wp_kses( sprintf( __( 'Enjoying your site with %s?', 'pixelgrade_care' ), $theme_name ), wp_kses_allowed_html( 'post' ) ); ?></h3>
                    <p class="pxg-review-modal__footer-content"><?php esc_html_e( 'We would be grateful if you could take a few moments and tell us about your experience, so far.', 'pixelgrade_care' ); ?></p>
                    <a href="<?php echo esc_url( $review_link ); ?>" target="_blank"
                       class="btn btn--action btn--full btn--core"><?php esc_html_e( 'Add your review on Pixelgrade.com', 'pixelgrade_care' ); ?></a>
                    <a class="btn btn--text btn--full"><?php esc_html_e( 'No thanks', 'pixelgrade_care' ); ?></a>
                </div>
            </div>
        </div>

		<?php

		return ob_get_clean();
	}

	protected function cleanStandardizeContent( $content ) {
		// The content might be with <p>s. Lets make sure that there are no stray <br>s and such.
		if ( false !== strpos( $content, '/p>') ) {
			// Remove <p>s that contain just a <br>
			$content = preg_replace( "#<p>\s*<br\/?>\s*<\/p>#mi", '', $content );
			// Remove double <br>s
			$content = preg_replace( "#<br\/?>\s*<br\/?>#mi", '<br>', $content );
		}

		$content = trim( $content );

		return $content;
	}

	public function outputMarkup() {
		// Allow others to prevent this.
		if ( true !== apply_filters( 'pixcare_output_review_notifications', true ) ) {
			return;
		}

		if ( ! empty( $this->reviewNotifications ) ) {
			foreach ( $this->reviewNotifications as $reviewNotification ) {
				echo $reviewNotification;
			}
		}
	}

	public function outputCSS() {
		// Allow others to prevent this.
		if ( true !== apply_filters( 'pixcare_output_review_notifications', true ) ) {
			return;
		}

		wp_register_style( $this->parent->get_plugin_name() . '-review_notice_css', plugin_dir_url( $this->parent->file ) . 'admin/css/review-notice.css', [], $this->parent->get_version(), 'all' );
		wp_enqueue_style( $this->parent->get_plugin_name() . '-review_notice_css' );
	}

	public function outputJS() {
		// Allow others to prevent this.
		if ( true !== apply_filters( 'pixcare_output_review_notifications', true ) ) {
			return;
		}

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_register_script( $this->parent->get_plugin_name() . '-review_notice_js', plugin_dir_url( $this->parent->file ) . 'admin/js/review-notice' . $suffix . '.js', [
			'jquery',
		] );
		wp_enqueue_script( $this->parent->get_plugin_name() . '-review_notice_js' );

		wp_localize_script( $this->parent->get_plugin_name() . '-review_notice_js', 'pxgReviewNotice', [
			'activeThemeSlug' => basename( get_template_directory() ), // This is the slug that the core's update logic refers to.
		] );
	}

	public function isOurTheme() {
		// Determine if the current active theme is one of our themes.
		$current_theme = wp_get_theme( get_template() );

		if ( strtolower( $current_theme->get( 'Author' ) ) === 'pixelgrade' ||
		     false !== strpos( strtolower( $current_theme->get( 'ThemeURI' ) ), 'pixelgrade' ) ||
		     false !== strpos( strtolower( $current_theme->get( 'AuthorURI' ) ), 'pixelgrade' ) ) {
			return true;
		}

		return false;
	}

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
