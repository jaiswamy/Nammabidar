<?php
/**
 * This is the class for handling custom post types metafields.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @package   CPT Metafields
 * @author    Pixelgrade <contact@pixelgrade.com>
 */
class PixelgradeCare_CPT_Metafields {

	const OPTION_NAME_ENABLE = 'pixcare_cpt_metafields_enable';
	const OPTION_NAME_MANAGEMENT = 'pixcare_cpt_metafields_enable_management';
	const OPTION_NAME_FIELDS_LIST = 'pixcare_cpt_metafields_fields_list';
	const META_KEY_PREFIX = 'pixfield';

	/**
	 * Instance of this class.
	 * @since     1.12.2
	 * @var      PixelgradeCare_CPT_Metafields
	 */
	protected static $_instance = null;

	/**
	 * The main plugin object (the parent).
	 * @since     1.12.2
	 * @var     PixelgradeCare
	 * @access    public
	 */
	public $parent = null;

	/**
	 * General metafields configuration per each CPT.
	 *
	 * @since     1.12.2
	 * @var array List keyed by each CPT name (slug).
	 */
	protected static $general_config = [];

	/**
	 * The fields list configuration per each CPT.
	 *
	 * @since     1.12.2
	 * @var array List keyed by each CPT name (slug).
	 */
	public static $fields_list = [];

	/**
	 * Constructor.
	 *
	 * @since 1.12.2
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
	 * @since 1.12.2
	 * @return void
	 */
	public function register_hooks() {
		// Determine if we should activate theme support.
		// Go with a 12 priority to come after themes, but not too late.
		add_action( 'after_setup_theme', [ $this, 'setup' ], 12 );

		// Initialize the logic, after each CPT has been registered (at priority 10).
		add_action( 'init', [ $this, 'init' ], 12 );
	}

	/**
	 * Setup the CPT metafields data.
	 *
	 * @since 1.12.2
	 * @return void
	 */
	public function setup() {
		// Set up the metafields fields list config.
		$fields_list = get_option( self::OPTION_NAME_FIELDS_LIST, false );
		if ( false === $fields_list ) {
			// The option is not set. We will set it now.
			self::activation_cpt_metafields_list();
		}
		$fields_list = self::get_option_and_ensure_autoload( self::OPTION_NAME_FIELDS_LIST, [] );
		foreach ( $fields_list as $cpt_name => $fields_config ) {
			// Fire up a CPT specific hook and a general one.
			self::$fields_list[ $cpt_name ] = apply_filters( 'pixelgrade_care/cpt_metafields/fields_list/' . $cpt_name, $fields_config );
			self::$fields_list[ $cpt_name ] = apply_filters( 'pixelgrade_care/cpt_metafields/cpt_fields_list', $fields_config, $cpt_name );
		}

		// Filter the entire fields list, for all CPTs.
		self::$fields_list = apply_filters( 'pixelgrade_care/cpt_metafields/fields_list', self::$fields_list );

		// Process the Pixelgrade Care theme config in regard to metafields.
		$pixcare_config = PixelgradeCare_Admin::get_theme_config();
		if ( ! empty( $pixcare_config['customPostTypes'] ) && is_array( $pixcare_config['customPostTypes'] ) ) {
			foreach ( $pixcare_config['customPostTypes'] as $cpt_name => $cpt_details ) {
				if ( ! is_array( $cpt_details )
				     || empty( $cpt_details['enable'] )
				     || empty( $cpt_details['metafields'] )
				     || ! is_array( $cpt_details['metafields'] )
				) {
					continue;
				}

				/**
				 * Now extract the metafields config from the theme config.
				 */
				// Determine the data about whether we allow the user to manage the fields list.
				if ( ! empty( $cpt_details['metafields']['config']['allowFieldsManagement'] ) ) {
					self::$general_config[ $cpt_name ]['allow_fields_management'] = $cpt_details['metafields']['config']['allowFieldsManagement'];
				} else {
					self::$general_config[ $cpt_name ]['allow_fields_management'] = false;
				}

				// Fire up a CPT specific hook and a general one.
				self::$general_config[ $cpt_name ] = apply_filters( 'pixelgrade_care/cpt_metafields/general_config/' . $cpt_name, self::$general_config[ $cpt_name ] );
				self::$general_config[ $cpt_name ] = apply_filters( 'pixelgrade_care/cpt_metafields/cpt_general_config', self::$general_config[ $cpt_name ], $cpt_name );
			}
		}

		$enabled_management = get_option( static::OPTION_NAME_MANAGEMENT, false );
		if ( false === $enabled_management ) {
			// The option is not set. We will set it now.
			self::activation_cpt_metafields_management_support();
		} else {
			// Go through each CPT for which fields management is configured, and make sure that its general config
			// is in place, even if the Pixelgrade Care config didn't provide any details.
			$enabled_management = self::get_option_and_ensure_autoload( self::OPTION_NAME_MANAGEMENT, [] );
			if ( ! empty( $enabled_management ) ) {
				foreach ( $enabled_management as $cpt_name => $enabled ) {
					// Make sure that user settings take effect.
					if ( empty( $enabled ) && ! empty( self::$general_config[ $cpt_name ]['allow_fields_management'] ) ) {
						self::$general_config[ $cpt_name ]['allow_fields_management'] = false;
						continue;
					}

					if ( empty( self::$general_config[ $cpt_name ] ) ) {
						self::$general_config[ $cpt_name ] = [];
					}

					if ( empty( self::$general_config[ $cpt_name ]['allow_fields_management'] ) ) {
						self::$general_config[ $cpt_name ]['allow_fields_management'] = ! empty( $enabled );
					}
				}
			}
		}

		// Filter the entire general config, for all CPTs.
		self::$general_config = apply_filters( 'pixelgrade_care/cpt_metafields/general_config', self::$general_config );
	}

	/**
	 * Conditionally hook into WordPress.
	 *
	 * Setup user option for enabling CPT metafields.
	 * If user has CPT metafields enabled, show in admin.
	 *
	 * @since 1.12.2
	 * @return bool False if no CPT supports metafields. True otherwise.
	 */
	public function init() {
		// Add an options to enable CPT metafields.
		add_action( 'pixelgrade_care/cpt_setting_api_init', [ $this, 'settings_api_init' ], 10, 2 );

		// On theme switch, check if CPTs support metafields and set the setting appropriate value.
		// The remote config should have been refreshed by this priority.
		add_action( 'after_switch_theme', [ $this, 'activation_cpt_metafields_list' ], 100 );
		add_action( 'after_switch_theme', [ $this, 'activation_cpt_metafields_support' ], 102 );
		add_action( 'after_switch_theme', [ $this, 'activation_cpt_metafields_management_support' ], 104 );

		// Bail early if no CPT supports metafields.
		$setting = static::get_option_and_ensure_autoload( static::OPTION_NAME_ENABLE, [] );
		if ( empty( $setting ) && ! self::site_supports_metafields() ) {
			return false;
		}

		// Load admin stylesheet and JavaScript.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

		// Save fields meta data on post save.
		add_action( 'save_post', [ $this, 'save_post_meta_data' ] );
		// Metafields
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ], 10, 2 );
		// Fields management modal.
		add_action( 'add_meta_boxes', [ $this, 'add_modal_meta_box' ], 10, 2 );

		/**
		 * AJAX Callbacks
		 */
		add_action( 'wp_ajax_save_pixcare_cpt_fields_list', [ $this, 'ajax_update_metafields_list' ] );
		add_action( 'wp_ajax_pixcare_cpt_field_autocomplete', [ $this, 'ajax_field_get_autocomplete' ] );
		// Only logged in users can access AJAX callbacks.
		add_action( 'wp_ajax_nopriv_save_pixcare_cpt_fields_list', [ $this, 'ajax_no_access' ] );
		add_action( 'wp_ajax_nopriv_pixcare_cpt_field_autocomplete', [ $this, 'ajax_no_access' ] );

		return true;
	}

	/**
	 * Add a checkbox fields in 'Settings' > 'Writing' for enabling CPT metafields functionality.
	 *
	 * @since 1.12.2
	 *
	 * @param string             $cpt_name
	 * @param PixelgradeCare_CPT $cpt
	 *
	 * @return void
	 */
	public function settings_api_init( $cpt_name, $cpt ) {
		// Check if CPT is enabled first.
		if ( get_option( $cpt::OPTION_NAME, '0' ) || current_theme_supports( $cpt::THEME_SUPPORTS ) ) {
			// Register the settings.
			register_setting(
				'writing',
				self::OPTION_NAME_ENABLE,
				[
					'type'    => 'array',
					'default' => [],
				]
			);
			register_setting(
				'writing',
				self::OPTION_NAME_MANAGEMENT,
				[
					'type'    => 'array',
					'default' => [],
				]
			);

			// Hook to output settings HTML after each CPT settings HTML.
			add_action( 'pixelgrade_care/cpt_setting_html/' . $cpt_name, [ $this, 'setting_html' ], 10, 1 );
		}
	}

	/**
	 * HTML code to display a checkbox true/false option for a certain CPT setting.
	 *
	 * @since 1.12.2
	 *
	 * @param PixelgradeCare_CPT $cpt
	 *
	 * @return void
	 */
	public function setting_html( $cpt ) {
		$enabled_value            = get_option( static::OPTION_NAME_ENABLE, [ $cpt::CUSTOM_POST_TYPE => '0' ] );
		$enabled_management_value = get_option( static::OPTION_NAME_MANAGEMENT, [ $cpt::CUSTOM_POST_TYPE => '0' ] );
		?>
		<p><label for="<?php echo esc_attr( static::OPTION_NAME_ENABLE . '[' . $cpt::CUSTOM_POST_TYPE . ']' ); ?>">
				<input name="<?php echo esc_attr( static::OPTION_NAME_ENABLE . '[' . $cpt::CUSTOM_POST_TYPE . ']' ); ?>"
				       id="<?php echo esc_attr( static::OPTION_NAME_ENABLE . '[' . $cpt::CUSTOM_POST_TYPE . ']' ); ?>"
					<?php echo checked( ! empty( $enabled_value[ $cpt::CUSTOM_POST_TYPE ] ), true, false ); ?>
					   type="checkbox" value="1"/>
				<?php esc_html_e( 'Enable metafields for this content type.', 'pixelgrade_care' ); ?>
			</label></p>
		<?php

		if ( current_user_can( 'manage_options' ) ) { ?>
			<p><label
					for="<?php echo esc_attr( static::OPTION_NAME_MANAGEMENT . '[' . $cpt::CUSTOM_POST_TYPE . ']' ); ?>"
					style="padding-left: 1.8em"
				>
					<input
						name="<?php echo esc_attr( static::OPTION_NAME_MANAGEMENT . '[' . $cpt::CUSTOM_POST_TYPE . ']' ); ?>"
						id="<?php echo esc_attr( static::OPTION_NAME_MANAGEMENT . '[' . $cpt::CUSTOM_POST_TYPE . ']' ); ?>"
						<?php echo checked( ! empty( $enabled_management_value[ $cpt::CUSTOM_POST_TYPE ] ), true, false ); ?>
						type="checkbox" value="1"/>
					<?php esc_html_e( 'Enable metafields management for this content type, via post edit pages.', 'pixelgrade_care' ); ?>
				</label></p>
		<?php } ?>
		<script>
			(function ( $ ) {
				$(document).ready( function () {
					// Initialize.
					let $parent = $('#<?php echo esc_attr( static::OPTION_NAME_MANAGEMENT . '\\\\[' . $cpt::CUSTOM_POST_TYPE . '\\\\]' ); ?>').closest('p');
					if ( $parent.length ) {
						if ( $('#<?php echo esc_attr( static::OPTION_NAME_ENABLE . '\\\\[' . $cpt::CUSTOM_POST_TYPE . '\\\\]' ); ?>').is( ':checked' ) ) {
							$parent.show();
						} else {
							$parent.hide();
						}
					}

					// Show or hide on change.
					$('#<?php echo esc_attr( static::OPTION_NAME_ENABLE . '\\\\[' . $cpt::CUSTOM_POST_TYPE . '\\\\]' ); ?>').on('change', function() {
						let $parent = $('#<?php echo esc_attr( static::OPTION_NAME_MANAGEMENT . '\\\\[' . $cpt::CUSTOM_POST_TYPE . '\\\\]' ); ?>').closest('p');
						if ( $parent.length ) {
							if ( $( this ).is( ':checked' ) ) {
								$parent.show();
							} else {
								$parent.hide();
							}
						}
					})
				})
			}( jQuery ));
		</script>
		<?php
	}

	/**
	 * On plugin/theme activation, check if CPTs support metafields and update the setting accordingly.
	 *
	 * @since 1.12.2
	 */
	public static function activation_cpt_metafields_support() {
		$new_value = [];

		if ( empty( self::$fields_list ) ) {
			update_option( static::OPTION_NAME_ENABLE, $new_value, true );

			return;
		}

		foreach ( self::$fields_list as $cpt => $fields_config ) {
			if ( ! empty( $fields_config ) ) {
				$new_value[ $cpt ] = '1';
			} else {
				$new_value[ $cpt ] = '0';
			}
		}

		update_option( static::OPTION_NAME_ENABLE, $new_value, true );
	}

	/**
	 * On plugin/theme activation, check if CPTs support metafields management and update the setting accordingly.
	 *
	 * @since 1.12.2
	 */
	public static function activation_cpt_metafields_management_support() {
		$new_value = [];

		if ( empty( self::$general_config ) ) {
			update_option( static::OPTION_NAME_MANAGEMENT, $new_value, true );

			return;
		}

		foreach ( self::$general_config as $cpt => $config ) {
			if ( ! empty( $config['allow_fields_management'] ) ) {
				$new_value[ $cpt ] = '1';
			} else {
				$new_value[ $cpt ] = '0';
			}
		}

		update_option( static::OPTION_NAME_MANAGEMENT, $new_value, true );
	}

	/**
	 * On plugin/theme activation, check if CPTs support metafields and initialize the fields list setting.
	 *
	 * We will avoid overwriting existing (non-empty) settings (per CPT)
	 * since the user might want to keep his previously edited fields list.
	 *
	 * @since 1.12.2
	 */
	public static function activation_cpt_metafields_list() {
		$current_value = get_option( self::OPTION_NAME_FIELDS_LIST, [] );

		$fields_list = self::extract_metafields_list_from_pixcare_config();

		$new_value = $current_value;
		// Transfer any CPT fields list from the Pixelgrade Care config for CPTs that don't have any config.
		foreach ( $fields_list as $cpt => $fields_config ) {
			if ( ! isset( $new_value[ $cpt ] ) ) {
				$new_value[ $cpt ] = $fields_config;
			}
		}

		update_option( static::OPTION_NAME_FIELDS_LIST, $new_value, true );
	}

	protected static function extract_metafields_list_from_pixcare_config() {
		$fields_list = [];
		// Check if the Pixelgrade Care theme config instructs us to activate any CPT with metafields.
		$pixcare_config = PixelgradeCare_Admin::get_theme_config();
		if ( ! empty( $pixcare_config['customPostTypes'] ) && is_array( $pixcare_config['customPostTypes'] ) ) {
			foreach ( $pixcare_config['customPostTypes'] as $cpt_name => $cpt_details ) {
				if ( ! is_array( $cpt_details )
				     || empty( $cpt_details['enable'] )
				     || empty( $cpt_details['metafields'] )
				     || ! is_array( $cpt_details['metafields'] )
				) {
					continue;
				}

				// Determine the fields list for this CPT.
				if ( ! empty( $cpt_details['metafields']['fields'] ) && is_array( $cpt_details['metafields']['fields'] ) ) {
					$fields_list[ $cpt_name ] = $cpt_details['metafields']['fields'];
				}
			}
		}

		return apply_filters( 'pixelgrade_care/extract_metafields_list_from_pixcare_config', $fields_list, $pixcare_config );
	}

	/**
	 * Does the site support metafields?
	 *
	 * @since 1.12.2
	 *
	 * @param string $post_type Leave empty to check if any CPT supports metafields,
	 *                          or provide CPT name to check for a certain CPT.
	 *
	 * @return bool
	 */
	public static function site_supports_metafields( $post_type = '' ) {
		$enable_cpt_metafields = get_option( static::OPTION_NAME_ENABLE, [] );
		if ( empty( $enable_cpt_metafields ) || ! is_array( $enable_cpt_metafields ) ) {
			return false;
		}

		if ( ! empty( $post_type ) ) {
			return ! empty( $enable_cpt_metafields[ $post_type ] );
		}

		foreach ( $enable_cpt_metafields as $cpt => $enable_for_cpt ) {
			if ( ! empty( $enable_for_cpt ) ) {
				return true;
			}
		}

		// Otherwise, say no unless something wants to filter us to say yes.
		return (bool) apply_filters( 'pixelgrade_care/site_supports_metafields', false, $post_type );
	}

	/**
	 * Does the site support metafields management?
	 *
	 * @since 1.12.2
	 *
	 * @param string $post_type Leave empty to check if any CPT supports metafields management,
	 *                          or provide CPT name to check for a certain CPT.
	 *
	 * @return bool
	 */
	public static function site_supports_metafields_management( $post_type = '' ) {
		$enable_cpt_metafields_management = get_option( static::OPTION_NAME_MANAGEMENT, [] );
		if ( empty( $enable_cpt_metafields_management ) || ! is_array( $enable_cpt_metafields_management ) ) {
			return false;
		}

		if ( ! empty( $post_type ) ) {
			return ! empty( $enable_cpt_metafields_management[ $post_type ] );
		}

		foreach ( $enable_cpt_metafields_management as $cpt => $enable_for_cpt ) {
			if ( ! empty( $enable_for_cpt ) ) {
				return true;
			}
		}

		// Otherwise, say no unless something wants to filter us to say yes.
		return (bool) apply_filters( 'pixelgrade_care/site_supports_metafields_management', false, $post_type );
	}

	/**
	 * Determine if the current user in the current context can manage fields.
	 *
	 * @param string           $post_type
	 * @param int|WP_Post|null $post   Optional. Post ID or post object. `null`, `false`, `0` and other PHP falsey values
	 *                                 return the current global post inside the loop. Defaults to global $post.
	 *
	 * @return bool
	 */
	protected function can_manage_post_fields( $post_type, $post = null ) {
		$allow_fields_management = self::$general_config[ $post_type ]['allow_fields_management'];
		// We accept a boolean value.
		if ( empty( $allow_fields_management ) ) {
			return false;
		}

		if ( is_bool( $allow_fields_management )
		     || ( is_string( $allow_fields_management ) && false === strpos( $allow_fields_management, ',' ) ) ) {
			$allow_fields_management = (bool) $this->string_to_bool( $allow_fields_management );
			if ( empty( $allow_fields_management ) ) {
				return false;
			}

			// If we have been given a boolean truthy value, we will at least restrict to the `manage_options` capability.
			$allow_fields_management = 'manage_options';
		}

		$post = get_post( $post );

		// We also accept a list or a comma separated list of user capabilities to check.
		// By default, we will use AND between these capabilities.
		$caps = wp_parse_list( $allow_fields_management );
		foreach ( $caps as $cap ) {
			// Stop at the first one not satisfied since we are using the AND relation.
			// If the capability ends in 's', this is a capability not specific to a certain post.
			if ( strlen( $cap ) - 1 === strrpos( $cap, 's' )
			     && ! current_user_can( $cap ) ) {

				return false;
			} else if ( ! empty( $post->ID )
			            && ! current_user_can( $cap, $post->ID ) ) {

				return false;
			}
		}

		return true;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 * @since     1.12.2
	 * @return    void
	 */
	function enqueue_admin_styles() {
		$current_post_type = get_post_type();
		// Only enqueue the admin styles
		// if we are on a WP admin page related to a CPT
		// or if the current CPT does support metafields.
		if ( empty( $current_post_type ) || ! self::site_supports_metafields( $current_post_type ) ) {
			return;
		}

		wp_enqueue_style(
			$this->parent->get_plugin_name() . '-metafields-admin-styles',
			plugins_url( 'css/admin.css', __FILE__ ),
			[ 'dashicons' ],
			$this->parent->get_version()
		);
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 * @since     1.12.2
	 * @return    void
	 */
	function enqueue_admin_scripts() {
		$current_post_type = get_post_type();
		// Only enqueue the admin styles
		// if we are on a WP admin page related to a CPT
		// if the current CPT does support metafields.
		// or we are on an edit page.
		if ( empty( $current_post_type )
		     || ! self::site_supports_metafields( $current_post_type )
		     || ! $this->is_edit_page() ) {

			return;
		}

		wp_enqueue_script( $this->parent->get_plugin_name() . '-metafields-admin-script',
			plugins_url( 'js/admin.js', __FILE__ ),
			[
				'jquery',
				'jquery-ui-autocomplete',
				'jquery-ui-sortable',
			],
			$this->parent->get_version()
		);

		$localized_array = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'pixcare_cpt_fields_ajax_nonce' ),
		];

		wp_localize_script(
			$this->parent->get_plugin_name() . '-metafields-admin-script',
			'pixcare_cpt_fields_l10n',
			$localized_array
		);
	}

	/**
	 * Determine if we are on a WP admin post edit page.
	 *
	 * @since 1.12.2
	 *
	 * @param $new_edit
	 *
	 * @return bool
	 */
	protected function is_edit_page( $new_edit = null ) {
		global $pagenow;

		// Make sure we are in the WP admin.
		if ( ! is_admin() ) {
			return false;
		}


		if ( $new_edit === 'edit' ) {
			return in_array( $pagenow, [ 'post.php', ] );
		} elseif ( $new_edit === 'new' ) {
			// Check for new post page.
			return in_array( $pagenow, [ 'post-new.php' ] );
		} else {
			// Check for either new or edit.
			return in_array( $pagenow, [ 'post.php', 'post-new.php' ] );
		}
	}

	/**
	 * Adds a meta box to the main column on any post type supported.
	 *
	 * @since 1.12.2
	 *
	 * @param string  $post_type Post type.
	 * @param WP_Post $post      Post object.
	 *
	 * @return void
	 */
	public function add_meta_box( $post_type, $post ) {
		// Only add the fields meta box for CPTs that support it.
		if ( ! self::site_supports_metafields( $post_type ) ) {
			return;
		}

		// Make a nice metabox title
		$post_type_obj  = get_post_type_object( $post_type );
		$post_type_name = $post_type;
		if ( $post_type_obj !== null ) {
			$post_type_name = $post_type_obj->labels->singular_name;
		}

		add_meta_box(
			'pixcare_cpt_fields',
			/* translators: %s: The post type name. */
			sprintf( esc_html__( '%s fields', 'pixelgrade_care' ), $post_type_name ),
			[ $this, 'meta_box_callback' ],
			$post_type,
			'side'
		);
	}

	/**
	 * Output the fields meta box HTML.
	 *
	 * @since 1.12.2
	 *
	 * @param WP_Post $post The current post.
	 *
	 * @return void
	 */
	public function meta_box_callback( $post ) {
		// Add a nonce field so we can secure the form save.
		wp_nonce_field( 'pixcare_cpt_fields_meta_box', 'pixcare_cpt_fields_meta_box_nonce' );

		// These settings depend on post type.
		$post_type = $post->post_type;
		if ( empty( $post_type ) ) {
			return;
		} ?>
		<ul class="pixcare_cpt_fields" data-post_type="<?php echo esc_attr( $post_type ); ?>">
			<?php
			// Check if we have fields for this post type.
			$fields_list = self::get_cpt_fields_list();
			if ( ! empty( $fields_list[ $post->post_type ] ) ) {
				foreach ( $fields_list[ $post->post_type ] as $key => $field ) {
					$meta_key = self::maybe_prefix_metakey( $field['meta_key'] );
					$value    = self::get_post_metafield_value( $meta_key, $post ); ?>
					<li class="pixcare_cpt_field ui-front" data-field_key="<?php echo esc_attr( $meta_key ); ?>">
						<label for="<?php echo esc_attr( $meta_key ); ?>"><?php echo $field['label']; ?></label>
						<br/>
						<input type="text" class="pixcare_cpt_field_value"
						       name="<?php echo esc_attr( $meta_key ); ?>" <?php echo( ! empty( $value ) ? 'value="' . $value . '"' : '' ); ?>/>
					</li>
				<?php }
			} ?>
		</ul>

		<?php
		// Only add the fields management modal meta box for CPTs that allow that
		// and only add the fields management modal meta box for users that have access to it.
		if ( self::site_supports_metafields_management( $post_type )
		     && $this->can_manage_post_fields( $post_type, $post ) ) { ?>
			<span class="manage_button_wrapper">
				<a href="#" class="open_pixcare_cpt_fields_modal"><?php _e( 'Manage fields', 'pixelgrade_care' ); ?></a>
			</span>
		<?php }
	}

	/**
	 * Converts a string (e.g. 'yes' or 'no') to a bool.
	 *
	 * @since 1.12.2
	 *
	 * @param string|bool $string String to convert. If a bool is passed it will be returned as-is.
	 *
	 * @return bool
	 */
	protected function string_to_bool( $string ) {
		return is_bool( $string ) ? $string : ( 'yes' === strtolower( $string ) || 1 === $string || 'true' === strtolower( $string ) || '1' === $string );
	}

	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @since 1.12.2
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save_post_meta_data( $post_id ) {
		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */
		// Check if our nonce is set and if it's valid.
		if ( ! isset( $_POST['pixcare_cpt_fields_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['pixcare_cpt_fields_meta_box_nonce'], 'pixcare_cpt_fields_meta_box' ) ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'page' === $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$post = get_post( $post_id );
		if ( empty( $post ) ) {
			return;
		}

		/* OK, it's safe for us to save the data now. */

		// Get only our fields keys.
		$meta_keys = self::get_metafields_keys( $post->post_type );
		if ( empty( $meta_keys ) ) {
			return;
		}

		$our_fields = array_intersect_key( $_POST, array_flip( $meta_keys ) );
		foreach ( $our_fields as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Retrieve the metafields keys for a certain post type.
	 *
	 * @since 1.12.2
	 *
	 * @param string $post_type
	 *
	 * @return array
	 */
	public static function get_metafields_keys( $post_type ) {
		$keys = [];
		if ( ! empty( self::$fields_list[ $post_type ] ) && is_array( self::$fields_list[ $post_type ] ) ) {
			foreach ( self::$fields_list[ $post_type ] as $field ) {
				$keys[] = self::maybe_prefix_metakey( $field['meta_key'] );
			}
		}

		return $keys;
	}

	/**
	 * Register the fields management meta box for a certain post, if we are allowed to.
	 *
	 * @since 1.12.2
	 *
	 * @param string  $post_type Post type.
	 * @param WP_Post $post      Post object.
	 *
	 * @return void
	 */
	public function add_modal_meta_box( $post_type, $post ) {

		// Only add the fields management modal meta box for CPTs that allow that.
		if ( ! self::site_supports_metafields_management( $post_type ) ) {
			return;
		}

		// Only add the fields management modal meta box for users that have access to it.
		if ( ! $this->can_manage_post_fields( $post_type, $post ) ) {
			return;
		}

		add_meta_box(
			'pixcare_cpt_fields_manager',
			esc_html__( 'Manage fields', 'pixelgrade_care' ),
			[ $this, 'modal_meta_box_callback' ],
			$post_type
		);
	}

	/**
	 * Output the fields management modal meta box HTML.
	 *
	 * @since 1.12.2
	 *
	 * @param WP_Post $post The current post.
	 *
	 * @return void
	 */
	function modal_meta_box_callback( $post ) {
		$config    = [
			'settings-key'   => self::OPTION_NAME_FIELDS_LIST,
			'template-paths' =>
				[
					trailingslashit( dirname( __FILE__ ) ) . 'core/views/form-partials/',
					trailingslashit( dirname( __FILE__ ) ) . 'views/form-partials/',
				],
			'fields'         => [
				'fields_manager' => [
					'type'    => 'postbox',
					'label'   => __( 'Fields', 'pixelgrade_care' ),
					'options' => [
						'pixcare_cpt_fields_list' => [
							'label' => __( 'Manage Fields', 'pixelgrade_care' ),
							'type'  => 'pixcare_cpt_metafields_manager',
						],
					],
				],
			],
			'processor'      => [
				'preupdate'  => [],
				'postupdate' => [],
			],
		];
		$processor = PixelgradeCare_MetafieldsCore::processor( $config );

		$f = PixelgradeCare_MetafieldsCore::form( $config, $processor );
		?>

		<div class="pixcare_cpt_fields_manager_modal">
			<div class="pixcare_cpt_fields_manager_form">
				<?php echo $f->field( 'fields_manager' )->render(); ?>
			</div>
		</div>

	<?php }

	/**
	 * @since 1.12.2
	 * @return void
	 */
	public function ajax_no_access() {
		echo 'You have no access here!';
		die();
	}

	/**
	 * Handle the AJAX request to update the metafields list for a certain post type.
	 *
	 * @since 1.12.2
	 * @return void
	 */
	public function ajax_update_metafields_list() {
		// Check if our nonce is set and if it's valid.
		check_ajax_referer( 'pixcare_cpt_fields_ajax_nonce', 'nonce' );


		if ( empty( $_REQUEST['post_id'] ) ) {
			wp_send_json_error( 'No post ID specified.' );
		}

		$post = get_post( $_REQUEST['post_id'] );
		if ( empty( $post ) ) {
			wp_send_json_error( 'Post not found.' );
		}

		if ( ! $this->can_manage_post_fields( $post->post_type, $post ) ) {
			wp_send_json_error( 'You are not allowed to manage the fields list.' );
		}

		if ( ! isset( $_REQUEST['fields'] ) ) {
			wp_send_json_error( 'No fields sent.' );
		}
		$fields_string = $_REQUEST['fields'];

		ob_start();
		parse_str( $fields_string, $fields );

		if ( ! isset ( $fields['pixcare_cpt_fields_list'] ) ) {
			$fields['pixcare_cpt_fields_list'] = [ $post->post_type => [] ];
		}

		// Process the fields list and update the DB.
		$this->make_fields( $post->post_type, $fields['pixcare_cpt_fields_list'] );

		// Send back the HTML of the main metabox so we can update the available metafields.
		$this->meta_box_callback( $post );

		$out = ob_get_clean();

		wp_send_json_success( $out );
	}

	/**
	 * @since 1.12.2
	 *
	 * @param $post_type
	 * @param $metafields_list
	 *
	 * @return void
	 */
	protected function make_fields( $post_type, $metafields_list ) {
		if ( ! empty ( $metafields_list ) ) {
			foreach ( $metafields_list as $post_type => $fields ) {

				// check if this post type has fields
				if ( empty( $fields ) ) {
					self::$fields_list[ $post_type ] = [];
					continue;
				}

				// Make a list with all meta_keys already existent ( they should already be unique )
				$unique_meta_keys = [];
				foreach ( $fields as $key => $field ) {
					if ( isset( $field['meta_key'] ) ) {
						$unique_meta_keys[ $field['meta_key'] ] = $field['meta_key'];
					}
				}

				$fields = array_values( $fields );

				foreach ( $fields as $key => $field ) {

					$fields[ $key ] = array_map( 'sanitize_text_field', $field );

					// If we don't have a meta key this means this is a new field.
					// We ensure there isn't already a meta_key with the same value.
					if ( ! isset( $field['meta_key'] ) ) {
						$meta_key = $field['label']; // old way sanitize_title_with_dashes( $field['label'] );

						// Try to sanitize a little.
						$meta_key = strip_tags( $meta_key );
						$meta_key = strtolower( $meta_key );
						$meta_key = preg_replace( '/&.+?;/', '', $meta_key ); // kill entities
						$meta_key = str_replace( [ ' ', '.', '!', '?', ',', ':' ], '-', $meta_key );

						// But if it is we make it unique with a unique id.
						if ( in_array( $meta_key, $unique_meta_keys ) ) {
							$meta_key = $meta_key . '-' . wp_unique_id();
						}

						array_push( $unique_meta_keys, $meta_key );

						$fields[ $key ]['meta_key'] = $meta_key;
					}
				}

				self::$fields_list[ $post_type ] = $fields;
			}

			// Update the meta field in the database.
			update_option( self::OPTION_NAME_FIELDS_LIST, self::$fields_list );
		} else {
			self::$fields_list[ $post_type ] = [];
			update_option( self::OPTION_NAME_FIELDS_LIST, self::$fields_list[ $post_type ] );
		}
	}

	/**
	 * Handle the AJAX fields autocomplete values request.
	 *
	 * @since 1.12.2
	 * @return void
	 */
	public function ajax_field_get_autocomplete() {
		// Check if our nonce is set and if it's valid.
		check_ajax_referer( 'pixcare_cpt_fields_ajax_nonce', 'nonce' );

		ob_start();
		if ( empty( $_REQUEST['post_type'] ) && empty( $_REQUEST['field_key'] ) && ! isset( $_REQUEST['term'] ) ) {
			wp_send_json_error( 'No data received' );
		}

		$meta_key  = sanitize_text_field( $_REQUEST['field_key'] );
		$post_type = sanitize_text_field( $_REQUEST['post_type'] );
		$values    = self::get_meta_values( $meta_key, $post_type );

		// Filter only the values that contain the sent (partial) term.
		$term = trim( strtolower( sanitize_text_field( $_REQUEST['term'] ) ) );
		if ( ! empty( $term ) ) {
			$filtered_values = array_filter( $values, function ( $value ) use ( $term ) {
				if ( false !== strpos( strtolower( $value ), $term ) ) {
					return true;
				}

				return false;
			} );

			// Only return the filtered values if we haven't removed all values.
			// Otherwise, return the entire list.
			if ( ! empty( $filtered_values ) ) {
				$values = $filtered_values;
			}
		}

		wp_send_json_success( $values );
	}

	/**
	 * Get all the values for a certain meta key and certain post type.
	 *
	 * @since 1.12.2
	 *
	 * @param string $meta_key The meta key name to retrieve values of.
	 * @param string $post_type Optional. The custom post type of posts to retrieve value of.
	 *                          Defaults to the `post` post type.
	 * @param string $post_status Optional. Restrict target posts by their status.
	 *                            Defaults to published posts.
	 *
	 * @return array List of unique metafields values. Empty array when none were found.
	 */
	public static function get_meta_values( $meta_key = '', $post_type = 'post', $post_status = 'publish' ) {
		$values = [];
		if ( empty( $meta_key ) ) {
			return $values;
		}

		// First get all posts of a certain type.
		$args  = [
			'numberposts'      => 200, // A more than decent limit.
			'post_type'        => $post_type,
			'post_status'      => $post_status,
			'suppress_filters' => false, // Allow filters - like WPML
		];
		$posts = get_posts( $args );

		// Now go through each and get the meta value for the given meta key.
		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				$meta_value = get_post_meta( $post->ID, self::maybe_prefix_metakey( $meta_key ), true );

				if ( ! empty( $meta_value ) ) {
					$values[] = $meta_value;
				}
			}
		}

		if ( ! empty( $values ) ) {
			return array_unique( $values );
		}

		return $values;
	}

	/**
	 * Retrieve all the metafields details for a certain post.
	 *
	 * @since 1.12.2
	 *
	 * @param int|WP_Post|null $post Optional. Post ID or post object. `null`, `false`, `0` and other PHP falsey values
	 *                               return the current global post inside the loop. Defaults to global $post.
	 *
	 * @return array List of post metafields keyed by the meta_key.
	 *               Each entry includes `label`, `value`, and optionally, `filter`.
	 */
	public static function get_post_metafields( $post = 0 ) {
		$metafields = [];

		$post = get_post( $post );
		if ( empty( $post ) ) {
			return $metafields;
		}
		$post_type = get_post_type( $post );

		$fields_list = self::get_cpt_fields_list();
		if ( ! empty( $fields_list[ $post_type ] ) ) {
			foreach ( $fields_list[ $post_type ] as $field ) {

				$metafields[ $field['meta_key'] ] = [
					'label' => $field['label'],
					'value' => get_post_meta( $post->ID, self::maybe_prefix_metakey( $field['meta_key'] ), true ),
				];

				if ( isset( $field['filter'] ) ) {
					$metafields[ $field['meta_key'] ]['filter'] = $field['filter'];
				}
			}
		}

		return apply_filters( 'pixelgrade_care/cpt_metafields/post_metafields', $metafields, $post );
	}

	/**
	 * Retrieve a metafield's value for a certain post.
	 *
	 * @since 1.12.2
	 *
	 * @param string           $meta_key The meta key name to retrieve the value of.
	 *                                   Do not include the prefix since we will automatically prefix it.
	 * @param int|WP_Post|null $post     Optional. Post ID or post object. `null`, `false`, `0` and other PHP falsey values
	 *                                   return the current global post inside the loop. Defaults to global $post.
	 *
	 * @return mixed|false The post's meta value. False on error.
	 */
	public static function get_post_metafield_value( $meta_key, $post = 0 ) {
		$metafield_value = false;

		$post = get_post( $post );
		if ( empty( $post ) || empty( $meta_key ) ) {
			return $metafield_value;
		}

		$metafield_value = get_post_meta( $post->ID, self::maybe_prefix_metakey( $meta_key ), true );

		return apply_filters( 'pixelgrade_care/cpt_metafields/post_metafield_value', $metafield_value, $meta_key, $post );
	}

	/**
	 * Get all the filterable metafields.
	 *
	 * @since 1.12.2
	 *
	 * @param string $post_type Optional. Defaults to the post type of the current global post.
	 *
	 * @return array List of filterable metakeys as $key => $label. Empty list if none were found.
	 */
	public static function get_filterable_metafields( $post_type = '' ) {
		$filterable_metakeys = [];

		if ( empty( $post_type ) ) {
			$post_type = get_post_type();
		}
		if ( empty( $post_type ) ) {
			return $filterable_metakeys;
		}

		$fields_list = self::get_cpt_fields_list();
		if ( isset( $fields_list[ $post_type ] ) ) {
			foreach ( $fields_list[ $post_type ] as $key => $fields ) {
				if ( ! isset( $fields['filter'] ) || ! isset( $fields['meta_key'] ) ) {
					continue;
				}

				if ( empty( $fields['label'] ) ) {
					$fields['label'] = esc_html__( '(No label)', 'pixelgrade_care' );
				}

				$filterable_metakeys[ $fields['meta_key'] ] = $fields['label'];
			}
		}

		return apply_filters( 'pixelgrade_care/cpt_metafields/post_type_filterable_metakeys', $filterable_metakeys, $post_type );
	}

	/**
	 * Retrieve the fields list config.
	 *
	 * @since 1.12.2
	 *
	 * @param string $cpt Optional. The custom post type name to restrict fields config to.
	 *
	 * @return array The fields list config. Empty array when no fields list config found.
	 */
	public static function get_cpt_fields_list( $cpt = '' ) {

		if ( self::$fields_list === null ) {
			self::$fields_list = get_option( self::OPTION_NAME_FIELDS_LIST, [] );
		}

		if ( empty( $cpt ) ) {
			return self::$fields_list;
		}

		if ( empty( self::$fields_list[ $cpt ] ) ) {
			return [];
		}

		return self::$fields_list[ $cpt ];
	}

	/**
	 * Add the prefix to a given meta key.
	 *
	 * @param string $meta_key The meta key to prefix.
	 *
	 * @return string
	 */
	public static function maybe_prefix_metakey( $meta_key ) {
		// If it is already prefixed, leave it unchanged.
		if ( 0 === strpos( $meta_key, self::META_KEY_PREFIX . '_' ) ) {
			return $meta_key;
		}

		return self::META_KEY_PREFIX . '_' . $meta_key;
	}

	/**
	 * Returns the requested option, and ensures it's autoloaded in the future.
	 *
	 * @since 1.12.2
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
	 * @since  1.12.2
	 * @static
	 *
	 * @param PixelgradeCare $parent The main plugin object (the parent).
	 *
	 * @return PixelgradeCare_CPT_Metafields Main instance
	 */
	public static function instance( $parent ) {

		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new static( $parent );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.12.2
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), null );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.12.2
	 */
	public function __wakeup() {

		_doing_it_wrong( __FUNCTION__, esc_html__( 'You should not do that!', 'pixelgrade_care' ), null );
	}
}
