<?php
/**
 * This is the class that handles the overall logic for registering the Gallery custom post type.
 *
 * Inspired by Jetpack's Jetpack_Portfolio class.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PixelgradeCare_Gallery' ) ) :
	require_once 'class-pixelgrade_care-cpt.php';

	class PixelgradeCare_Gallery extends PixelgradeCare_CPT {
		const THEME_SUPPORTS = 'pixcare_gallery';

		const CUSTOM_POST_TYPE = 'gallery';
		const CUSTOM_TAXONOMY_TYPE = 'gallery_type';
		const CUSTOM_TAXONOMY_TAG = 'gallery_tag';

		const OPTION_NAME = 'pixcare_enable_gallery_cpt';
		const OPTION_READING_SETTING = 'pixcare_gallery_posts_per_page';
		const OPTION_ARCHIVE_PAGE_SETTING = 'pixcare_gallery_archive_page';

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
			// Initialize pseudo-constants.
			$this->OPTION_TITLE = __( 'Galleries', 'pixelgrade_care' );

			// Fire up the standard initialization and only continue if that returns true.
			if ( ! parent::init() ) {
				return false;
			}

			// Admin Customization.
			add_filter( 'post_updated_messages', [ $this, 'updated_messages' ] );
			add_filter( sprintf( 'manage_%s_posts_columns', self::CUSTOM_POST_TYPE ), [
				$this,
				'edit_admin_columns',
			] );
			add_filter( sprintf( 'manage_%s_posts_custom_column', self::CUSTOM_POST_TYPE ), [
				$this,
				'image_column',
			], 10, 2 );

			add_image_size( 'pixcare-gallery-admin-thumb', 50, 50, true );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );

			return true;
		}


		/**
		 * HTML code to display a checkbox true/false option for the Portfolio CPT setting.
		 *
		 * @since 1.12.0
		 * @return void
		 */
		public function setting_html() {
			if ( current_theme_supports( self::THEME_SUPPORTS ) ) { ?>
				<p><?php
					/* translators: %s is the name of a custom post type such as "portfolio" */
					printf( __( 'Your theme supports <strong>%s.</strong>', 'pixelgrade_care' ), self::CUSTOM_POST_TYPE ); ?></p>
			<?php } else { ?>
				<label for="<?php echo esc_attr( self::OPTION_NAME ); ?>">
					<input name="<?php echo esc_attr( self::OPTION_NAME ); ?>"
					       id="<?php echo esc_attr( self::OPTION_NAME ); ?>" <?php echo checked( get_option( self::OPTION_NAME, '0' ), true, false ); ?>
					       type="checkbox" value="1"/>
					<?php esc_html_e( 'Enable Galleries for this site.', 'pixelgrade_care' ); ?>
					<a target="_blank"
					   href="#"><?php esc_html_e( 'Learn More', 'pixelgrade_care' ); ?></a>
				</label>
			<?php }
			if ( get_option( self::OPTION_NAME, '0' ) || current_theme_supports( self::THEME_SUPPORTS ) ) {
				printf( '<p><label for="%1$s">%2$s</label></p>',
					esc_attr( self::OPTION_READING_SETTING ),
					/* translators: %1$s is replaced with an input field for numbers */
					sprintf( __( 'Galleries pages display at most %1$s galleries.', 'pixelgrade_care' ),
						sprintf( '<input name="%1$s" id="%1$s" type="number" step="1" min="1" value="%2$s" class="small-text" />',
							esc_attr( self::OPTION_READING_SETTING ),
							esc_attr( get_option( self::OPTION_READING_SETTING, '10' ) )
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
		 * Register Post Type.
		 *
		 * @since 1.12.0
		 */
		protected function register_post_types() {
			if ( post_type_exists( self::CUSTOM_POST_TYPE ) ) {
				return;
			}

			register_post_type( self::CUSTOM_POST_TYPE, [
				'labels'          => [
					'name'                  => esc_html__( 'Galleries', 'pixelgrade_care' ),
					'singular_name'         => esc_html__( 'Gallery', 'pixelgrade_care' ),
					'menu_name'             => esc_html__( 'Galleries', 'pixelgrade_care' ),
					'all_items'             => esc_html__( 'All Galleries', 'pixelgrade_care' ),
					'add_new'               => esc_html__( 'Add New', 'pixelgrade_care' ),
					'add_new_item'          => esc_html__( 'Add New Gallery', 'pixelgrade_care' ),
					'edit_item'             => esc_html__( 'Edit Gallery', 'pixelgrade_care' ),
					'new_item'              => esc_html__( 'New Gallery', 'pixelgrade_care' ),
					'view_item'             => esc_html__( 'View Gallery', 'pixelgrade_care' ),
					'search_items'          => esc_html__( 'Search Galleries', 'pixelgrade_care' ),
					'not_found'             => esc_html__( 'No Galleries found', 'pixelgrade_care' ),
					'not_found_in_trash'    => esc_html__( 'No Galleries found in Trash', 'pixelgrade_care' ),
					'filter_items_list'     => esc_html__( 'Filter gallery list', 'pixelgrade_care' ),
					'items_list_navigation' => esc_html__( 'Gallery list navigation', 'pixelgrade_care' ),
					'items_list'            => esc_html__( 'Galleries list', 'pixelgrade_care' ),
				],
				'supports'        => [
					'title',
					'thumbnail',
					'page-attributes',
					'excerpt',
					'author',
					'comments',
					'publicize',
					'revisions',
					'excerpt',
					'custom-fields',
					'newspack_blocks',
				],
				'rewrite'         => [
					'slug'       => 'galleries',
					'with_front' => false,
					'feeds'      => true,
					'pages'      => true,
				],
				'public'          => true,
				'show_ui'         => true,
				'menu_position'   => 22,                    // below Pages and Portfolio
				'menu_icon'       => 'dashicons-format-gallery', // 3.8+ dashicon option
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'taxonomies'      => [ self::CUSTOM_TAXONOMY_TYPE, self::CUSTOM_TAXONOMY_TAG ],
				'has_archive'     => 'galleries-archive',
				'query_var'       => 'galleries',
				'show_in_rest'    => true,
			] );

			register_taxonomy( self::CUSTOM_TAXONOMY_TYPE, self::CUSTOM_POST_TYPE, [
				'hierarchical'      => true,
				'labels'            => [
					'name'                  => esc_html__( 'Gallery Types', 'pixelgrade_care' ),
					'singular_name'         => esc_html__( 'Gallery Type', 'pixelgrade_care' ),
					'menu_name'             => esc_html__( 'Gallery Types', 'pixelgrade_care' ),
					'all_items'             => esc_html__( 'All Gallery Types', 'pixelgrade_care' ),
					'edit_item'             => esc_html__( 'Edit Gallery Type', 'pixelgrade_care' ),
					'view_item'             => esc_html__( 'View Gallery Type', 'pixelgrade_care' ),
					'update_item'           => esc_html__( 'Update Gallery Type', 'pixelgrade_care' ),
					'add_new_item'          => esc_html__( 'Add New Gallery Type', 'pixelgrade_care' ),
					'new_item_name'         => esc_html__( 'New Gallery Type Name', 'pixelgrade_care' ),
					'parent_item'           => esc_html__( 'Parent Gallery Type', 'pixelgrade_care' ),
					'parent_item_colon'     => esc_html__( 'Parent Gallery Type:', 'pixelgrade_care' ),
					'search_items'          => esc_html__( 'Search Gallery Types', 'pixelgrade_care' ),
					'items_list_navigation' => esc_html__( 'Gallery type list navigation', 'pixelgrade_care' ),
					'items_list'            => esc_html__( 'Gallery type list', 'pixelgrade_care' ),
				],
				'public'            => true,
				'show_ui'           => true,
				'show_in_nav_menus' => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => [ 'slug' => 'gallery-type' ],
			] );

			register_taxonomy( self::CUSTOM_TAXONOMY_TAG, self::CUSTOM_POST_TYPE, [
				'hierarchical'      => false,
				'labels'            => [
					'name'                       => esc_html__( 'Gallery Tags', 'pixelgrade_care' ),
					'singular_name'              => esc_html__( 'Gallery Tag', 'pixelgrade_care' ),
					'menu_name'                  => esc_html__( 'Gallery Tags', 'pixelgrade_care' ),
					'all_items'                  => esc_html__( 'All Gallery Tags', 'pixelgrade_care' ),
					'edit_item'                  => esc_html__( 'Edit Gallery Tag', 'pixelgrade_care' ),
					'view_item'                  => esc_html__( 'View Gallery Tag', 'pixelgrade_care' ),
					'update_item'                => esc_html__( 'Update Gallery Tag', 'pixelgrade_care' ),
					'add_new_item'               => esc_html__( 'Add New Gallery Tag', 'pixelgrade_care' ),
					'new_item_name'              => esc_html__( 'New Gallery Tag Name', 'pixelgrade_care' ),
					'search_items'               => esc_html__( 'Search Gallery Tags', 'pixelgrade_care' ),
					'popular_items'              => esc_html__( 'Popular Gallery Tags', 'pixelgrade_care' ),
					'separate_items_with_commas' => esc_html__( 'Separate tags with commas', 'pixelgrade_care' ),
					'add_or_remove_items'        => esc_html__( 'Add or remove tags', 'pixelgrade_care' ),
					'choose_from_most_used'      => esc_html__( 'Choose from the most used tags', 'pixelgrade_care' ),
					'not_found'                  => esc_html__( 'No tags found.', 'pixelgrade_care' ),
					'items_list_navigation'      => esc_html__( 'Gallery tag list navigation', 'pixelgrade_care' ),
					'items_list'                 => esc_html__( 'Gallery tag list', 'pixelgrade_care' ),
				],
				'public'            => true,
				'show_ui'           => true,
				'show_in_nav_menus' => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => [ 'slug' => 'gallery-tag' ],
			] );
		}

		/**
		 * Update messages for the Gallery admin.
		 *
		 * @since 1.12.0
		 */
		public function updated_messages( $messages ) {
			global $post;

			$messages[ self::CUSTOM_POST_TYPE ] = [
				0  => '', // Unused. Messages start at index 1.
				1  => sprintf( __( 'Gallery updated. <a href="%s">View item</a>', 'pixelgrade_care' ), esc_url( get_permalink( $post->ID ) ) ),
				2  => esc_html__( 'Custom field updated.', 'pixelgrade_care' ),
				3  => esc_html__( 'Custom field deleted.', 'pixelgrade_care' ),
				4  => esc_html__( 'Gallery updated.', 'pixelgrade_care' ),
				/* translators: %s: date and time of the revision */
				5  => isset( $_GET['revision'] ) ? sprintf( esc_html__( 'Gallery restored to revision from %s', 'pixelgrade_care' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6  => sprintf( __( 'Gallery published. <a href="%s">View project</a>', 'pixelgrade_care' ), esc_url( get_permalink( $post->ID ) ) ),
				7  => esc_html__( 'Gallery saved.', 'pixelgrade_care' ),
				8  => sprintf( __( 'Gallery submitted. <a target="_blank" href="%s">Preview gallery</a>', 'pixelgrade_care' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ),
				9  => sprintf( __( 'Gallery scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview gallery</a>', 'pixelgrade_care' ),
					// translators: Publish box date format, see https://php.net/date
					date_i18n( __( 'M j, Y @ G:i', 'pixelgrade_care' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post->ID ) ) ),
				10 => sprintf( __( 'Gallery item draft updated. <a target="_blank" href="%s">Preview gallery</a>', 'pixelgrade_care' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ),
			];

			return $messages;
		}

		/**
		 * Change ‘Title’ column label.
		 * Add Featured Image column.
		 *
		 * @since 1.12.0
		 */
		public function edit_admin_columns( $columns ) {
			// Change 'Title' to 'Gallery'
			$columns['title'] = __( 'Gallery', 'pixelgrade_care' );
			if ( current_theme_supports( 'post-thumbnails' ) ) {
				// add featured image before 'Gallery'
				$columns = array_slice( $columns, 0, 1, true ) + [ 'thumbnail' => '' ] + array_slice( $columns, 1, null, true );
			}

			return $columns;
		}

		/**
		 * Add featured image to column.
		 *
		 * @since 1.12.0
		 */
		public function image_column( $column, $post_id ) {
			global $post;
			switch ( $column ) {
				case 'thumbnail':
					echo get_the_post_thumbnail( $post_id, 'pixcare-gallery-admin-thumb' );
					break;
			}
		}

		/**
		 * Adjust image column width.
		 *
		 * @since 1.12.0
		 */
		public function enqueue_admin_styles( $hook ) {
			$screen = get_current_screen();

			if ( 'edit.php' == $hook && self::CUSTOM_POST_TYPE == $screen->post_type && current_theme_supports( 'post-thumbnails' ) ) {
				wp_add_inline_style( 'wp-admin', '.manage-column.column-thumbnail { width: 50px; } @media screen and (max-width: 360px) { .column-thumbnail{ display:none; } }' );
			}
		}

		/**
		 * Follow CPT reading setting on CPT archive and taxonomy pages.
		 *
		 * @since 1.12.0
		 */
		public function query_reading_setting( $query ) {
			if ( ( ! is_admin() || ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX ) )
			     && $query->is_main_query()
			     && ( $query->is_post_type_archive( self::CUSTOM_POST_TYPE )
			          || $query->is_tax( self::CUSTOM_TAXONOMY_TYPE )
			          || $query->is_tax( self::CUSTOM_TAXONOMY_TAG ) )
			) {
				$query->set( 'posts_per_page', get_option( self::OPTION_READING_SETTING, '10' ) );
			}
		}

		/**
		 * Displays the gallery type that a project belongs to.
		 *
		 * @since 1.12.0
		 * @static
		 * @return string
		 */
		public static function get_gallery_type( $post_id ) {
			$project_types = get_the_terms( $post_id, self::CUSTOM_TAXONOMY_TYPE );

			// If no types, return empty string
			if ( empty( $project_types ) || is_wp_error( $project_types ) ) {
				return '';
			}

			$html  = '<div class="gallery-types"><span>' . __( 'Types', 'pixelgrade_care' ) . ':</span>';
			$types = [];
			// Loop thorugh all the types
			foreach ( $project_types as $project_type ) {
				$project_type_link = get_term_link( $project_type, self::CUSTOM_TAXONOMY_TYPE );

				if ( is_wp_error( $project_type_link ) ) {
					return $project_type_link;
				}

				$types[] = '<a href="' . esc_url( $project_type_link ) . '" rel="tag">' . esc_html( $project_type->name ) . '</a>';
			}
			$html .= ' ' . implode( ', ', $types );
			$html .= '</div>';

			return $html;
		}

		/**
		 * Displays the gallery tags that a project belongs to.
		 *
		 * @since 1.12.0
		 * @static
		 * @return string
		 */
		public static function get_gallery_tags( $post_id ) {
			$project_tags = get_the_terms( $post_id, self::CUSTOM_TAXONOMY_TAG );

			// If no tags, return empty string.
			if ( empty( $project_tags ) || is_wp_error( $project_tags ) ) {
				return '';
			}

			$html = '<div class="gallery-tags"><span>' . __( 'Tags', 'pixelgrade_care' ) . ':</span>';
			$tags = [];
			// Loop thorugh all the tags.
			foreach ( $project_tags as $project_tag ) {
				$project_tag_link = get_term_link( $project_tag, self::CUSTOM_TAXONOMY_TYPE );

				if ( is_wp_error( $project_tag_link ) ) {
					return $project_tag_link;
				}

				$tags[] = '<a href="' . esc_url( $project_tag_link ) . '" rel="tag">' . esc_html( $project_tag->name ) . '</a>';
			}
			$html .= ' ' . implode( ', ', $tags );
			$html .= '</div>';

			return $html;
		}

		/**
		 * Displays the author of the current gallery.
		 *
		 * @since 1.12.0
		 * @static
		 * @return string
		 */
		public static function get_gallery_author() {
			$html = '<div class="gallery-author">';
			/* translators: %1$s is link to author posts, %2$s is author display name */
			$html .= sprintf( __( '<span>Author:</span> <a href="%1$s">%2$s</a>', 'pixelgrade_care' ),
				esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
				esc_html( get_the_author() )
			);
			$html .= '</div>';

			return $html;
		}

		/**
		 * Display the featured image if it's available
		 *
		 * @since 1.12.0
		 * @static
		 *
		 * @return string
		 */
		public static function get_gallery_thumbnail_link( $post_id ) {
			if ( has_post_thumbnail( $post_id ) ) {
				/**
				 * Change the Gallery thumbnail size.
				 *
				 * @module custom-content-types
				 *
				 * @since  3.4.0
				 *
				 * @param string|array $var Either a registered size keyword or size array.
				 */
				return '<a class="gallery-featured-image" href="' . esc_url( get_permalink( $post_id ) ) . '">' . get_the_post_thumbnail( $post_id, apply_filters( 'pixelgrade_care/gallery_thumbnail_size', 'large' ) ) . '</a>';
			}

			return '';
		}
	}
endif;
