<?php
/**
 * This is the class that handles the overall logic for handling block patterns.
 *
 * @see         https://pixelgrade.com
 * @author      Pixelgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PixelgradeCare_Block_Patterns' ) ) :

	class PixelgradeCare_Block_Patterns {

		/**
		 * Holds the only instance of this class.
		 * @since   1.12.0
		 * @var     null|PixelgradeCare_Block_Patterns
		 * @access  protected
		 */
		protected static $_instance = null;

		/**
		 * The main plugin object (the parent).
		 * @since     1.12.0
		 * @var     PixelgradeCare
		 * @access    public
		 */
		public $parent = null;

		/**
		 * Block patterns to be processed and maybe registered.
		 *
		 * @access  protected
		 * @since   1.12.0
		 * @var     array|null
		 */
		protected $block_patterns = null;

		/**
		 * Block patterns categories to be processed and maybe registered.
		 *
		 * @access  protected
		 * @since   1.12.0
		 * @var     array|null
		 */
		protected $block_patterns_categories = null;

		/**
		 * Constructor.
		 *
		 * @since 1.12.0
		 *
		 * @param PixelgradeCare $parent
		 * @param array          $args
		 */
		protected function __construct( $parent, $args = [] ) {
			$this->parent = $parent;

			$this->init( $args );
		}

		/**
		 * Initialize the block patterns manager.
		 *
		 * @since  1.12.0
		 *
		 * @param array   $args                      {
		 *
		 * @type    array $block_patterns            Array of array of block patterns with keys the block pattern name, including namespace.
		 * @type    array $block_patterns_categories Array of array block patterns categories with keys the block pattern category name/slug.
		 *  }
		 */
		public function init( $args ) {
			if ( isset( $args['block_patterns'] ) && is_array( $args['block_patterns'] ) ) {
				$this->block_patterns = $args['block_patterns'];
			}

			if ( isset( $args['block_patterns_categories'] ) && is_array( $args['block_patterns_categories'] ) ) {
				$this->block_patterns_categories = $args['block_patterns_categories'];
			}

			// Add hooks, but only if we are not uninstalling the plugin.
			if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
				$this->add_hooks();
			}
		}

		/**
		 * Initiate our hooks.
		 *
		 * @since 1.12.0
		 * @return void
		 */
		public function add_hooks() {
			// Add action to load remote block patterns.
			add_action( 'init', [ $this, 'maybe_load_remote_block_patterns' ], 7 );

			// Add actions to register block patterns categories.
			add_action( 'init', [ $this, 'register_block_patterns_categories' ], 8 );

			// Add actions to register block patterns.
			add_action( 'init', [ $this, 'register_block_patterns' ], 30 );
		}

		public function maybe_load_remote_block_patterns() {
			// By default, we want Nova Blocks to be active. But we let others have a say as well.
			if ( ! apply_filters( 'pixelgrade_care/use_remote_block_patterns', function_exists( 'novablocks_plugin_setup' ) ) ) {
				return;
			}

			if ( $this->block_patterns === null || $this->block_patterns_categories === null ) {
				$remote_block_patterns_config = $this->get_remote_block_patterns_config();

				if ( empty( $this->block_patterns ) ) {
					$this->block_patterns = $this->convert_remote_block_patterns_config( $remote_block_patterns_config );
				}

				if ( empty( $this->block_patterns_categories ) ) {
					$this->block_patterns_categories = $this->extract_categories_from_block_patterns_config( $remote_block_patterns_config );
				}
			}
		}

		/**
		 * Registers block patterns categories found in all block patterns.
		 *
		 * @since 1.12.0
		 * @return void
		 */
		public function register_block_patterns_categories() {
			$block_pattern_categories = $this->block_patterns_categories;
			if ( empty( $block_pattern_categories ) ) {
				$block_pattern_categories = [];
			}

			/**
			 * Filters the Pixelgrade Care block patterns categories.
			 *
			 * @param array[] $block_pattern_categories {
			 *                                          An associative array of block patterns categories, keyed by the category name.
			 *
			 * @type array[]  $properties               {
			 *         An array of block patterns category properties.
			 *
			 * @type string   $label                    A human-readable label for the block patterns category.
			 *     }
			 * }
			 */
			$block_pattern_categories = apply_filters( 'pixelgrade_care/block_patterns_categories', $block_pattern_categories );

			foreach ( $block_pattern_categories as $block_pattern_category ) {
				if ( empty( $block_pattern_category['name'] ) || empty( $block_pattern_category['properties'] ) ) {
					continue;
				}

				if ( ! WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( $block_pattern_category['name'] ) ) {
					register_block_pattern_category(
						$block_pattern_category['name'],
						$block_pattern_category['properties']
					);
				}
			}
		}

		/**
		 * Registers block patterns.
		 *
		 * @since 1.12.0
		 * @return void
		 */
		public function register_block_patterns() {
			$block_patterns = $this->block_patterns;
			if ( empty( $block_patterns ) ) {
				$block_patterns = [];
			}

			/**
			 * Filters the Pixelgrade Care block patterns.
			 *
			 * @param array  $block_patterns {
			 *                               List of block patterns keyed by their name, including namespace.
			 *
			 * @type array[] $properties     {
			 *         An array of block pattern properties.
			 *
			 * @type string  $name           The block pattern name, including namespace.
			 * @type array   $properties     The block pattern properties. @see WP_Block_Patterns_Registry::register()
			 *                               for supported properties.
			 *     }
			 * }
			 */
			$block_patterns = apply_filters( 'pixelgrade_care/block_patterns', $block_patterns );

			// If an empty or falsy value was received, bail.
			if ( empty( $block_patterns ) || ! is_array( $block_patterns ) ) {
				return;
			}

			foreach ( $block_patterns as $block_pattern ) {
				if ( empty( $block_pattern['name'] ) || empty( $block_pattern['properties'] ) ) {
					continue;
				}

				register_block_pattern(
					$block_pattern['name'],
					$block_pattern['properties']
				);
			}
		}

		/**
		 * Get the remote block patterns configuration.
		 *
		 * @since 1.12.0
		 *
		 * @param bool $skip_cache Optional. Whether to use the cached config or fetch a new one.
		 *
		 * @return array
		 */
		protected function get_remote_block_patterns_config( $skip_cache = false ) {
			// Make sure that the Remote Block Patterns class is loaded.
			require_once 'class-pixelgrade_care-remote-block-patterns.php';

			// Get the block patterns data.
			$remote_data = PixelgradeCare_Remote_Block_Patterns::instance()->get( $skip_cache );
			if ( false === $remote_data || empty( $remote_data['items'] ) ) {
				$block_patterns_config = [];
			} else {
				$block_patterns_config = $remote_data['items'];
			}

			return apply_filters( 'pixelgrade_care_novablocks_get_remote_block_patterns_config', $block_patterns_config );
		}

		/**
		 * Identify all block patterns categories from the config and return them in a standard, ready-to-register format.
		 *
		 * @since   1.12.0
		 *
		 * @param array $block_patterns_config
		 *
		 * @return array The block patterns categories keyed by their name.
		 */
		protected function extract_categories_from_block_patterns_config( $block_patterns_config ) {
			$block_patterns_categories = [];
			if ( empty( $block_patterns_config ) || ! is_array( $block_patterns_config ) ) {
				return $block_patterns_categories;
			}

			foreach ( $block_patterns_config as $block_pattern_config ) {
				if ( empty( $block_pattern_config['categories'] ) || ! is_array( $block_pattern_config['categories'] ) ) {
					continue;
				}

				foreach ( $block_pattern_config['categories'] as $category ) {
					if ( ! is_array( $category ) || empty( $category['slug'] ) || empty( $category['name'] ) ) {
						continue;
					}

					$block_patterns_categories[ $category['slug'] ] = [
						'name'       => esc_html( $category['slug'] ),
						'properties' => [
							'label' => esc_html( $category['name'] ),
						],
					];
				}
			}

			// Sort the categories by their slug.
			ksort( $block_patterns_categories );

			return $block_patterns_categories;
		}

		/**
		 * Identify all block patterns from the config and return them in a standard, ready-to-register format.
		 *
		 * @since   1.12.0
		 *
		 * @param array $block_patterns_config
		 *
		 * @return array The block patterns configurations keyed by their name, including namespace.
		 */
		protected function convert_remote_block_patterns_config( $block_patterns_config ) {
			$block_patterns = [];
			if ( empty( $block_patterns_config ) || ! is_array( $block_patterns_config ) ) {
				return $block_patterns;
			}

			foreach ( $block_patterns_config as $block_pattern_config ) {
				// Make sure we have something to work with.
				if ( ! is_array( $block_pattern_config )
				     || empty( $block_pattern_config['name'] )
				     || empty( $block_pattern_config['properties'] ) ) {

					continue;
				}

				$properties = $block_pattern_config['properties'];

				// Parse the properties.
				$properties = $this->parse_block_pattern_properties( $properties, $block_pattern_config );
				if ( empty( $properties ) ) {
					continue;
				}

				// If categories were not provided, we will construct a list of categories from the categories terms list.
				if ( empty( $properties['categories'] ) && ! empty( $block_pattern_config['categories'] ) ) {
					$properties['categories'] = [];
					foreach ( $block_pattern_config['categories'] as $category ) {
						if ( ! empty( $category['slug'] ) ) {
							$properties['categories'][] = strip_tags( $category['slug'] );
						} else if ( ! empty( $category['name'] ) ) {
							$properties['categories'][] = sanitize_title_with_dashes( $category['name'] );
						}
					}

					$properties['categories'] = array_unique( $properties['categories'] );
				}

				// If keywords were not provided, we will construct a list of keywords from the tags terms list.
				if ( empty( $properties['keywords'] ) && ! empty( $block_pattern_config['tags'] ) ) {
					$properties['keywords'] = [];
					foreach ( $block_pattern_config['tags'] as $tag ) {
						if ( ! empty( $tag['name'] ) ) {
							$properties['keywords'][] = strip_tags( $tag['name'] );
						} else if ( ! empty( $tag['slug'] ) ) {
							$properties['keywords'][] = sanitize_title( $tag['slug'] );
						}
					}

					$properties['keywords'] = array_unique( $properties['keywords'] );
				}

				$block_patterns[ $block_pattern_config['name'] ] = [
					'name'       => $block_pattern_config['name'],
					'properties' => $properties,
				];
			}

			return $block_patterns;
		}

		/**
		 * Parse block pattern properties.
		 *
		 * @access  protected
		 * @since   1.12.0
		 *
		 * @param array $properties
		 * @param array $block_pattern_config
		 *
		 * @return  array|false Block pattern properties with defaults validated and set as necessary, or false if values not validated.
		 */
		protected function parse_block_pattern_properties( $properties, $block_pattern_config = [] ) {
			if ( empty( $properties ) || ! is_array( $properties ) ) {
				return false;
			}

			// Set the block pattern properties using defaults where necessary.
			$defaults = [
				'title'       => '',
				'blockTypes'  => [],
				'content'     => '',
				'description' => '',
				'categories'  => [],
				'keywords'    => [],
			];

			$properties = wp_parse_args( $properties, $defaults );

			// If the viewportWidth is empty or a negative number, delete the entry to make sure we don't mess things up in the editor.
			if ( isset( $properties['viewportWidth'] ) && ( empty( $properties['viewportWidth'] ) || intval( $properties['viewportWidth'] ) < 0 ) ) {
				unset( $properties['viewportWidth'] );
			} else {
				$properties['viewportWidth'] = absint( $properties['viewportWidth'] );
			}

			// Sanitize and validate all values.
			foreach ( $properties as $key => $value ) {

				switch ( $key ) {
					case 'title' :
					case 'description':
						if ( ! is_string( $value ) ) {
							return false;
						}

						$properties[ $key ] = wp_kses( wp_unslash( $value ), wp_kses_allowed_html( 'data' ) );
						break;
					case 'blockTypes' :
						if ( ! is_string( $value ) && ! is_array( $value ) ) {
							return false;
						}

						$properties[ $key ] = array_unique( wp_parse_list( $value ) );
						break;
					case 'content' :
						if ( ! is_string( $value ) ) {
							return false;
						}

						if ( empty( trim( $value ) ) ) {
							return false;
						}

						$properties[ $key ] = $this->parse_content_tags( $value );
						break;
					case 'keywords' :
					case 'categories':
						if ( ! is_string( $value ) && ! is_array( $value ) ) {
							return false;
						}

						if ( is_string( $value ) && ! empty( $value ) ) {
							$properties[ $key ] = explode( ',', $value );
							$properties[ $key ] = array_unique( array_map( 'trim', $properties[ $key ] ) );
						}
						break;
					default:
						break;
				}
			}

			return $properties;
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
			$content = apply_filters( 'pixelgrade_care/block_patterns/parse_content_tags:before', $content );

			// Now we will replace all the supported tags with their value
			// %year%
			$content = str_replace( '%year%', date( 'Y' ), $content );

			// %site-title% or %site_title%
			$content = str_replace( '%site-title%', get_bloginfo( 'name' ), $content );
			$content = str_replace( '%site_title%', get_bloginfo( 'name' ), $content );

			// %site-tagline%, %site_tagline%, %site-description% or %site_description%
			$content = str_replace( '%site-tagline%', get_bloginfo( 'description' ), $content );
			$content = str_replace( '%site_tagline%', get_bloginfo( 'description' ), $content );
			$content = str_replace( '%site-description%', get_bloginfo( 'description' ), $content );
			$content = str_replace( '%site_description%', get_bloginfo( 'description' ), $content );

			// %active_theme%
			$content = str_replace( '%active_theme%', PixelgradeCare_Admin::get_original_theme_name(), $content );

			// %footer_copyright%
			$content = str_replace( '%footer_copyright%', $this->get_footer_copyright(), $content );

			// %footer_credits%
			$content = str_replace( '%footer_credits%', $this->get_footer_credits(), $content );

			/*
			 * URLs.
			 */
			// %home_url%
			$content = str_replace( '%home_url%', home_url(), $content );

			// Allow others to alter the content after we did our work
			return apply_filters( 'pixelgrade_care/block_patterns/parse_content_tags:after', $content, $original_content );
		}

		/**
		 * Get the footer copyright.
		 *
		 * @return string
		 */
		protected function get_footer_copyright() {
			$output = '';

			if ( function_exists( 'anima_footer_get_copyright_content' ) ) {
				$output .= anima_footer_get_copyright_content();
			} else {
				/* translators: The footer copyright text. 1: The current year, 2: The site name.  */
				$output .= sprintf( esc_html__( '&copy; %1$s %2$s.', 'pixelgrade_care' ), date( 'Y' ), get_bloginfo( 'name' ) );
			}

			return $output;
		}

		/**
		 * Get the footer Pixelgrade credits HTML.
		 *
		 * @return string
		 */
		protected function get_footer_credits() {
			$output = '';

			$hide_credits = false;
			if ( function_exists( 'pixelgrade_option' ) ) {
				$hide_credits = pixelgrade_option( 'footer_hide_credits', false );
			}
			if ( empty( $hide_credits ) ) {
				$output .= '<span class="c-footer__credits">' . sprintf( esc_html__( 'Theme: %1$s by %2$s.', 'pixelgrade_care' ), esc_html( pixelgrade_get_original_theme_name() ), '<a href="https://pixelgrade.com/?utm_source=anima-clients&utm_medium=footer&utm_campaign=anima" title="' . esc_html__( 'The Pixelgrade Website', '__theme_txtd' ) . '" rel="nofollow">Pixelgrade</a>' ) . '</span>';
			}

			return $output;
		}

		/**
		 * Main PixelgradeCare_Block_Patterns Instance
		 *
		 * Ensures only one instance of PixelgradeCare_Block_Patterns is loaded or can be loaded.
		 *
		 * @since  1.12.0
		 * @static
		 *
		 * @param PixelgradeCare $parent The main plugin object (the parent).
		 * @param array $args The arguments to initialize the block patterns manager.
		 *
		 * @return PixelgradeCare_Block_Patterns Main PixelgradeCare_Block_Patterns instance
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
endif;
