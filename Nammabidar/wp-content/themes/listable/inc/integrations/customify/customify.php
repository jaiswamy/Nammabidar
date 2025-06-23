<?php
/**
 * Listable Options Config
 *
 * @package Listable
 * @since Listable 1.0
 */

add_filter( 'customify_filter_fields', 'listable_add_customify_options' );

// Color Constants
define( 'SM_COLOR_PRIMARY', '#FF4D58' );
define( 'SM_COLOR_SECONDARY', '#28acab' );
define( 'SM_COLOR_TERTIARY', '#8fcc80' );
define( 'SM_DARK_PRIMARY', '#484848' );
define( 'SM_DARK_SECONDARY', '#2F2929' );
define( 'SM_DARK_TERTIARY', '#919191' );
define( 'SM_LIGHT_PRIMARY', '#FFFFFF' );
define( 'SM_LIGHT_SECONDARY', '#F9F9F9' );
define( 'SM_LIGHT_TERTIARY', '#F9F9F9' );

require_once __DIR__ . '/extras.php';
require_once __DIR__ . '/connected-fields.php';
require_once __DIR__ . '/font-palettes.php';
require_once __DIR__ . '/style-presets.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/colors.php';
require_once __DIR__ . '/fonts.php';

/**
 * Hook into the Customify's fields and settings
 *
 * @param $options array - Contains the plugin's options array right before they are used, so edit with care
 *
 * @return mixed
 */

if ( ! function_exists( 'listable_add_customify_options' ) ) {

	function listable_add_customify_options( $options ) {

		$options['opt-name'] = 'listable_options';

		// keep this empty now
		$options['sections'] = array();

		$options['panels']['theme_options'] = array(
			'title'    => '&#x1f506; ' . esc_html__( 'Theme Options', 'listable' ),
			'sections' => array(
				'general'     => array(
					'title'   => esc_html__( 'General', 'listable' ),
					'options' => array(
						'footer_copyright' => array(
							'type'              => 'textarea',
							'label'             => esc_html__( 'Footer Copyright Text', 'listable' ),
							'desc' => esc_html__( 'The copyright text which should appear in the footer area.', 'listable' ),
							'default'           => esc_html__( 'Copyright &copy; %year% Company Inc.   &bull;   Address  &bull;   Tel: 42-898-4363', 'listable' ),
							'sanitize_callback' => 'wp_kses_post',
							'live'              => array( '.site-info .site-copyright-area' )
						),
					)
				),
				'map_options' => array(
					'title'   => esc_html__( 'Map Options', 'listable' ),
					'options' => array(
						'map_default_location' => array(
							'type'    => 'text',
							'label'   => esc_html__( 'Default Map Location', 'listable' ),
							'default' => '51.5073509,-0.12775829999998223',
							'live'    => true,
							'desc'    => '<p>' . sprintf( esc_html__( 'Default GPS coordinates (latitude, longitude). Get them from %s.', 'listable' ), '<a href="https://www.gps-coordinates.net/" target="_blank">' . esc_html__( 'here', 'listable' ) . '</a>' ) . '</p>',
						),
						'mapbox_token'        => array(
							'type'    => 'text',
							'label'   => esc_html__( 'Mapbox Integration (optional)', 'listable' ),
							'default' => '',
							'desc'    => '<p>' . sprintf( esc_html__( 'We are offering integration with the %s service, so you can have better looking and highly performance maps.', 'listable' ), '<a href="https://www.mapbox.com/" target="_blank">Mapbox</a>' ) . '</p>' .
								'<p>' . sprintf( esc_html__( '%sGet a FREE token%s and paste it above. If there is nothing added, we will fallback to the Google Maps service.', 'listable' ), '<a href="https://docs.mapbox.com/help/how-mapbox-works/access-tokens/" target="_blank">', '</a>' ) . '</p>',
						),
						'mapbox_style'        => array(
							'type'    => 'radio_image',
							'label'   => esc_html__( 'Mapbox Style', 'listable' ),
							'default' => 'mapbox.streets-basic',
							'choices' => listable_get_mapbox_choices(),
							'desc'    => esc_html__( 'Custom styles works only if you have set a valid Mapbox API token in the field above.', 'listable' ),
						),
					)
				),
				'custom_js'   => array(
					'title'   => esc_html__( 'Custom JavaScript', 'listable' ),
					'options' => array(
						'custom_js'        => array(
							'type'        => 'ace_editor',
							'label'       => esc_html__( 'Header', 'listable' ),
							'desc'        => esc_html__( 'Easily add Custom Javascript code. This code will be loaded in the <head> section.', 'listable' ),
							'editor_type' => 'javascript',
						),
						'custom_js_footer' => array(
							'type'        => 'ace_editor',
							'label'       => esc_html__( 'Footer', 'listable' ),
							'desc'        => esc_html__( 'You can paste here your Google Analytics tracking code (or for what matters any tracking code) and we will put it on every page.', 'listable' ),
							'editor_type' => 'javascript',
						),
					)
				),
			)
		);

		/**
		 * Register a second logo option which will be moved in the title_tagline section
		 */
		$options['sections']['to_be_removed'] = array(
			'options' => array(
				'logo_invert' => array(
					'type'    => 'media',
					'label'   => esc_html__( 'Logo while on Transparent Hero Area', 'listable' ),
					'desc'    => esc_html__( 'Replace the default logo on the Front Page Hero.', 'listable' ),
					'show_on' => array( 'header_transparent' ),
				),
			)
		);

		// $options['panels'] = array();
		return $options;
	}
}
