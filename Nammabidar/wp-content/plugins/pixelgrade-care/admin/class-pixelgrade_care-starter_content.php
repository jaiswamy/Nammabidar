<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 * Class responsible for the Starter Content Component.
 *
 * Basically this is an Import Demo Data system.
 *
 * @since      1.1.6
 * @package    PixelgradeCare
 * @subpackage PixelgradeCare/admin
 * @author     Pixelgrade <help@pixelgrade.com>
 */
class PixelgradeCare_StarterContent {

	/**
	 * The main plugin object (the parent).
	 * @since     1.3.0
	 * @var     PixelgradeCare
	 * @access    public
	 */
	public $parent = null;

	/**
	 * The only instance.
	 * @since   1.3.0
	 * @var     PixelgradeCare_StarterContent
	 * @access  protected
	 */
	protected static $_instance = null;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.1.6
	 *
	 * @param PixelgradeCare $parent The parent instance.
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		add_action( 'rest_api_init', [ $this, 'add_rest_routes_api' ] );

		if ( apply_filters( 'pixcare_sce_allow_options_filtering', true ) ) {
			add_filter( 'pixcare_sce_import_post_option_page_on_front', [
				$this,
				'filter_post_option_page_on_front',
			], 10, 2 );
			add_filter( 'pixcare_sce_import_post_option_page_for_posts', [
				$this,
				'filter_post_option_page_for_posts',
			], 10, 2 );
			add_filter( 'pixcare_sce_import_post_theme_mod_nav_menu_locations', [
				$this,
				'filter_post_theme_mod_nav_menu_locations',
			], 10, 2 );

			/*
			 * Replace the custom logo attachment ID with the new one.
			 */
			add_filter( 'pixcare_sce_import_post_theme_mod_custom_logo', [
				$this,
				'filter_post_theme_mod_custom_logo',
			], 10, 2 );
			/**
			 * Some themes use custom keys for various attachment ID controls, so we need to treat them separately.
			 */
			add_filter( 'pixcare_sce_import_post_theme_mod_rosa_transparent_logo', [
				$this,
				'filter_post_theme_mod_custom_logo',
			], 10, 2 );
			add_filter( 'pixcare_sce_import_post_theme_mod_osteria_transparent_logo', [
				$this,
				'filter_post_theme_mod_custom_logo',
			], 10, 2 );
			add_filter( 'pixcare_sce_import_post_theme_mod_pixelgrade_transparent_logo', [
				$this,
				'filter_post_theme_mod_custom_logo',
			], 10, 2 );
			add_filter( 'pixcare_sce_import_post_theme_mod_anima_transparent_logo', [
				$this,
				'filter_post_theme_mod_custom_logo',
			], 10, 2 );

			// prevent Jetpack from disabling the theme's style on import
			add_filter( 'pixcare_sce_import_post_theme_mod_jetpack_custom_css', [
				$this,
				'uncheck_jetpack_custom_css_style_replacement',
			] );

			// Widgets

			// Content links
			add_action( 'pixcare_sce_after_insert_post', [ $this, 'prepare_menus_links' ], 10, 3 );
			add_action( 'pixcare_sce_import_end', [ $this, 'end_import' ] );
		}

		// Add the already imported content to the Pixelgrade Care theme config.
		add_filter( 'pixcare_config', [ $this, 'add_imported_content_to_theme_config' ] );

		// Modify the default Hello World post after we have imported the `post` post type.
		add_action( 'pixcare_sce_imported_post_type', [ $this, 'modify_hello_world_post' ], 10, 1 );
	}

	/** REST-ful methods */
	public function add_rest_routes_api() {
		register_rest_route( 'pixcare/v1', '/import', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'rest_import_step' ],
			'permission_callback' => [ $this, 'permission_nonce_callback' ],
			'args'                => [
				'demo_key' => [ 'required' => true ],
				'demo_url'      => [ 'required' => true ],
				'type'     => [ 'required' => true ],
				'base_rest_url'      => [ 'required' => true ],
				'args'     => [ 'required' => true ],
			],
		] );

		register_rest_route( 'pixcare/v1', '/upload_media', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'rest_upload_media' ],
			'permission_callback' => [ $this, 'permission_nonce_callback' ],
			'args'                => [
				'demo_key'      => [ 'required' => true ],
				'file_data'     => [ 'required' => true ],
				'title'         => [ 'required' => true ],
				'group'         => [ 'required' => true ],
				'ext'           => [ 'required' => true ],
				'remote_id'     => [ 'required' => true ],
				'pixcare_nonce' => [ 'required' => true ],
			],
		] );
	}

	/**
	 * Handle the request to upload a media file.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function rest_upload_media( $request ) {
		$params = $request->get_params();

		$display_errors = @ini_set( 'display_errors', 0 );

		add_filter( 'upload_mimes', [ $this, 'allow_svg_upload' ] );

		$demo_key  = sanitize_text_field( $params['demo_key'] );
		$post_type = $params['post_type'];
		if ( ! is_string( $post_type ) || empty( $post_type ) || 'false' === $post_type ) {
			$post_type = false;
		}

		// If we've been instructed to upload media for a specific post type,
		// but it doesn't exist or the user doesn't have access to it, do not upload since it will not be used.
		if ( false !== $post_type && (
			! post_type_exists( $post_type )
		     || ( in_array( $post_type, ['product','product_variation' ] ) && ! pixelgrade_user_has_access( 'woocommerce' ) )
			)
		) {
			return rest_ensure_response( [
				'code'    => 'success',
				'message' => sprintf( esc_html__( 'Skipped this media since it is specific to the "%s" post type that will not be imported.', 'pixelgrade_care' ), $post_type ),
				'data'    => [],
			] );
		}

		$group     = sanitize_text_field( $params['group'] );
		$title     = sanitize_text_field( $params['title'] );
		$remote_id = absint( $params['remote_id'] );
		$remote_urls = ! empty( $params['remote_urls'] ) ? $params['remote_urls'] : [];

		// Get already imported media details to determine if we should update an existing attachment.
		$imported_starter_content = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );

		// Make sure that we have the necessary entries
		if ( empty( $imported_starter_content ) ) {
			$imported_starter_content = [];
		}
		if ( empty( $imported_starter_content[ $demo_key ] ) ) {
			$imported_starter_content[ $demo_key ] = [];
		}
		$imported_sc_entry = &$imported_starter_content[ $demo_key ];
		if ( ! empty( $post_type ) ) {
			if ( ! isset( $imported_starter_content[ $demo_key ]['post_types'] ) ) {
				$imported_starter_content[ $demo_key ]['post_types'] = [];
			}
			if ( ! isset( $imported_starter_content[ $demo_key ]['post_types'][ $post_type ] ) ) {
				$imported_starter_content[ $demo_key ]['post_types'][ $post_type ] = [];
			}

			$imported_sc_entry = &$imported_starter_content[ $demo_key ]['post_types'][ $post_type ];
		}

		if ( ! isset( $imported_sc_entry['media'] ) ) {
			$imported_sc_entry['media'] = [];
		}
		if ( ! isset( $imported_sc_entry['media'][ $group ] ) ) {
			$imported_sc_entry['media'][ $group ] = [];
		}

		$attachment = [
			'post_parent'  => 0,
			'post_title'   => $title,
			'post_content' => '',
			'post_status'  => 'inherit',
		];

		// If we have previously imported this attachment, delete the previous attachment (by specifying the same ID).
		if ( ! empty( $imported_sc_entry['media'][ $group ][ $remote_id ] ) ) {
			$previously_imported_attachment_id = false;
			// First, try the new format (with extra details).
			if ( is_array( $imported_sc_entry['media'][ $group ][ $remote_id ] ) ) {
				if ( ! empty( $imported_sc_entry['media'][ $group ][ $remote_id ]['imported_id'] ) ) {
					$previously_imported_attachment_id = absint( $imported_sc_entry['media'][ $group ][ $remote_id ]['imported_id'] );
				}
			} else {
				// We simply have the imported attachment id.
				$previously_imported_attachment_id = absint( $imported_sc_entry['media'][ $group ][ $remote_id ] );
			}

			if ( ! empty( $previously_imported_attachment_id ) ) {
				$previous_attachment_metadata = wp_get_attachment_metadata( $previously_imported_attachment_id );
				if ( ! empty( $previous_attachment_metadata['imported_with_pixcare_at'] ) ) {
					// Allow others to prevent us from overwriting/reimporting existing media.
					if ( apply_filters( 'pixcare_sce_should_overwrite_existing_media', true, $previously_imported_attachment_id, $attachment, $demo_key ) ) {
						$attachment['ID'] = $previously_imported_attachment_id;
					} else {
						@ini_set( 'display_errors', $display_errors );

						// We have been instructed to skip the media overwrite/reimport.
						return rest_ensure_response( [
							'code'    => 'success',
							'message' => 'Skipped the existing media overwrite, as instructed.',
							'data'    => [
								'attachmentID' => $previously_imported_attachment_id,
							],
						] );
					}
				}
			}
		}

		$filename  = $title . '.' . sanitize_text_field( $params['ext'] );
		$file_data = $this->decode_chunk( $params['file_data'] );
		if ( false === $file_data ) {
			@ini_set( 'display_errors', $display_errors );

			return rest_ensure_response( [
				'code'    => 'error',
				'message' => esc_html__( 'No file data.', 'pixelgrade_care' ),
				'data'    => [],
			] );
		}

		$upload_file = wp_upload_bits( $filename, null, $file_data );
		if ( $upload_file['error'] ) {
			@ini_set( 'display_errors', $display_errors );

			return rest_ensure_response( [
				'code'    => 'error',
				'message' => esc_html__( 'File permission error.', 'pixelgrade_care' ),
				'data'    => [],
			] );
		}
//		$attachment['guid'] = $upload_file['url'];

		$wp_filetype                  = wp_check_filetype( $filename, null );
		$attachment['post_mime_type'] = $wp_filetype['type'];

		$attachment_id = wp_insert_attachment( $attachment, $upload_file['file'] );

		if ( ! is_wp_error( $attachment_id ) ) {

			// Remember the imported attachment details.
			$imported_sc_entry['media'][ $group ][ $remote_id ] = [
				'remote_id' => $remote_id,
				'remote_urls' => $remote_urls,
				'imported_id' => $attachment_id,
			];

			// Save the data in the DB
			PixelgradeCare_Admin::set_option( 'imported_starter_content', $imported_starter_content );
			PixelgradeCare_Admin::save_options();

			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );

			$attachment_data['imported_with_pixcare_at'] = current_time( 'mysql', 1 );

			wp_update_attachment_metadata( $attachment_id, $attachment_data );

			@ini_set( 'display_errors', $display_errors );

			return rest_ensure_response( [
				'code'    => 'success',
				'message' => '',
				'data'    => [
					'attachmentID' => $attachment_id,
				],
			] );
		}

		@ini_set( 'display_errors', $display_errors );

		return rest_ensure_response( [
			'code'    => 'error',
			'message' => esc_html__( 'Something went wrong with uploading the media file.', 'pixelgrade_care' ),
			'data'    => [
				'error' => $attachment_id,
			],
		] );
	}

	/**
	 * Handle the request to import something.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function rest_import_step( $request ) {

		$params = $request->get_params();

		$display_errors = @ini_set( 'display_errors', 0 );

		// clear whatever was printed before, we only need a pure json
		if ( ob_get_length() ) {
			ob_get_clean();
		}

		// We need to import posts without the intervention of the cache system
		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );
		wp_suspend_cache_invalidation( true );

		if ( empty( $params['demo_key'] )
		     || empty( $params['demo_url'] )
		     || empty( $params['args'] )
		     || empty( $params['type'] )
		     || empty( $params['base_rest_url'] ) ) {

			@ini_set( 'display_errors', $display_errors );

			return rest_ensure_response( [
				'code'    => 'missing_params',
				'message' => esc_html__( 'You need to provide all the needed parameters.', 'pixelgrade_care' ),
				'data'    => [],
			] );
		}

		$demo_key = sanitize_text_field( $params['demo_key'] );
		$base_rest_url = sanitize_text_field( $params['base_rest_url'] );
		$type     = sanitize_text_field( $params['type'] );
		$args     = $params['args'];

		// The default response data
		$response = [];

		switch ( $type ) {
			case 'post_type':
			{
				$result = $this->import_post_type( $demo_key, $base_rest_url, $args );
				if ( ! is_wp_error( $result ) && ! $result instanceof WP_REST_Response ) {
					$response['importedPostIds'] = $result;
				} else {
					$response = $result;
				}
				break;
			}

			case 'taxonomy':
			{
				$result = $this->import_taxonomy( $demo_key, $base_rest_url, $args );
				if ( ! is_wp_error( $result ) && ! $result instanceof WP_REST_Response ) {
					$response['importedTermIds'] = $result;
				} else {
					$response = $result;
				}
				break;
			}

			case 'widgets':
			{
				if ( empty( $args['data'] ) ) {
					break;
				}

				$args['data'] = $this->maybeCastNumbersDeep( $args['data'] );

				$result = $this->import_widgets( $demo_key, $args['data'] );
				if ( ! is_wp_error( $result ) && ! $result instanceof WP_REST_Response ) {
					$response['widgets'] = $result;
				} else {
					$response = $result;
				}
				break;
			}

			case 'parsed_widgets':
			{

				$result = $this->import_parsed_widgets( $demo_key, $base_rest_url );
				if ( ! is_wp_error( $result ) && ! $result instanceof WP_REST_Response ) {
					$response['widgets'] = $result;
				} else {
					$response = $result;
				}
				break;
			}

			case 'pre_settings':
			{
				if ( empty( $args['data'] ) ) {
					break;
				}

				if ( ! is_array( $args['data'] ) ) {
					$args['data'] = json_decode( $args['data'], true );
				}
				$args['data'] = $this->maybeCastNumbersDeep( $args['data'] );

				$result = $this->import_settings( $demo_key, 'pre', $args['data'] );
				if ( ! is_wp_error( $result ) && ! $result instanceof WP_REST_Response ) {
					$response['settings'] = $result;
				} else {
					$response = $result;
				}
				break;
			}

			case 'post_settings':
			{
				if ( empty( $args['data'] ) ) {
					break;
				}

				if ( ! is_array( $args['data'] ) ) {
					$args['data'] = json_decode( $args['data'], true );
				}
				$args['data'] = $this->maybeCastNumbersDeep( $args['data'] );

				$result = $this->import_settings( $demo_key, 'post', $args['data'] );
				if ( ! is_wp_error( $result ) && ! $result instanceof WP_REST_Response ) {
					$response['settings'] = $result;
				} else {
					$response = $result;
				}
				break;
			}

			default :
				break;
		}

		// add cache invalidation as before
		wp_suspend_cache_invalidation( false );
		wp_cache_flush();

		$taxonomies = get_taxonomies();
		foreach ( $taxonomies as $tax ) {
			delete_option( "{$tax}_children" );
			_get_term_hierarchy( $tax );
		}

		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );

		@ini_set( 'display_errors', $display_errors );

		// If we have received an error or a REST response, just pass it along
		if ( is_wp_error( $response ) || $response instanceof WP_REST_Response ) {
			return rest_ensure_response( $response );
		}

		return rest_ensure_response( [
			'code'    => 'success',
			'message' => '',
			'data'    => $response,
		] );
	}

	private function maybeCastNumbersDeep( $data ) {
		if ( ! is_array( $data ) ) {
			$data = json_decode( $data, true );
		}

		array_walk_recursive( $data, function ( &$item ) {
			if ( is_string( $item ) && is_numeric( $item ) ) {
				$item = $item + 0;
			}
		} );

		return $data;
	}

	private function array_map_recursive( callable $func, array $array ) {
		return filter_var( $array, \FILTER_CALLBACK, [ 'options' => $func ] );
	}

	private function castNumericValue( $val ) {
		if ( is_string( $val ) && is_numeric( $val ) ) {
			return $val + 0;
		}

		return $val;
	}

	/**
	 * Import posts of a certain post type.
	 *
	 * @param string $demo_key
	 * @param string $base_rest_url
	 * @param array  $args
	 *
	 * @return bool|array|WP_REST_Response False on failure, the imported post IDs otherwise.
	 */
	private function import_post_type( $demo_key, $base_rest_url, $args = [] ) {
		$imported_ids = [];

		if ( empty( $args['ids'] ) || empty( $args['post_type'] ) ) {
			return false;
		}

		$post_type = $args['post_type'];
		if ( ! post_type_exists( $post_type )
			|| ( in_array( $post_type, ['product','product_variation' ] ) && ! pixelgrade_user_has_access( 'woocommerce' ) )
		) {
			return rest_ensure_response( [
				'code'    => 'success',
				/* translators: %s: the taxonomy name */
				'message' => sprintf( esc_html__( 'Skipped "%s" posts due to missing post type or lack of access (maybe you need an add-on?)', 'pixelgrade_care' ), $post_type ),
				'data'    => [],
			] );
		}

		// Get the already imported starter content.
		$imported_starter_content = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );

		// Make sure that we have the necessary entries.
		if ( empty( $imported_starter_content ) ) {
			$imported_starter_content = [];
		}
		if ( empty( $imported_starter_content[ $demo_key ] ) ) {
			$imported_starter_content[ $demo_key ] = [];
		}
		if ( ! isset( $imported_starter_content[ $demo_key ]['post_types'] ) ) {
			$imported_starter_content[ $demo_key ]['post_types'] = [];
		}

		$request_url = trailingslashit( $base_rest_url ) . 'posts';

		$request_data = [
			'post_type'      => $post_type,
			'include'        => $args['ids'],
			'placeholders'   => $this->get_placeholders( $demo_key, $post_type ),
			'ignored_images' => $this->get_ignored_images( $demo_key, $post_type ),
		];

		$request_args = [
			'method'    => 'POST',
			'timeout'   => 5,
			'blocking'  => true,
			'body'      => $request_data,
			'sslverify' => false,
		];

		// Increase timeout if the target URL is a development one, so we can account for slow local (development) installations.
		if ( PixelgradeCare_Admin::is_development_url( $request_url ) ) {
			$request_args['timeout'] = 10;
		}

		// We will do a blocking request.
		$response = wp_remote_request( $request_url, $request_args );
		if ( is_wp_error( $response ) ) {
			return rest_ensure_response( $response );
		}
		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		// Bail in case of decode error or failure to retrieve data.
		if ( null === $response_data ) {
			return rest_ensure_response( [
				'code'    => 'json_error',
				'message' => esc_html__( 'Something went wrong with decoding the data received.', 'pixelgrade_care' ),
				'data'    => [
					'response' => wp_remote_retrieve_body( $response ),
				],
			] );
		}

		if ( empty( $response_data['code'] ) || 'success' !== $response_data['code'] ) {
			return rest_ensure_response( $response_data );
		}

		foreach ( $response_data['data']['posts'] as $received_post ) {
			$post_args = [
//				'import_id'             => $received_post['ID'],
				'post_title'            => wp_strip_all_tags( $received_post['post_title'] ),
				'post_content'          => $received_post['post_content'],
				'post_content_filtered' => $received_post['post_content_filtered'],
				'post_excerpt'          => $received_post['post_excerpt'],
				'post_status'           => $received_post['post_status'],
				'post_name'             => $received_post['post_name'],
				'post_type'             => $received_post['post_type'],
				'post_date'             => $received_post['post_date'],
				'post_date_gmt'         => $received_post['post_date_gmt'],
				'post_modified'         => $received_post['post_modified'],
				'post_modified_gmt'     => $received_post['post_modified_gmt'],
				'menu_order'            => $received_post['menu_order'],
				'meta_input'            => [
					'imported_with_pixcare_at' => current_time( 'mysql', 1 ),
				],
			];

			// Now decide what to do if the post slug already exists.
			$existing_post_id = $this->the_slug_exists( $received_post['post_name'], $received_post['post_type'] );
			if ( $existing_post_id ) {
				$overwrite = false;
				// Determine if it is safe to overwrite.
				// If the post was not modified in the meantime, we overwrite.
				$imported_with_pixcare_at = get_post_meta( $existing_post_id, 'imported_with_pixcare_at', true );
				if ( ! empty( $imported_with_pixcare_at ) && get_post_modified_time( 'U', true, $existing_post_id ) <= strtotime( $imported_with_pixcare_at ) ) {
					$overwrite = true;
				}

				if ( apply_filters( 'pixcare_sce_should_overwrite_existing_post', $overwrite, $existing_post_id, $received_post, $demo_key ) ) {
					$post_args['ID'] = $existing_post_id;
				} else {
					if ( isset( $imported_starter_content[ $demo_key ]['post_types'][ $post_type ][ $received_post['ID'] ] ) ) {
						// If we have already imported this post, keep the data.
						$imported_ids[ $received_post['ID'] ] = $imported_starter_content[ $demo_key ]['post_types'][ $post_type ][ $received_post['ID'] ];
					} else {
						// We can safely consider this post imported as the existing post.
						$imported_ids[ $received_post['ID'] ] = absint( $existing_post_id );
					}
					continue;
				}
			}

			if ( ! empty( $received_post['meta'] ) ) {
				$skip_post_import = false;

				// We need to handle special cases for nav_menu_items
				// (e.g. items relating to stuff that hasn't been imported or is not available).
				// We rely on the fact that nav_menu_items are imported last.
				if ( 'nav_menu_item' === $received_post['post_type']
				     && ! empty( $received_post['meta']['_menu_item_type'] )
					&& ! empty( $received_post['meta']['_menu_item_object'] ) ) {

					$menu_item_type      = maybe_unserialize( $received_post['meta']['_menu_item_type'][0] );
					$menu_item_object    = maybe_unserialize( $received_post['meta']['_menu_item_object'][0] );

					switch ( $menu_item_type ) {
						case 'post_type':
						case 'post_type_archive':
							// We want to make sure that the post type actually exists.
							if ( ! empty( $menu_item_object ) && ! post_type_exists( $menu_item_object ) ) {
								$skip_post_import = true;
							}
							break;
						case 'taxonomy':
							// We want to make sure that the taxonomy actually exists.
							if ( ! empty( $menu_item_object ) && ! taxonomy_exists( $menu_item_object ) ) {
								$skip_post_import = true;
							}
							break;
					}
				}
				if ( $skip_post_import ) {
					continue;
				}

				foreach ( $received_post['meta'] as $key => $meta ) {
					if ( $meta === null || $meta === [ null ] ) {
						continue;
					}

					if ( ! empty( $meta ) ) {
						// We only need  the first value.
						if ( isset( $meta[0] ) ) {
							$meta = $meta[0];
						}
						$meta = maybe_unserialize( $meta );
					}

					// Do not import menu items that are related to post formats.
					if ( $key === '_menu_item_object' && $meta === 'post_format' ) {
						$skip_post_import = true;
						break;
					}

					$post_args['meta_input'][ $key ] = apply_filters( 'pixcare_sce_pre_postmeta', $meta, $key, $demo_key );
				}

				if ( $skip_post_import ) {
					continue;
				}
			}

			if ( ! empty( $received_post['taxonomies'] ) && is_array( $received_post['taxonomies'] ) ) {
				// Just set this argument as empty,
				// as we will pass all taxonomies through the new, universal tax_input argument.
				$post_args['post_category'] = [];

				$post_args['tax_input'] = [];
				foreach ( $received_post['taxonomies'] as $taxonomy => $terms ) {

					if ( ! taxonomy_exists( $taxonomy ) ) {
						// @TODO inform the user that the taxonomy doesn't exist and maybe he should install a plugin
						continue;
					}

					$post_args['tax_input'][ $taxonomy ] = [];
					foreach ( $terms as $term ) {
						if ( is_numeric( $term ) && isset( $imported_starter_content[ $demo_key ]['taxonomies'][ $taxonomy ][ $term ] ) ) {
							$term = $imported_starter_content[ $demo_key ]['taxonomies'][ $taxonomy ][ $term ];
						}

						$post_args['tax_input'][ $taxonomy ][] = $term;
					}
				}
			}

			// Allow others to have a say in it.
			$post_args = apply_filters( 'pixcare_sce_insert_post_args', $post_args, $received_post, $demo_key );
			// Since wp_insert_post() at post.php@L3884 does a wp_unslash() on the whole post data, we need to do a wp_slash() to prevent things from breaking.
			$post_args = wp_slash( $post_args );

			$imported_post_id = wp_insert_post( $post_args );

			if ( is_wp_error( $imported_post_id ) || empty( $imported_post_id ) ) {
				// well ... error
				// Do nothing for now.
			} else {
				$imported_ids[ $received_post['ID'] ] = $imported_post_id;
			}
		}

		// Post-processing to handle parents, guid, content changes.
		foreach ( $response_data['data']['posts'] as $i => $received_post ) {
			$update_this = false;

			if ( ! isset( $imported_ids[ $received_post['ID'] ] ) ) {
				continue;
			}

			$update_args = [
				'ID' => $imported_ids[ $received_post['ID'] ],
			];

			// Bind parents after we have all the posts.
			if ( ! empty( $received_post['post_parent'] ) && isset( $imported_ids[ $received_post['post_parent'] ] ) ) {
				$update_args['post_parent'] = $imported_ids[ $received_post['post_parent'] ];
				$update_this                = true;
			}

			// Recheck the guid.
			$new_perm = get_permalink( $received_post['ID'] );

			// If the guid takes the place of the permalink, rebase it.
			if ( ! empty( $new_perm ) && ! is_numeric( $received_post['guid'] ) ) {
				$update_args['guid'] = $new_perm;
				$update_this         = true;
			}

			$new_post_content = $received_post['post_content'];
			// We need to handle various taxonomy IDs that might be present in blocks (attributes) throughout the content.
			if ( has_blocks( $new_post_content ) && ! empty( $imported_starter_content[ $demo_key ]['taxonomies'] ) ) {
				$new_post_content = $this->maybe_replace_tax_ids_in_blocks( $new_post_content, $imported_starter_content[ $demo_key ]['taxonomies'] );
			}
			// We need to handle various post IDs that might be present in blocks (attributes) throughout the content.
			if ( has_blocks( $new_post_content ) && ! empty( $imported_starter_content[ $demo_key ]['post_types'] ) ) {
				$new_post_content = $this->maybe_replace_post_ids_in_blocks( $new_post_content, $imported_starter_content[ $demo_key ]['post_types'] );
			}

			if ( $new_post_content !== $received_post['post_content'] ) {
				$update_args['post_content'] = $new_post_content;
				$update_this                 = true;
			}

			if ( $update_this ) {
				// Since wp_insert_post() at post.php@L3884 does a wp_unslash() on the whole post data, we need to do a wp_slash() to prevent things from breaking.
				$update_args = wp_slash( $update_args );
				wp_update_post( $update_args );
			}

			do_action( 'pixcare_sce_after_insert_post', $received_post, $imported_ids, $demo_key );
		}

		// Remember the imported post IDs.
		$imported_starter_content[ $demo_key ]['post_types'][ $post_type ] = $imported_ids;
		// Save the data in the DB.
		PixelgradeCare_Admin::set_option( 'imported_starter_content', $imported_starter_content );
		PixelgradeCare_Admin::save_options();

		// Let others do something after a certain post type posts are imported.
		do_action( 'pixcare_sce_imported_post_type', $post_type, $imported_ids, $demo_key );

		// Return the imported post IDs.
		return $imported_ids;
	}

	/**
	 * Parse the blocks in the content and replace already imported taxonomies term IDs where it is appropriate.
	 *
	 * Mainly it's about the query block and its taxQuery argument.
	 *
	 * @param string $post_content
	 * @param array  $imported_taxonomies
	 *
	 * @return string
	 */
	private function maybe_replace_tax_ids_in_blocks( $post_content, $imported_taxonomies ) {
		// We will only do the work for post content with blocks and if the WP version is at least 5.9.
		if ( ! has_blocks( $post_content ) || ! version_compare( get_bloginfo( 'version' ), '5.9', '>=' ) ) {
			return $post_content;
		}

		$has_updated_content = false;
		$new_content         = '';
		$template_blocks     = parse_blocks( $post_content );

		$blocks = _flatten_blocks( $template_blocks );
		foreach ( $blocks as &$block ) {
			// Handle all the blocks that may need handling.

			// Replace the term IDs in the taxQuery attribute of the core Query block with the imported ones.
			if (
				'core/query' === $block['blockName'] &&
				! empty( $block['attrs']['query']['taxQuery'] )
			) {
				foreach ( $block['attrs']['query']['taxQuery'] as $taxonomy => $term_ids ) {
					if ( empty( $imported_taxonomies[ $taxonomy ] ) || empty( $term_ids ) ) {
						continue;
					}

					foreach ( $term_ids as $key => $term_id ) {
						if ( ! empty( $imported_taxonomies[ $taxonomy ][ $term_id ] ) ) {
							// Replace the old term ID with the imported on.
							$block['attrs']['query']['taxQuery'][ $taxonomy ][ $key ] = $imported_taxonomies[ $taxonomy ][ $term_id ];
						} else {
							// We will clean up any term IDs that we can't find in the imported data, just to be safe.
							unset( $block['attrs']['query']['taxQuery'][ $taxonomy ][ $key ] );
						}
					}

					$has_updated_content = true;
				}
			}

			// Replace the term IDs in the attributes of the core navigation-link block with the imported ones.
			if (
				'core/navigation-link' === $block['blockName']
				&& ! empty( $block['attrs']['type'] )
				&& ! empty( $block['attrs']['kind'] )
				&& 'taxonomy' === $block['attrs']['kind']
				&& ! empty( $block['attrs']['id'] )
			) {

				if ( ! empty( $imported_taxonomies[ $block['attrs']['type'] ][ $block['attrs']['id'] ] ) ) {
					// Replace the old term ID with the imported on.
					$block['attrs']['id'] = $imported_taxonomies[ $block['attrs']['type'] ][ $block['attrs']['id'] ];
					$has_updated_content = true;
				}
			}
		}

		if ( $has_updated_content ) {
			foreach ( $template_blocks as &$block ) {
				$new_content .= serialize_block( $block );
			}

			return $new_content;
		}

		return $post_content;
	}

	/**
	 * Parse the blocks in the content and replace already imported post IDs where it is appropriate.
	 *
	 * It is important that the order in which posts (types) are imported allows for this replacement.
	 *
	 * @param string $post_content
	 * @param array  $imported_post_types
	 *
	 * @return string
	 */
	private function maybe_replace_post_ids_in_blocks( $post_content, $imported_post_types ) {
		// We will only do the work for post content with blocks and if the WP version is at least 5.9.
		if ( ! has_blocks( $post_content ) || ! version_compare( get_bloginfo( 'version' ), '5.9', '>=' ) ) {
			return $post_content;
		}

		$has_updated_content = false;
		$new_content         = '';
		$template_blocks     = parse_blocks( $post_content );

		$blocks = _flatten_blocks( $template_blocks );
		foreach ( $blocks as &$block ) {
			// Handle all the blocks that may need handling.

			// Replace the post IDs in the attributes of the core navigation-link block with the imported ones.
			if (
				'core/navigation-link' === $block['blockName']
				&& ! empty( $block['attrs']['type'] )
				&& ! empty( $block['attrs']['kind'] )
				&& 'post-type' === $block['attrs']['kind']
				&& ! empty( $block['attrs']['id'] )
			) {

				if ( ! empty( $imported_post_types[ $block['attrs']['type'] ][ $block['attrs']['id'] ] ) ) {
					// Replace the old post ID with the imported on.
					$block['attrs']['id'] = $imported_post_types[ $block['attrs']['type'] ][ $block['attrs']['id'] ];
					$has_updated_content = true;
				}
			}

			// Replace the post IDs in the attributes of the core navigation block with the imported ones.
			if (
				'core/navigation' === $block['blockName']
				&& ! empty( $block['attrs']['ref'] )
			) {

				if ( ! empty( $imported_post_types[ 'wp_navigation' ][ $block['attrs']['ref'] ] ) ) {
					// Replace the old post ID with the imported on.
					$block['attrs']['ref'] = $imported_post_types[ 'wp_navigation' ][ $block['attrs']['ref'] ];
					$has_updated_content = true;
				}
			}
		}

		if ( $has_updated_content ) {
			foreach ( $template_blocks as &$block ) {
				$new_content .= serialize_block( $block );
			}

			return $new_content;
		}

		return $post_content;
	}

	/**
	 * Import the terms from a certain taxonomy.
	 *
	 * @param string $demo_key
	 * @param string $base_rest_url
	 * @param array  $args
	 *
	 * @return bool|array|WP_Error|WP_REST_Response
	 */
	private function import_taxonomy( $demo_key, $base_rest_url, $args ) {
		$imported_ids = [];

		if ( empty( $args['ids'] ) || empty( $args['tax'] ) ) {
			return false;
		}

		if ( ! taxonomy_exists( $args['tax'] )
		     || ( in_array( $args['tax'], ['product_cat', 'product_tag' ] ) && ! pixelgrade_user_has_access( 'woocommerce' ) )
		) {
			return rest_ensure_response( [
				'code'    => 'success',
				/* translators: %s: the taxonomy name */
				'message' => sprintf( esc_html__( 'Skipping "%s" terms due to missing taxonomy or lack of access (maybe you need an add-on?)', 'pixelgrade_care' ), $args['tax'] ),
				'data'    => [],
			] );
		}

		// Get the terms already imported
		$imported_starter_content = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );

		// Make sure that we have the necessary entries
		if ( empty( $imported_starter_content ) ) {
			$imported_starter_content = [];
		}
		if ( empty( $imported_starter_content[ $demo_key ] ) ) {
			$imported_starter_content[ $demo_key ] = [];
		}
		if ( ! isset( $imported_starter_content[ $demo_key ]['taxonomies'] ) ) {
			$imported_starter_content[ $demo_key ]['taxonomies'] = [];
		}

		$request_url = trailingslashit( $base_rest_url ) . 'terms';

		$request_data = [
			'taxonomy' => $args['tax'],
			'include'  => $args['ids'],
		];

		$request_args = [
			'method'    => 'POST',
			'timeout'   => 5,
			'blocking'  => true,
			'body'      => $request_data,
			'sslverify' => false,
		];

		// Increase timeout if the target URL is a development one so we can account for slow local (development) installations.
		if ( PixelgradeCare_Admin::is_development_url( $request_url ) ) {
			$request_args['timeout'] = 10;
		}

		// We will do a blocking request
		$response = wp_remote_request( $request_url, $request_args );
		if ( is_wp_error( $response ) ) {
			return rest_ensure_response( $response );
		}

		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		// Bail in case of decode error or failure to retrieve data
		if ( null === $response_data ) {
			return rest_ensure_response( [
				'code'    => 'json_error',
				'message' => esc_html__( 'Something went wrong with decoding the data received.', 'pixelgrade_care' ),
				'data'    => [
					'response' => wp_remote_retrieve_body( $response ),
				],
			] );
		}

		if ( empty( $response_data['code'] ) || 'success' !== $response_data['code'] ) {
			return rest_ensure_response( $response_data );
		}

		foreach ( $response_data['data']['terms'] as $i => $term ) {

			$term_args = [
				'description' => $term['description'],
				'slug'        => $term['slug'],
			];

			$new_id = wp_insert_term(
				$term['name'],     // the term
				$term['taxonomy'], // the taxonomy
				$term_args
			);

			if ( is_wp_error( $new_id ) ) {
				// If the term exists, we will use the existing ID.
				if ( ! empty( $new_id->error_data['term_exists'] ) ) {
					$imported_ids[ $term['term_id'] ] = $new_id->error_data['term_exists'];
				}
			} else {
				$imported_ids[ $term['term_id'] ] = $new_id['term_id'];

				if ( ! empty( $term['meta'] ) ) {

					foreach ( $term['meta'] as $key => $meta ) {
						$value = false;
						if ( isset( $meta[0] ) ) {
							$value = maybe_unserialize( $meta[0] );
						}

						if ( 'pix_term_icon' === $key && isset( $imported_starter_content[ $demo_key ]['media']['ignored'][ $value ]['imported_id'] ) ) {
							$value = absint( $imported_starter_content[ $demo_key ]['media']['ignored'][ $value ]['imported_id'] );
						}

						update_term_meta( $new_id['term_id'], $key, $value );
					}
					update_term_meta( $new_id['term_id'], 'imported_with_pixcare_at', current_time( 'mysql', 1 ) );
				}
			}

			// Clear the term cache
			if ( ! is_wp_error( $new_id ) && ! empty( $new_id['term_id'] ) ) {
				clean_term_cache( $new_id['term_id'], $args['tax'] );
			}
		}

		// Bind the parents.
		foreach ( $response_data['data']['terms'] as $i => $term ) {
			if ( isset( $imported_ids[ $term['parent'] ] ) ) {
				wp_update_term( $imported_ids[ $term['term_id'] ], $args['tax'], [
					'parent' => $imported_ids[ $term['parent'] ],
				] );
			}
		}

		// Save the imported term IDs
		$imported_starter_content[ $demo_key ]['taxonomies'][ $args['tax'] ] = $imported_ids;
		// Save the data in the DB
		PixelgradeCare_Admin::set_option( 'imported_starter_content', $imported_starter_content );
		PixelgradeCare_Admin::save_options();

		// Return the imported term IDs
		return $imported_ids;
	}

	/**
	 * @param string $demo_key
	 * @param string $type
	 * @param array $data
	 *
	 * @return bool
	 */
	private function import_settings( $demo_key, $type, $data ) {
		if ( ! is_array( $data ) ) {
			$data = json_decode( $data, true );
		}

		$settings_key = $type . '_settings';

		if ( empty( $data ) ) {
			return false;
		}

		$imported_starter_content = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );

		// Make sure that we have the necessary entries
		if ( empty( $imported_starter_content ) ) {
			$imported_starter_content = [];
		}
		if ( empty( $imported_starter_content[ $demo_key ] ) ) {
			$imported_starter_content[ $demo_key ] = [];
		}
		if ( empty( $imported_starter_content[ $demo_key ][ $settings_key ] ) ) {
			$imported_starter_content[ $demo_key ][ $settings_key ] = [];
		}

		if ( ! empty( $data['mods'] ) ) {

			if ( empty( $imported_starter_content[ $demo_key ][ $settings_key ]['mods'] ) ) {
				$imported_starter_content[ $demo_key ][ $settings_key ]['mods'] = [];
			}

			foreach ( $data['mods'] as $mod_key => $mod_value ) {
				if ( is_array( $mod_value ) ) {
					$mod_value = array_map( [ $this, 'sanitize_import_value' ], $mod_value );
				} else {
					$mod_value = $this->sanitize_import_value( $mod_value );
				}

				$mod_value = apply_filters( "pixcare_sce_import_{$type}_theme_mod_{$mod_key}", $mod_value, $demo_key );

				// Check if the key refers to a sub-entry.
				// For these entries we only want to update the sub-entry, not overwrite the whole theme_mods entry.
				if ( false !== strpos( $mod_key, '[' ) ) {
					preg_match( '#(.+)\[(?:[\'\"]*)([^\'\"]+)(?:[\'\"]*)\]#', $mod_key, $matches );

					if ( ! empty( $matches )
					     && ! empty( $matches[1] )
					     && ! empty( $matches[2] )
					) {

						$primary_mod_key = $matches[1];
						$sub_mod_key     = $matches[2];

						$primary_mod_value = get_theme_mod( $primary_mod_key );
						if ( empty( $primary_mod_value ) ) {
							$primary_mod_value = [];
						}
						if ( ! isset( $primary_mod_value[ $sub_mod_key ] ) ) {
							$primary_mod_value[ $sub_mod_key ] = '';
						}

						if ( empty( $imported_starter_content[ $demo_key ][ $settings_key ]['mods'][ $primary_mod_key ] ) ) {
							$imported_starter_content[ $demo_key ][ $settings_key ]['mods'][ $primary_mod_key ] = [];
						}
						$imported_starter_content[ $demo_key ][ $settings_key ]['mods'][ $primary_mod_key ][ $sub_mod_key ] = $primary_mod_value[ $sub_mod_key ];

						$primary_mod_value[ $sub_mod_key ] = $mod_value;
						set_theme_mod( $primary_mod_key, $primary_mod_value );
					}
				} else {
					$imported_starter_content[ $demo_key ][ $settings_key ]['mods'][ $mod_key ] = get_theme_mod( $mod_key );
					set_theme_mod( $mod_key, $mod_value );
				}
			}
		}

		if ( ! empty( $data['options'] ) ) {
			if ( empty( $imported_starter_content[ $demo_key ][ $settings_key ]['options'] ) ) {
				$imported_starter_content[ $demo_key ][ $settings_key ]['options'] = [];
			}

			foreach ( $data['options'] as $option_key => $option_value ) {

				if ( is_array( $option_value ) ) {
					$option_value = array_map( [ $this, 'sanitize_import_value' ], $option_value );
				} else {
					$option_value = $this->sanitize_import_value( $option_value );
				}

				$option_value = apply_filters( "pixcare_sce_import_{$type}_option_{$option_key}", $option_value, $demo_key );

				// Check if the key refers to a sub-entry.
				// For these entries we only want to update the sub-entry, not overwrite the whole option entry.
				if ( false !== strpos( $option_key, '[' ) ) {
					preg_match( '#(.+)\[(?:[\'\"]*)([^\'\"]+)(?:[\'\"]*)\]#', $option_key, $matches );

					if ( ! empty( $matches )
					     && ! empty( $matches[1] )
					     && ! empty( $matches[2] )
					) {

						$primary_option_key = $matches[1];
						$sub_option_key     = $matches[2];

						$primary_option_value = get_option( $primary_option_key );
						if ( empty( $primary_option_value ) ) {
							$primary_option_value = [];
						}
						if ( ! isset( $primary_option_value[ $sub_option_key ] ) ) {
							$primary_option_value[ $sub_option_key ] = '';
						}

						if ( empty( $imported_starter_content[ $demo_key ][ $settings_key ]['options'][ $primary_option_key ] ) ) {
							$imported_starter_content[ $demo_key ][ $settings_key ]['options'][ $primary_option_key ] = [];
						}
						$imported_starter_content[ $demo_key ][ $settings_key ]['options'][ $primary_option_key ][ $sub_option_key ] = $primary_option_value[ $sub_option_key ];

						$primary_option_value[ $sub_option_key ] = $option_value;
						update_option( $primary_option_key, $primary_option_value );
					}
				} else {
					$imported_starter_content[ $demo_key ][ $settings_key ]['options'][ $option_key ] = get_option( $option_key );
					update_option( $option_key, $option_value );
				}

				// Handle special keys.
				switch ( $option_key ) {
					case 'permalink_structure':
					case 'woocommerce_permalinks':
						// When these options are updated we need to flush the rewrite rules.
						flush_rewrite_rules();
						break;
					default:
						break;
				}
			}
		}

		if ( 'pre' === $type ) {
			do_action( 'pixcare_sce_import_start', $demo_key );
		}


		if ( 'post' === $type ) {
			do_action( 'pixcare_sce_import_end', $demo_key );
		}

		// Save the data in the DB
		PixelgradeCare_Admin::set_option( 'imported_starter_content', $imported_starter_content );
		PixelgradeCare_Admin::save_options();

		return true;
	}

	/**
	 * Import the widgets (not used right now).
	 *
	 * @param string $demo_key
	 * @param array $data
	 *
	 * @return bool
	 */
	private function import_widgets( $demo_key, $data ) {

		if ( empty( $data ) ) {
			return false;
		}

		$starter_content = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );
		// Make sure that we have the necessary entries
		if ( empty( $starter_content ) ) {
			$starter_content = [];
		}
		if ( empty( $starter_content[ $demo_key ] ) ) {
			$starter_content[ $demo_key ] = [];
		}

		// First let's remove all the widgets in sidebars to avoid a big mess
		$sidebars_widgets = wp_get_sidebars_widgets();
		foreach ( $sidebars_widgets as $sidebarID => $widgets ) {
			if ( $sidebarID != 'wp_inactive_widgets' ) {
				$sidebars_widgets[ $sidebarID ] = [];
			}
		}
		wp_set_sidebars_widgets( $sidebars_widgets );

		// Let's get to work
		$json_data = json_decode( base64_decode( $data ), true );

		$sidebar_data = $json_data[0];
		$widget_data  = $json_data[1];

		foreach ( $sidebar_data as $type => $sidebar ) {
			$count = count( $sidebar );
			for ( $i = 0; $i < $count; $i ++ ) {
				$widget               = [];
				$widget['type']       = trim( substr( $sidebar[ $i ], 0, strrpos( $sidebar[ $i ], '-' ) ) );
				$widget['type-index'] = trim( substr( $sidebar[ $i ], strrpos( $sidebar[ $i ], '-' ) + 1 ) );
				if ( ! isset( $widget_data[ $widget['type'] ][ $widget['type-index'] ] ) ) {
					unset( $sidebar_data[ $type ][ $i ] );
				}
			}
			$sidebar_data[ $type ] = array_values( $sidebar_data[ $type ] );
		}

		$sidebar_data = [ array_filter( $sidebar_data ), $widget_data ];

		$starter_content[ $demo_key ]['widgets'] = false;

		if ( ! $this->parse_import_data( $sidebar_data, $demo_key ) ) {

			$starter_content[ $demo_key ]['widgets'] = true;

			// Save the data in the DB
			PixelgradeCare_Admin::set_option( 'imported_starter_content', $starter_content );
			PixelgradeCare_Admin::save_options();

			return false;
		}

		return $starter_content[ $demo_key ]['widgets'];
	}

	/**
	 * Import widgets with the settings already parsed on the data origin server.
	 *
	 * @param string $demo_key
	 * @param string $base_rest_url
	 *
	 * @return bool|null
	 */
	private function import_parsed_widgets( $demo_key, $base_rest_url ) {
		$starter_content = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );
		// Make sure that we have the necessary entries
		if ( empty( $starter_content ) ) {
			$starter_content = [];
		}
		if ( empty( $starter_content[ $demo_key ] ) ) {
			$starter_content[ $demo_key ] = [];
		}

		$request_url = trailingslashit( $base_rest_url ) . 'widgets';

		$request_data = [
			'post_types'     => empty( $starter_content[ $demo_key ]['post_types'] ) ? [] : $starter_content[ $demo_key ]['post_types'],
			'taxonomies'     => empty( $starter_content[ $demo_key ]['taxonomies'] ) ? [] : $starter_content[ $demo_key ]['taxonomies'],
			'placeholders'   => $this->get_placeholders( $demo_key ),
			'ignored_images' => $this->get_ignored_images( $demo_key ),
		];

		$request_args = [
			'method'    => 'POST',
			'timeout'   => 5,
			'blocking'  => true,
			'body'      => $request_data,
			'sslverify' => false,
		];

		// Increase timeout if the target URL is a development one so we can account for slow local (development) installations.
		if ( PixelgradeCare_Admin::is_development_url( $request_url ) ) {
			$request_args['timeout'] = 10;
		}

		// We will do a blocking request
		$response = wp_remote_request( $request_url, $request_args );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$response_data = json_decode( wp_remote_retrieve_body( $response ), true );
		// Bail in case of decode error or failure to retrieve data
		if ( null === $response_data || empty( $response_data['data']['widgets'][0] ) || empty( $response_data['data']['widgets'][1] ) ) {
			return false;
		}

		// First let's remove all the widgets in the sidebars to avoid a big mess
		$sidebars_widgets = wp_get_sidebars_widgets();
		foreach ( $sidebars_widgets as $sidebarID => $widgets ) {
			if ( $sidebarID != 'wp_inactive_widgets' ) {
				$sidebars_widgets[ $sidebarID ] = [];
			}
		}
		wp_set_sidebars_widgets( $sidebars_widgets );

		$sidebar_data = $response_data['data']['widgets'][0];
		$widget_data  = $response_data['data']['widgets'][1];

		if ( ! empty( $sidebar_data ) ) {
			foreach ( $sidebar_data as $key => $sidebar ) {
				$count = count( $sidebar );
				for ( $i = 0; $i < $count; $i ++ ) {
					$widget               = [];
					$widget['type']       = trim( substr( $sidebar[ $i ], 0, strrpos( $sidebar[ $i ], '-' ) ) );
					$widget['type-index'] = trim( substr( $sidebar[ $i ], strrpos( $sidebar[ $i ], '-' ) + 1 ) );
					if ( ! isset( $widget_data[ $widget['type'] ][ $widget['type-index'] ] ) ) {
						unset( $sidebar_data[ $key ][ $i ] );
					}
				}
				$sidebar_data[ $key ] = array_values( $sidebar_data[ $key ] );
			}
		}

		if ( ! is_array( $sidebar_data ) || empty( $sidebar_data ) ) {
			return null;
		}

		$sidebar_data = [ array_filter( $sidebar_data ), $widget_data ];

		$starter_content[ $demo_key ]['widgets'] = false;

		if ( $this->parse_import_data( $sidebar_data, $demo_key ) ) {

			$starter_content[ $demo_key ]['widgets'] = true;

			// Save the data in the DB
			PixelgradeCare_Admin::set_option( 'imported_starter_content', $starter_content );
			PixelgradeCare_Admin::save_options();

			// ugly bug, ugly fix ... import widgets twice
			// @todo What Does This Mean? Ugly bug! What is the bug? Where the ... is it?!?
			$this->parse_import_data( $sidebar_data, $demo_key );
		}

		return $starter_content[ $demo_key ]['widgets'];
	}

	/**
	 * ================
	 * Widgets helpers
	 * ================
	 */

	/**
	 * @param array $import_array
	 * @param string $demo_key
	 *
	 * @return bool
	 */
	private function parse_import_data( $import_array, $demo_key ) {
		// Bail if we have no data to work with
		if ( empty( $import_array[0] ) || empty( $import_array[1] ) ) {
			return false;
		}

		$sidebars_data = $import_array[0];
		$widget_data   = $import_array[1];

		$current_sidebars = wp_get_sidebars_widgets();
		$new_widgets      = [];

		foreach ( $sidebars_data as $import_sidebar => $import_widgets ) :
			$current_sidebars[ $import_sidebar ] = [];
			foreach ( $import_widgets as $import_widget ) :

				$import_widget = json_decode( json_encode( $import_widget ), true );

				$type                = trim( substr( $import_widget, 0, strrpos( $import_widget, '-' ) ) );
				$index               = trim( substr( $import_widget, strrpos( $import_widget, '-' ) + 1 ) );
				$current_widget_data = get_option( 'widget_' . $type );
				$new_widget_name     = $this->get_new_widget_name( $type, $index );
				$new_index           = trim( substr( $new_widget_name, strrpos( $new_widget_name, '-' ) + 1 ) );

				if ( is_array( $new_widgets[ $type ] ) ) {
					while ( array_key_exists( $new_index, $new_widgets[ $type ] ) ) {
						$new_index ++;
					}
				}
				$current_sidebars[ $import_sidebar ][] = $type . '-' . $new_index;
				if ( array_key_exists( $type, $new_widgets ) ) {
					$new_widgets[ $type ][ $new_index ] = $widget_data[ $type ][ $index ];
				} else {
					$current_widget_data[ $new_index ] = $widget_data[ $type ][ $index ];
					$new_widgets[ $type ]              = $current_widget_data;
				}

				// All widgets should use the new format _multiwidget
				$new_widgets[ $type ]['_multiwidget'] = 1;
			endforeach;
		endforeach;

		if ( ! empty( $new_widgets ) && ! empty( $current_sidebars ) ) {
			foreach ( $new_widgets as $type => $content ) {
				// Save the data for each widget type
				$content = apply_filters( "pixcare_sce_import_widget_{$type}", $content, $type, $demo_key );
				update_option( 'widget_' . $type, $content );
			}

			// Save the sidebars data
			wp_set_sidebars_widgets( $current_sidebars );

			return true;
		}

		return false;
	}

	/**
	 * @param string $widget_name
	 * @param string|int $widget_index
	 *
	 * @return string
	 */
	private function get_new_widget_name( $widget_name, $widget_index ) {
		$current_sidebars = get_option( 'sidebars_widgets' );
		$all_widget_array = [];
		foreach ( $current_sidebars as $sidebar => $widgets ) {
			if ( is_array( $widgets ) && $sidebar != 'wp_inactive_widgets' ) {
				foreach ( $widgets as $widget ) {
					$all_widget_array[] = $widget;
				}
			}
		}
		while ( in_array( $widget_name . '-' . $widget_index, $all_widget_array ) ) {
			$widget_index ++;
		}
		$new_widget_name = $widget_name . '-' . $widget_index;

		return $new_widget_name;
	}

	/** CUSTOM FILTERS */
	public function prepare_menus_links( $post, $imported_ids, $demo_key ) {

		if ( 'nav_menu_item' !== $post['post_type'] ) {
			return;
		}

		/**
		 * We need to remap the nav menu item parent.
		 */
		if ( ! empty( $post['meta']['_menu_item_menu_item_parent'] ) ) {
			$menu_item_menu_item_parent = maybe_unserialize( $post['meta']['_menu_item_menu_item_parent'][0] );
			if ( ! empty( $menu_item_menu_item_parent ) && isset( $imported_ids[ $menu_item_menu_item_parent ] )
			     && $imported_ids[ $menu_item_menu_item_parent ] != $menu_item_menu_item_parent ) {

				update_post_meta( $imported_ids[ $post['ID'] ], '_menu_item_menu_item_parent', $imported_ids[ $menu_item_menu_item_parent ] );
			} else {

				// Now test of the menu-item parent item actually exists. We may have skipped it.
				// In this case, we will delete all child menu-items (with missing parent menu-items).
				if ( ! empty( $imported_ids[ $post['ID'] ] ) && ! empty( $menu_item_menu_item_parent ) && null === get_post( $menu_item_menu_item_parent ) ) {
					wp_delete_post( $imported_ids[ $post['ID'] ] );
					return;
				}
			}
		}

		$starter_content     = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );
		$menu_item_type      = maybe_unserialize( $post['meta']['_menu_item_type'] );
		$menu_item_type      = wp_slash( $menu_item_type[0] );
		$menu_item_object    = maybe_unserialize( $post['meta']['_menu_item_object'] );
		$menu_item_object    = wp_slash( $menu_item_object[0] );
		$menu_item_object_id = maybe_unserialize( $post['meta']['_menu_item_object_id'] );
		$menu_item_object_id = wp_slash( $menu_item_object_id[0] );

		// Try to remap custom objects in nav items.
		switch ( $menu_item_type ) {
			case 'taxonomy':
				if ( isset( $starter_content[ $demo_key ]['taxonomies'][ $menu_item_object ][ $menu_item_object_id ] ) ) {
					$menu_item_object_id = $starter_content[ $demo_key ]['taxonomies'][ $menu_item_object ][ $menu_item_object_id ];
				}
				break;
			case 'post_type':
				if ( isset( $starter_content[ $demo_key ]['post_types'][ $menu_item_object ][ $menu_item_object_id ] ) ) {
					$menu_item_object_id = $starter_content[ $demo_key ]['post_types'][ $menu_item_object ][ $menu_item_object_id ];
				}
				break;
			case 'custom':
				/**
				 * Remap custom links.
				 */
				$meta_url = get_post_meta( $post['ID'], '_menu_item_url', true );
				if ( isset( $_POST['demo_url'] ) && ! empty( $meta_url ) ) {
					$meta_url = str_replace( self::cleanup_url( $_POST['demo_url'] ), self::cleanup_url( site_url() ), $meta_url );
					update_post_meta( $imported_ids[ $post['ID'] ], '_menu_item_url', esc_url_raw( $meta_url ) );
					return;
				}
				break;
			default:
				// Nothing to do.
				break;
		}

		update_post_meta( $imported_ids[ $post['ID'] ], '_menu_item_object_id', wp_slash( $menu_item_object_id ) );
	}

	/**
	 * We will replace the URl of the starter content site with the one of the current site, in all DB tables.
	 */
	private function replace_demo_urls_in_db() {
		// We need the source starter content URL.
		if ( empty( trim( $_POST['demo_url'] ) ) ) {
			return;
		}

		// Make sure that the DB processing logic is loaded.
		require_once plugin_dir_path( $this->parent->file ) . 'includes/lib/class-pixelgrade_care-dbsearchreplace.php';
		$dbsr = new PixelgradeCare_DbSearchReplace();

		$search_for = self::cleanup_url( $_POST['demo_url'] );
		$replace_with = self::cleanup_url( site_url() );

		$db_tables = $dbsr::get_tables();
		foreach ( $db_tables as $db_table ) {
			// Set up the arguments for the run.
			$args = [
				'select_tables' 	=> [ $db_table ],
				'case_insensitive' 	=> 'on',
				'replace_guids' 	=> 'on',
				'dry_run' 			=> 'off',
				'search_for' 		=> esc_url_raw( $search_for ),
				'replace_with' 		=> esc_url_raw( $replace_with ),
				'completed_pages' 	=> 0,
				'total_pages'       => 1, // For now, we will not be doing paged replace; only the first (large) page.
			];

			$dbsr->srdb( $db_table, 0, $args );
		}
	}

	public function end_import() {
		$this->replace_demo_urls_in_db();

		// Just to be sure, flush the rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Cleans a URL (remove protocol and such).
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

		$url = untrailingslashit( $url );

		// Remove the get parameters.
		$url = strtok( $url, '?' );

		// Make it protocol relative.
		$url = preg_replace( '(^https?://)', '//', $url );

		return $url;
	}

	/**
	 * Replace the value of the `page_on_front` option with the id of the local front page
	 *
	 * @param string|int $value
	 * @param string $demo_key
	 *
	 * @return string|int
	 */
	public function filter_post_option_page_on_front( $value, $demo_key ) {
		$starter_content = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );
		if ( isset( $starter_content[ $demo_key ]['post_types']['page'][ $value ] ) ) {
			return $starter_content[ $demo_key ]['post_types']['page'][ $value ];
		}

		return $value;
	}

	/**
	 * Replace the value of the `page_for_posts` option with the id of the local blog page
	 *
	 * @param string|int $value
	 * @param string $demo_key
	 *
	 * @return string|int
	 */
	public function filter_post_option_page_for_posts( $value, $demo_key ) {
		$starter_content = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );
		if ( isset( $starter_content[ $demo_key ]['post_types']['page'][ $value ] ) ) {
			return $starter_content[ $demo_key ]['post_types']['page'][ $value ];
		}

		return $value;
	}

	/**
	 * Replace each menu id from `nav_menu_locations` with the new menus ids
	 *
	 * @param array $locations
	 * @param string $demo_key
	 *
	 * @return array
	 */
	public function filter_post_theme_mod_nav_menu_locations( $locations, $demo_key ) {
		if ( empty( $locations ) ) {
			return $locations;
		}

		$starter_content = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );

		foreach ( $locations as $location => $menu ) {
			if ( ! empty( $starter_content[ $demo_key ]['taxonomies']['nav_menu'][ $menu ] ) ) {
				$locations[ $location ] = $starter_content[ $demo_key ]['taxonomies']['nav_menu'][ $menu ];
			}
		}

		return $locations;
	}

	/**
	 * If there is a custom logo set, it will surely come with another attachment_id
	 * Wee need to replace the old attachment id with the local one
	 *
	 * @param int $attach_id
	 * @param string $demo_key
	 *
	 * @return int
	 */
	public function filter_post_theme_mod_custom_logo( $attach_id, $demo_key ) {
		if ( empty( $attach_id ) ) {
			return $attach_id;
		}

		$starter_content = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );

		if ( ! empty( $starter_content[ $demo_key ]['media']['ignored'][ $attach_id ]['imported_id'] ) ) {
			return absint( $starter_content[ $demo_key ]['media']['ignored'][ $attach_id ]['imported_id'] );
		}

		if ( ! empty( $starter_content[ $demo_key ]['media']['placeholders'][ $attach_id ]['imported_id'] ) ) {
			return absint( $starter_content[ $demo_key ]['media']['placeholders'][ $attach_id ]['imported_id'] );
		}

		return $attach_id;
	}

	public function add_imported_content_to_theme_config( $config ) {

		if ( empty( $config['starterContent'] ) ) {
			$config['starterContent'] = [];
		}
		if ( empty( $config['starterContent']['alreadyImported'] ) ) {
			$config['starterContent']['alreadyImported'] = [];
		}

		$imported_starter_content                    = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );
		$config['starterContent']['alreadyImported'] = array_merge( $config['starterContent']['alreadyImported'], $imported_starter_content );

		return $config;
	}

	/**
	 * We should allow svg uploads but only inside our REST route `sce/v2/upload_media`
	 *
	 * @param array $mimes
	 *
	 * @return array
	 */
	public function allow_svg_upload( $mimes ) {
		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';

		return $mimes;
	}

	/**
	 * Whatever the Exporter tells us, we will not replace the theme's style with the Jetpack's custom CSS
	 *
	 * @param array $value
	 *
	 * @return array
	 */
	public function uncheck_jetpack_custom_css_style_replacement( $value ) {
		if ( isset( $value['replace'] ) ) {
			$value['replace'] = false;
		}

		return $value;
	}

	/** END CUSTOM FILTERS */

	/**
	 * Modify the default `Hello world!` post so that it doesn't get in the way.
	 *
	 * @param string $post_type
	 *
	 * @return void
	 */
	public function modify_hello_world_post( $post_type ) {
		if ( 'post' !== $post_type ) {
			return;
		}

		// We simply want to make the `Hello world!` post the oldest post.
		// First, find the post.
		$query       = new WP_Query( [
			'post_type'        => 'post',
			'posts_per_page'   => 1,
			'suppress_filters' => true,
			'title'            => 'Hello world!',
		] );
		$found_posts = $query->get_posts();
		if ( empty( $found_posts ) ) {
			return;
		}

		// Do another query to get the oldest post, except the `Hello world!` post.
		$query        = new WP_Query( [
			'post_type'        => 'post',
			'posts_per_page'   => 1,
			'suppress_filters' => true,
			'exclude'          => [ $found_posts[0]->ID ],
			'order'            => 'ASC',
			'orderby'          => 'date',
		] );
		$oldest_posts = $query->get_posts();
		if ( empty( $oldest_posts ) ) {
			// It seems we only have one post.
			return;
		}
		if ( $oldest_posts[0]->ID === $found_posts[0]->ID || strtotime( $oldest_posts[0]->post_date_gmt ) > strtotime( $found_posts[0]->post_date_gmt ) ) {
			// The `Hello world!` post is already the oldest.
			return;
		}

		wp_update_post( [
			'ID'            => $found_posts[0]->ID,
			// Set it one day older than the oldest other post.
			'post_date' => date( 'Y-m-d H:i:s', strtotime( $oldest_posts[0]->post_date ) - DAY_IN_SECONDS ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( $oldest_posts[0]->post_date_gmt ) - DAY_IN_SECONDS ),
		] );
	}

	/**
	 * ========
	 * HELPERS
	 * ========
	 */

	/**
	 * Get the already imported placeholder attachment details.
	 *
	 * @param string $demo_key
	 * @param string|false $post_type
	 *
	 * @return array The list of imported placeholders keyed by each attachment's old ID
	 *              (the one used on the starter content provider site).
	 */
	private function get_placeholders( $demo_key, $post_type = false ) {
		$imported_media = [];

		$imported_starter_content = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );

		// First, look for post type specific media.
		// Otherwise, use the general ones.
		$placeholders = [];
		if ( ! empty( $post_type ) && ! empty( $imported_starter_content[ $demo_key ]['post_types'][$post_type]['media']['placeholders'] ) ) {
			$placeholders = $imported_starter_content[ $demo_key ]['post_types'][$post_type]['media']['placeholders'];
		} else if ( ! empty( $imported_starter_content[ $demo_key ]['media']['placeholders'] ) ) {
			$placeholders = $imported_starter_content[ $demo_key ]['media']['placeholders'];
		}

		foreach ( $placeholders as $old_id => $placeholder ) {
			$new_id = false;
			// First, try the new format for imported media details.
			if ( is_array( $placeholder ) ) {
				if ( ! empty( $placeholder['imported_id'] ) ) {
					$new_id = absint( $placeholder['imported_id'] );
				}
			} else {
				// The old format with just the new attachment ID.
				$new_id = absint( $placeholder );
			}

			if ( empty( $new_id ) ) {
				continue;
			}

			$sizes = $this->get_image_thumbnails( $new_id );
			if ( ! empty( $sizes ) ) {
				$imported_media[ $old_id ] = [
					'id'    => $new_id,
					'sizes' => $sizes,
				];
			}
		}

		return $imported_media;
	}

	/**
	 * Get the already imported ignored_images attachment details.
	 *
	 * @param string $demo_key
	 * @param string|false $post_type
	 *
	 * @return array The list of imported ignored_images keyed by each attachment's old ID
	 *              (the one used on the starter content provider site).
	 */
	private function get_ignored_images( $demo_key, $post_type = false ) {
		$imported_media = [];

		$imported_starter_content = PixelgradeCare_Admin::get_option( 'imported_starter_content', [] );

		// First, look for post type specific media.
		// Otherwise, use the general ones.
		$ignored = [];
		if ( ! empty( $post_type ) && ! empty( $imported_starter_content[ $demo_key ]['post_types'][$post_type]['media']['ignored'] ) ) {
			$ignored = $imported_starter_content[ $demo_key ]['post_types'][$post_type]['media']['ignored'];
		} else if ( ! empty( $imported_starter_content[ $demo_key ]['media']['ignored'] ) ) {
			$ignored = $imported_starter_content[ $demo_key ]['media']['ignored'];
		}

		foreach ( $ignored as $old_id => $placeholder ) {
			$new_id = false;
			// First, try the new format for imported media details.
			if ( is_array( $placeholder ) ) {
				if ( ! empty( $placeholder['imported_id'] ) ) {
					$new_id = absint( $placeholder['imported_id'] );
				}
			} else {
				// The old format with just the new attachment ID.
				$new_id = absint( $placeholder );
			}

			if ( empty( $new_id ) ) {
				continue;
			}

			$sizes = $this->get_image_thumbnails( $new_id );
			if ( ! empty( $sizes ) ) {
				$imported_media[ $old_id ] = [
					'id'    => $new_id,
					'sizes' => $sizes,
				];
			}
		}

		return $imported_media;
	}

	/**
	 * Get an array with all image thumbnails details (url, width, height) for a certain image ID.
	 *
	 * @param int $image_id
	 *
	 * @return array
	 */
	private function get_image_thumbnails( $image_id ) {
		$sizes = [];

		// First make sure that we at least have the full size
		$src = wp_get_attachment_image_src( $image_id, 'full' );
		if ( ! empty( $src[0] ) ) {
			$sizes['full'] = $src[0];
		}

		foreach ( get_intermediate_image_sizes() as $size ) {
			$src = wp_get_attachment_image_src( $image_id, $size );
			if ( ! empty( $src[0] ) ) {
				$sizes[ $size ] = [
					'url' => esc_url_raw( $src[0] ),
					'width' => absint( $src[1] ),
					'height' => absint( $src[2] ),
				];
			}
		}

		return $sizes;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return false|int
	 */
	public function permission_nonce_callback( $request ) {
		return wp_verify_nonce( $this->get_nonce( $request ), 'pixelgrade_care_rest' );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return null|string
	 */
	private function get_nonce( $request ) {
		$nonce = null;

		// Get the nonce we've been given
		$nonce = $request->get_param( 'pixcare_nonce' );
		if ( ! empty( $nonce ) ) {
			$nonce = wp_unslash( $nonce );
		}

		return $nonce;
	}

	/**
	 * Decodes a base64 encoded chunk.
	 *
	 * @param string $data
	 *
	 * @return array|bool|string
	 */
	private function decode_chunk( $data ) {
		$data = explode( ';base64,', $data );

		if ( ! is_array( $data ) || ! isset( $data[1] ) ) {
			return false;
		}

		return base64_decode( $data[1] );
	}

	private function the_slug_exists( $post_name, $post_type ) {
		global $wpdb;

		$post_id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . $post_name . "' AND post_type = '" . $post_type . "' LIMIT 1" );
		if ( ! empty( $post_id ) ) {
			return $post_id;
		} else {
			return false;
		}
	}

	public function sanitize_import_value( $value ) {
		// We will handle some edge cases so everything will run smoothly.
		if ( 'false' === $value ) {
			$value = false;
		} elseif ( 'true' === $value ) {
			$value = true;
		}

		return $value;
	}

	/**
	 * Main PixelgradeCareStarterContent Instance
	 *
	 * Ensures only one instance of PixelgradeCareStarterContent is loaded or can be loaded.
	 *
	 * @since  1.3.0
	 * @static
	 *
	 * @param PixelgradeCare $parent Main PixelgradeCare instance.
	 *
	 * @return PixelgradeCare_StarterContent Main PixelgradeCareStarterContent instance
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
