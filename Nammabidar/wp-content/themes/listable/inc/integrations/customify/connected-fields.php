<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add colors section and options to the Customify config
add_filter( 'customify_filter_fields', 'listable_add_customify_connected_fields', 12, 1 );

function listable_add_customify_connected_fields( $options ) {
	// If the theme hasn't declared support for style manager, bail.
	if ( ! current_theme_supports( 'customizer_style_manager' ) ) {
		return $options;
	}

	if ( ! isset( $options['sections']['style_manager_section'] ) ) {
		$options['sections']['style_manager_section'] = array();
	}

	// The section might be already defined, thus we merge, not replace the entire section config.
	$options['sections']['style_manager_section'] = array_replace_recursive( $options['sections']['style_manager_section'], array(
		'options' => array(
			// Font Palettes Assignment.
			'sm_font_palette' => array(
				'default' => 'softable',
			),
			'sm_font_primary'    => array(
				'connected_fields' => array(
					'site_title_font' => array(
						'font_size' => 24,
					),
					'page_titles_font' => array(
					),
					'card_title_font' => array(
						'font_size' => 24,
					),
				),
			),
			'sm_font_secondary'  => array(
				'connected_fields' => array(
					'navigation_font' => array(
						'font_size' => 15,
					),
					'page_subtitles_font' => array(
					),
					'card_font' => array(
					),
				),
			),
			'sm_font_body'       => array(
				'connected_fields' => array(
					'body_font' => array(
					),
				),
			),
			'sm_color_primary' => array(
				'default' => SM_COLOR_PRIMARY,
				'connected_fields' => array(

					// always colored
					'cards_title_color',            // #FF4D55
					'buttons_color',                // #FF4D55

					// low
					'pin_icon_color',               // #FF4D5A
					'accent_color',                 // #FF4D58
					'cards_icon_color',             // #FF4D5A
					'cards_icon_border_color',      // #FF4D5A
					'nav_active_color',             // #FF4D55
				),
			),
			'sm_color_secondary' => array(
				'default' => SM_COLOR_SECONDARY,
			),
			'sm_color_tertiary' => array(
				'default' => SM_COLOR_TERTIARY,
			),
			'sm_dark_primary' => array(
				'default' => SM_DARK_PRIMARY,
				'connected_fields' => array(
					// high / striking
					'site_title_color',             // #484848
					'page_titles_color',            // #484848

					// always dark
					'search_color',                 // #484848
					'footer_background',            // #261E1E
					'text_color',                   // #484848
				),
			),
			'sm_dark_secondary' => array(
				'default' => SM_DARK_SECONDARY,
				'connected_fields' => array(
					'prefooter_background',         // #2F2929
				),
			),
			'sm_dark_tertiary' => array(
				'default' => SM_DARK_TERTIARY,
				'connected_fields' => array(
					// medium
					'micro_color',                  // #ABABAB
					'page_subtitles_color',         // #919191
					// striking
					'nav_link_color',               // #919191
					// always dark
					'fields_color',                 // #919191
					'cards_text_color',             // #ABABAB
				),
			),
			'sm_light_primary' => array(
				'default' => SM_LIGHT_PRIMARY,
				'connected_fields' => array(
					'header_background_color',      // #FFFFFF
					'content_background',           // #FFFFFF
					'cards_background',             // #FFFFFF
					'cards_icon_background_color',  // #FFFFFF
					'pin_background_color',         // #FFFFFF
					'prefooter_text_color',         // #FFFFFF
				),
			),
			'sm_light_secondary' => array(
				'default' => SM_LIGHT_SECONDARY,
				'connected_fields' => array(
					'nav_button_color',             // #EBEBEB
					'page_background',              // #F9F9F9
					'footer_credits_color',         // #706C6C
					'footer_text_color',            // #ADADB2
				),
			),
			'sm_light_tertiary' => array(
				'default' => SM_LIGHT_TERTIARY,
			),
		),
	) );

	return $options;
}
