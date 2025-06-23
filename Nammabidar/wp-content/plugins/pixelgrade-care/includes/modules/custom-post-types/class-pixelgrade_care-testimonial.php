<?php
/**
 * This is the class that handles the overall logic for registering the Testimonial custom post type.
 *
 * Inspired by Jetpack's Jetpack_Testimonial class.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PixelgradeCare_Testimonial' ) ) :
	require_once 'class-pixelgrade_care-cpt.php';

	class PixelgradeCare_Testimonial extends PixelgradeCare_CPT {
		const THEME_SUPPORTS = 'pixcare_testimonial';

		const CUSTOM_POST_TYPE = 'testimonial';

		const OPTION_NAME = 'pixcare_enable_testimonial_cpt';
		const OPTION_READING_SETTING = 'pixcare_testimonial_posts_per_page';
		const OPTION_ARCHIVE_PAGE_SETTING = 'pixcare_testimonial_archive_page';

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
			$this->OPTION_TITLE = __( 'Testimonials', 'pixelgrade_care' );

			// Fire up the standard initialization and only continue if that returns true.
			if ( ! parent::init() ) {
				return false;
			}

			// Admin Customization.
			add_filter( 'enter_title_here', [ $this, 'change_default_title' ] );
			add_filter( sprintf( 'manage_%s_posts_columns', self::CUSTOM_POST_TYPE ), [
				$this,
				'edit_title_column_label',
			] );
			add_filter( 'post_updated_messages', [ $this, 'updated_messages' ] );

			return true;
		}

		/**
		 * HTML code to display a checkbox true/false option for the CPT setting.
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
					<?php esc_html_e( 'Enable Testimonials for this site.', 'pixelgrade_care' ); ?>
					<a target="_blank"
					   href="#"><?php esc_html_e( 'Learn More', 'pixelgrade_care' ); ?></a>
				</label>
			<?php }

			if ( $this->site_supports_custom_post_type() ) {
				printf( '<p><label for="%1$s">%2$s</label></p>',
					esc_attr( self::OPTION_READING_SETTING ),
					/* translators: %1$s is replaced with an input field for numbers */
					sprintf( __( 'Testimonial pages display at most %1$s testimonials.', 'pixelgrade_care' ),
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
		public function register_post_types() {
			if ( post_type_exists( self::CUSTOM_POST_TYPE ) ) {
				return;
			}

			register_post_type( self::CUSTOM_POST_TYPE, [
				'description'     => __( 'Customer Testimonials', 'pixelgrade_care' ),
				'labels'          => [
					'name'                  => esc_html__( 'Testimonials', 'pixelgrade_care' ),
					'singular_name'         => esc_html__( 'Testimonial', 'pixelgrade_care' ),
					'menu_name'             => esc_html__( 'Testimonials', 'pixelgrade_care' ),
					'all_items'             => esc_html__( 'All Testimonials', 'pixelgrade_care' ),
					'add_new'               => esc_html__( 'Add New', 'pixelgrade_care' ),
					'add_new_item'          => esc_html__( 'Add New Testimonial', 'pixelgrade_care' ),
					'edit_item'             => esc_html__( 'Edit Testimonial', 'pixelgrade_care' ),
					'new_item'              => esc_html__( 'New Testimonial', 'pixelgrade_care' ),
					'view_item'             => esc_html__( 'View Testimonial', 'pixelgrade_care' ),
					'search_items'          => esc_html__( 'Search Testimonials', 'pixelgrade_care' ),
					'not_found'             => esc_html__( 'No Testimonials found', 'pixelgrade_care' ),
					'not_found_in_trash'    => esc_html__( 'No Testimonials found in Trash', 'pixelgrade_care' ),
					'filter_items_list'     => esc_html__( 'Filter Testimonials list', 'pixelgrade_care' ),
					'items_list_navigation' => esc_html__( 'Testimonial list navigation', 'pixelgrade_care' ),
					'items_list'            => esc_html__( 'Testimonials list', 'pixelgrade_care' ),
				],
				'supports'        => [
					'title',
					'editor',
					'thumbnail',
					'page-attributes',
					'revisions',
					'excerpt',
					'newspack_blocks',
				],
				'rewrite'         => [
					'slug'       => 'testimonial',
					'with_front' => false,
					'feeds'      => false,
					'pages'      => true,
				],
				'public'          => true,
				'show_ui'         => true,
				'menu_position'   => 24, // below Pages, Portfolio and Galleries
				'menu_icon'       => 'dashicons-testimonial',
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'has_archive'     => true,
				'query_var'       => 'testimonial',
				'show_in_rest'    => true,
			] );
		}

		/**
		 * Update messages for the Testimonial admin.
		 *
		 * @since 1.12.0
		 */
		public function updated_messages( $messages ) {
			global $post;

			$messages[ self::CUSTOM_POST_TYPE ] = [
				0  => '', // Unused. Messages start at index 1.
				1  => sprintf( __( 'Testimonial updated. <a href="%s">View testimonial</a>', 'pixelgrade_care' ), esc_url( get_permalink( $post->ID ) ) ),
				2  => esc_html__( 'Custom field updated.', 'pixelgrade_care' ),
				3  => esc_html__( 'Custom field deleted.', 'pixelgrade_care' ),
				4  => esc_html__( 'Testimonial updated.', 'pixelgrade_care' ),
				/* translators: %s: date and time of the revision */
				5  => isset( $_GET['revision'] ) ? sprintf( esc_html__( 'Testimonial restored to revision from %s', 'pixelgrade_care' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6  => sprintf( __( 'Testimonial published. <a href="%s">View testimonial</a>', 'pixelgrade_care' ), esc_url( get_permalink( $post->ID ) ) ),
				7  => esc_html__( 'Testimonial saved.', 'pixelgrade_care' ),
				8  => sprintf( __( 'Testimonial submitted. <a target="_blank" href="%s">Preview testimonial</a>', 'pixelgrade_care' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ),
				9  => sprintf( __( 'Testimonial scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview testimonial</a>', 'pixelgrade_care' ),
					// translators: Publish box date format, see https://php.net/date
					date_i18n( __( 'M j, Y @ G:i', 'pixelgrade_care' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post->ID ) ) ),
				10 => sprintf( __( 'Testimonial draft updated. <a target="_blank" href="%s">Preview testimonial</a>', 'pixelgrade_care' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ),
			];

			return $messages;
		}

		/**
		 * Change ‘Enter Title Here’ text for the Testimonial.
		 *
		 * @since 1.12.0
		 */
		public function change_default_title( $title ) {
			if ( self::CUSTOM_POST_TYPE == get_post_type() ) {
				$title = esc_html__( "Enter the customer's name here", 'pixelgrade_care' );
			}

			return $title;
		}

		/**
		 * Change ‘Title’ column label on all Testimonials page.
		 *
		 * @since 1.12.0
		 */
		public function edit_title_column_label( $columns ) {
			$columns['title'] = esc_html__( 'Customer Name', 'pixelgrade_care' );

			return $columns;
		}

		/**
		 * Display the featured image if it's available
		 *
		 * @since 1.12.0
		 * @return string
		 */
		public static function get_testimonial_thumbnail_link( $post_id ) {
			if ( has_post_thumbnail( $post_id ) ) {
				/**
				 * Change the thumbnail size for the Testimonial CPT.
				 *
				 * @module custom-content-types
				 *
				 * @since  3.4.0
				 *
				 * @param string|array $var Either a registered size keyword or size array.
				 */
				return '<a class="testimonial-featured-image" href="' . esc_url( get_permalink( $post_id ) ) . '">' . get_the_post_thumbnail( $post_id, apply_filters( 'jetpack_testimonial_thumbnail_size', 'thumbnail' ) ) . '</a>';
			}

			return '';
		}
	}
endif;
