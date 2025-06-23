<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add colors section and options to the Customify config
add_filter( 'customify_filter_fields', 'listable_add_customify_style_presets', 12, 1 );

function listable_add_customify_style_presets( $options ) {

	if ( ! isset( $options['sections']['style_presets'] ) ) {
		$options['sections']['style_presets'] = array();
	}

	// The section might be already defined, thus we merge, not replace the entire section config.
	$options['sections']['style_presets'] = array_replace_recursive( $options['sections']['style_presets'], array(
		'title'   => '&#x1f3ad; ' . esc_html__( 'Style Presets', 'listable' ),
		'options' => array(
			'theme_style' => array(
				'type'         => 'preset',
				'label'        => esc_html__( 'Select a style:', 'listable' ),
				'desc'         => esc_html__( 'Conveniently change the design of your site with built-in style presets. Easy as pie.', 'listable' ),
				'default'      => 'listable',
				'choices_type' => 'awesome',
				'choices'      => array(

					// Default Preset
					'listable' => array(
						'label'   => esc_html__( 'Listable', 'listable' ),
						'preview' => array(
							'color-text'       => '#ffffff',
							'background-card'  => '#ff4d55',
							'background-label' => '#f13d46',
							'font-main'        => 'Hanken',
							'font-alt'         => 'Source Sans Pro',
						),
						'options' => array(

							// COLORS
							// Site Header
							'header_transparent'          => true,
							'header_background_color'     => '#ffffff',
							'site_title_color'            => '#484848',
							'search_color'                => '#484848',
							'nav_link_color'              => '#919191',
							'nav_active_color'            => '#FF4D55',
							'nav_button_color'            => '#EBEBEB',

							// Main Content
							'content_background'          => '#ffffff',
							'page_background'             => '#F9F9F9',
							'page_titles_color'           => '#484848',
							'page_subtitles_color'        => '#919191',
							'text_color'                  => '#484848',
							'buttons_color'               => '#FF4D55',

							// Cards
							'cards_background'            => '#ffffff',
							'cards_radius'                => '8',
							'thumbs_radius'               => '4',
							'cards_title_color'           => '#FF4D55',
							'cards_text_color'            => '#ababab',
							'cards_icon_color'            => '#FF4D5A',
							'cards_icon_border_color'     => '#FF4D5A',
							'cards_icon_background_color' => '#FFFFFF',
							'pin_background_color'        => '#FFFFFF',
							'pin_icon_color'              => '#FF4D5A',

							// Pre Footer
							'prefooter_background'        => '#2F2929',
							'prefooter_text_color'        => '#FFFFFF',

							// Footer
							'footer_background'           => '#261E1E',
							'footer_text_color'           => '#ADADB2',
							'footer_credits_color'        => '#706C6C',

							// Other Colors
							'accent_color'                => '#FF4D58',
							'fields_color'                => '#919191',
							'micro_color'                 => '#ABABAB',

							// FONTS
							'site_title_font'             => array(
								'font-family'    => 'Hanken',
								'font-size'      => 24,
								'font-weight'    => 700,
								'text-transform' => 'none',
								'letter-spacing' => 0,
							),
							'navigation_font'             => array(
								'font-family'    => 'Hanken',
								'font-size'      => 15,
								'font-weight'    => 400,
								'letter-spacing' => 0,
								'text-transform' => 'capitalize',
							),
							'body_font'                   => array(
								'font-family' => 'Source Sans Pro',
							),
							'page_titles_font'            => array(
								'font-family' => 'Hanken',
								'font-weight' => 700,
							),
							'page_subtitles_font'         => array(
								'font-family' => 'Hanken',
								'font-weight' => 400,
							),
							'card_title_font'             => array(
								'font-family'    => 'Hanken',
								'font-size'      => 24,
								'font-weight'    => 700,
								'letter-spacing' => 0,
								'text-transform' => 'none',
							),
							'card_font'                   => array(
								'font-family' => 'Hanken',
								'font-weight' => 400,
							),
							'meta_font'                   => array(
								'font-family' => 'Hanken',
								'font-weight' => 400,
							),
						)
					),

					// Royal Preset
					'royal'     => array(
						'label'   => esc_html__( 'Royal', 'listable' ),
						'preview' => array(
							'color-text'       => '#ffffff',
							'background-card'  => '#10324e',
							'background-label' => '#bdae6a',
							'font-main'        => 'Playfair Display',
							'font-alt'         => 'Source Sans Pro',
						),
						'options' => array(

							// COLORS
							// Site Header
							'header_transparent'          => true,
							'header_background_color'     => '#ffffff',
							'site_title_color'            => '#10324e',
							'search_color'                => '#484848',
							'nav_link_color'              => '#86898c',
							'nav_active_color'            => '#10324e',
							'nav_button_color'            => '#ebebeb',

							// Main Content
							'content_background'          => '#ffffff',
							'page_background'             => '#f9f9f9',
							'page_titles_color'           => '#10324e',
							'page_subtitles_color'        => '#86898c',
							'text_color'                  => '#10324e',
							'buttons_color'               => '#bdae6a',

							// Cards
							'cards_background'            => '#ffffff',
							'cards_radius'                => '4',
							'thumbs_radius'               => '16',
							'cards_title_color'           => '#bdae6a',
							'cards_text_color'            => '#86898c',
							'cards_icon_color'            => '#ffffff',
							'cards_icon_border_color'     => '#bdae6a',
							'cards_icon_background_color' => '#bdae6a',
							'pin_background_color'        => '#ffffff',
							'pin_icon_color'              => '#bdae6a',

							// Pre Footer
							'prefooter_background'        => '#1c486d',
							'prefooter_text_color'        => '#ffffff',

							// Footer
							'footer_background'           => '#10324e',
							'footer_text_color'           => '#d9dae0',
							'footer_credits_color'        => '#b0b5b7',

							// Other Colors
							'accent_color'                => '#bdae6a',
							'fields_color'                => '#919191',
							'micro_color'                 => '#ababab',

							// FONTS
							'site_title_font'             => array(
								'font-family'    => 'Playfair Display',
								'font-size'      => 24,
								'font-weight'    => 700,
								'text-transform' => 'none',
								'letter-spacing' => 0,
							),
							'navigation_font'             => array(
								'font-family'    => 'Source Sans Pro',
								'font-size'      => 15,
								'font-weight'    => 200,
								'letter-spacing' => 0,
								'text-transform' => 'capitalize',
							),
							'body_font'                   => array(
								'font-family' => 'Source Sans Pro',
							),
							'page_titles_font'            => array(
								'font-family' => 'Playfair Display',
								'font-weight' => 700,
							),
							'page_subtitles_font'         => array(
								'font-family' => 'Source Sans Pro',
								'font-weight' => 300,
							),
							'card_title_font'             => array(
								'font-family'    => 'Playfair Display',
								'font-size'      => 24,
								'font-weight'    => 'regular',
								'letter-spacing' => 0,
								'text-transform' => 'none',
							),
							'card_font'                   => array(
								'font-family' => 'Source Sans Pro',
								'font-weight' => 300,
							),
							'meta_font'                   => array(
								'font-family' => 'Source Sans Pro',
								'font-weight' => 300,
							),
						)
					),

					// Silkberry Preset
					'silkberry' => array(
						'label'   => esc_html__( 'Silkberry', 'listable' ),
						'preview' => array(
							'color-text'       => '#ffffff',
							'background-card'  => '#3D3235',
							'background-label' => '#FCC5BE',
							'font-main'        => 'Yeseva One',
							'font-alt'         => 'Source Sans Pro',
						),
						'options' => array(

							// COLORS
							// Site Header
							'header_transparent'          => true,
							'header_background_color'     => '#161314',
							'site_title_color'            => '#fcc5be',
							'search_color'                => '#484848',
							'nav_link_color'              => '#b2b2b2',
							'nav_active_color'            => '#fcc5be',
							'nav_button_color'            => '#9e9e9e',

							// Main Content
							'content_background'          => '#3d3235',
							'page_background'             => '#2b2427',
							'page_titles_color'           => '#fcc5be',
							'page_subtitles_color'        => '#bf8989',
							'text_color'                  => '#f9f9f9',
							'buttons_color'               => '#fcc5be',

							// Cards
							'cards_background'            => '#312a2d',
							'cards_radius'                => '24',
							'thumbs_radius'               => '22',
							'cards_title_color'           => '#fcc5be',
							'cards_text_color'            => '#ffffff',
							'cards_icon_color'            => '#3c3133',
							'cards_icon_border_color'     => '#fcc5be',
							'cards_icon_background_color' => '#fcc5be',
							'pin_background_color'        => '#ffffff',
							'pin_icon_color'              => '#ffadad',

							// Pre Footer
							'prefooter_background'        => '#2F2929',
							'prefooter_text_color'        => '#ffffff',

							// Footer
							'footer_background'           => '#261E1E',
							'footer_text_color'           => '#868889',
							'footer_credits_color'        => '#a3a09b',

							// Other Colors
							'accent_color'                => '#fcc5be',
							'fields_color'                => '#6d6d6d',
							'micro_color'                 => '#dddddd',

							// FONTS
							'site_title_font'             => array(
								'font-family'    => 'Libre Baskerville',
								'font-size'      => 24,
								'font-weight'    => 700,
								'text-transform' => 'none',
								'letter-spacing' => 0,
							),
							'navigation_font'             => array(
								'font-family'    => 'Raleway',
								'font-size'      => 15,
								'font-weight'    => 300,
								'letter-spacing' => 0,
								'text-transform' => 'none',
							),
							'navigation_font'             => array(
								'font_family'       => 'Raleway',
								'selected_variants' => '300'
							),
							'body_font'                   => array(
								'font-family' => 'Raleway',
							),
							'page_titles_font'            => array(
								'font-family' => 'Libre Baskerville',
								'font-weight' => 'regular',
							),
							'page_subtitles_font'         => array(
								'font-family' => 'Raleway',
								'font-weight' => 300,
							),
							'card_title_font'             => array(
								'font-family'    => 'Raleway',
								'font-size'      => 28,
								'font-weight'    => 300,
								'letter-spacing' => 0,
								'text-transform' => 'none',
							),
							'card_font'                   => array(
								'font-family' => 'Raleway',
								'font-weight' => 300,
							),
							'meta_font'                   => array(
								'font-family' => 'Raleway',
								'font-weight' => 300,
							),
						)
					),

					// Orangina Preset
					'Orangina'  => array(
						'label'   => esc_html__( 'Orangina', 'listable' ),
						'preview' => array(
							'color-text'       => '#ffffff',
							'background-card'  => '#01a7ba',
							'background-label' => '#e58500',
							'font-main'        => 'Delius',
							'font-alt'         => 'Lato',
						),
						'options' => array(

							// COLORS
							// Site Header
							'header_transparent'          => true,
							'header_background_color'     => '#01a7ba',
							'site_title_color'            => '#ffffff',
							'search_color'                => '#ffffff',
							'nav_link_color'              => '#ffffff',
							'nav_active_color'            => '#3d3d3d',
							'nav_button_color'            => '#3d3d3d',

							// Main Content
							'content_background'          => '#ffffff',
							'page_background'             => '#ffffff',
							'page_titles_color'           => '#01a7ba',
							'page_subtitles_color'        => '#86898c',
							'text_color'                  => '#3d3d3d',
							'buttons_color'               => '#f79f07',

							// Cards
							'cards_background'            => '#ffffff',
							'cards_radius'                => '20',
							'thumbs_radius'               => '6',
							'cards_title_color'           => '#f79f07',
							'cards_text_color'            => '#9b9b9b',
							'cards_icon_color'            => '#ffffff',
							'cards_icon_border_color'     => '#01a7ba',
							'cards_icon_background_color' => '#01a7ba',
							'pin_background_color'        => '#ffffff',
							'pin_icon_color'              => '#01a7ba',

							// Pre Footer
							'prefooter_background'        => '#01a7ba',
							'prefooter_text_color'        => '#ffffff',

							// Footer
							'footer_background'           => '#f79f07',
							'footer_text_color'           => '#ffffff',
							'footer_credits_color'        => '#000000',

							// Other Colors
							'accent_color'                => '#f79f07',
							'fields_color'                => '#848484',
							'micro_color'                 => '#000000',

							// FONTS
							'site_title_font'             => array(
								'font-family'    => 'Delius',
								'font-size'      => 24,
								'font-weight'    => 700,
								'text-transform' => 'none',
								'letter-spacing' => 0,
							),
							'navigation_font'             => array(
								'font-family'    => 'Raleway',
								'font-size'      => 15,
								'font-weight'    => 300,
								'letter-spacing' => 0,
								'text-transform' => 'none',
							),
							'body_font'                   => array(
								'font-family' => 'Raleway',
								'font-weight' => 'regular',
							),
							'page_titles_font'            => array(
								'font-family' => 'Delius',
								'font-weight' => 'regular',
							),
							'page_subtitles_font'         => array(
								'font-family' => 'Raleway',
								'font-weight' => 300,
							),
							'card_title_font'             => array(
								'font-family'    => 'Delius',
								'font-size'      => 28,
								'font-weight'    => 'regular',
								'letter-spacing' => 0,
								'text-transform' => 'none',
							),
							'card_font'                   => array(
								'font-family' => 'Raleway',
								'font-weight' => 'regular',
							),
							'meta_font'                   => array(
								'font-family' => 'Raleway',
								'font-weight' => 300,
							),
						)
					),

					// Jolly Preset
					'Jolly'     => array(
						'label'   => esc_html__( 'Jolly', 'listable' ),
						'preview' => array(
							'color-text'       => '#ffffff',
							'background-card'  => '#ea6f4d',
							'background-label' => '#ea481c',
							'font-main'        => 'Flamenco',
							'font-alt'         => 'Lato',
						),
						'options' => array(

							// COLORS
							// Site Header
							'header_transparent'          => true,
							'header_background_color'     => '#ea6f4d',
							'site_title_color'            => '#ffffff',
							'search_color'                => '#474747',
							'nav_link_color'              => '#ffffff',
							'nav_active_color'            => '#9b3030',
							'nav_button_color'            => '#000000',

							// Main Content
							'content_background'          => '#ffffff',
							'page_background'             => '#ffffff',
							'page_titles_color'           => '#ea6f4d',
							'page_subtitles_color'        => '#86898c',
							'text_color'                  => '#494949',
							'buttons_color'               => '#ea6f4d',

							// Cards
							'cards_background'            => '#ffffff',
							'cards_radius'                => '6',
							'thumbs_radius'               => '6',
							'cards_title_color'           => '#ea481c',
							'cards_text_color'            => '#666666',
							'cards_icon_color'            => '#ffffff',
							'cards_icon_border_color'     => '#ea6f4d',
							'cards_icon_background_color' => '#ea6f4d',
							'pin_background_color'        => '#ffffff',
							'pin_icon_color'              => '#ea481c',

							// Pre Footer
							'prefooter_background'        => '#ea481c',
							'prefooter_text_color'        => '#ffffff',

							// Footer
							'footer_background'           => '#ea6f4d',
							'footer_text_color'           => '#ffffff',
							'footer_credits_color'        => '#ffbaba',

							// Other Colors
							'accent_color'                => '#ea481c',
							'fields_color'                => '#898989',
							'micro_color'                 => '#777777',

							// FONTS
							'site_title_font'             => array(
								'font-family'    => 'Flamenco',
								'font-size'      => 24,
								'font-weight'    => 700,
								'text-transform' => 'none',
								'letter-spacing' => 0,
							),
							'navigation_font'             => array(
								'font-family'    => 'Lato',
								'font-size'      => 15,
								'font-weight'    => 300,
								'letter-spacing' => 0,
								'text-transform' => 'none',
							),
							'body_font'                   => array(
								'font-family' => 'Lato',
								'font-weight' => 300,
							),
							'page_titles_font'            => array(
								'font-family' => 'Flamenco',
								'font-weight' => 'regular',
							),
							'page_subtitles_font'         => array(
								'font-family' => 'Lato',
								'font-weight' => 300,
							),
							'card_title_font'             => array(
								'font-family'    => 'Flamenco',
								'font-size'      => 28,
								'font-weight'    => 'regular',
								'letter-spacing' => 0,
								'text-transform' => 'none',
							),
							'card_font'                   => array(
								'font-family' => 'Lato',
								'font-weight' => 300,
							),
							'meta_font'                   => array(
								'font-family' => 'Lato',
								'font-weight' => 300,
							),
						)
					),

					// Navy Preset
					'Navy'      => array(
						'label'   => esc_html__( 'Navy', 'listable' ),
						'preview' => array(
							'color-text'       => '#ffffff',
							'background-card'  => '#001f63',
							'background-label' => '#3a3a3a',
							'font-main'        => 'Vidaloka',
							'font-alt'         => 'Lato',
						),
						'options' => array(

							// COLORS
							// Site Header
							'header_transparent'          => true,
							'header_background_color'     => '#ffffff',
							'site_title_color'            => '#001f63',
							'search_color'                => '#3a3a3a',
							'nav_link_color'              => '#001f63',
							'nav_active_color'            => '#4a5463',
							'nav_button_color'            => '#000000',

							// Main Content
							'content_background'          => '#ffffff',
							'page_background'             => '#ffffff',
							'page_titles_color'           => '#161400',
							'page_subtitles_color'        => '#86898c',
							'text_color'                  => '#3d3d3d',
							'buttons_color'               => '#000144',

							// Cards
							'cards_background'            => '#ffffff',
							'cards_radius'                => '6',
							'thumbs_radius'               => '6',
							'cards_title_color'           => '#001f63',
							'cards_text_color'            => '#666666',
							'cards_icon_color'            => '#001f63',
							'cards_icon_border_color'     => '#001f63',
							'cards_icon_background_color' => '#ffffff',
							'pin_background_color'        => '#ffffff',
							'pin_icon_color'              => '#001f63',

							// Pre Footer
							'prefooter_background'        => '#002847',
							'prefooter_text_color'        => '#ffffff',

							// Footer
							'footer_background'           => '#00133f',
							'footer_text_color'           => '#ffffff',
							'footer_credits_color'        => '#c1c1c1',

							// Other Colors
							'accent_color'                => '#001f63',
							'fields_color'                => '#848484',
							'micro_color'                 => '#000000',

							// FONTS
							'site_title_font'             => array(
								'font-family'    => 'Vidaloka',
								'font-size'      => 24,
								'font-weight'    => 700,
								'text-transform' => 'none',
								'letter-spacing' => 0,
							),
							'navigation_font'             => array(
								'font-family'    => 'Lato',
								'font-size'      => 15,
								'font-weight'    => 'regular',
								'letter-spacing' => 0,
								'text-transform' => 'capitalize',
							),
							'body_font'                   => array(
								'font-family' => 'Lato',
							),
							'page_titles_font'            => array(
								'font-family' => 'Vidaloka',
								'font-weight' => 'regular',
							),
							'page_subtitles_font'         => array(
								'font-family' => 'Lato',
								'font-weight' => 'regular',
							),
							'card_title_font'             => array(
								'font-family'    => 'Vidaloka',
								'font-size'      => 24,
								'font-weight'    => 700,
								'letter-spacing' => 0,
								'text-transform' => 'none',
							),
							'card_font'                   => array(
								'font-family' => 'Lato',
								'font-weight' => 'regular',
							),
							'meta_font'                   => array(
								'font-family' => 'Lato',
								'font-weight' => 'regular',
							),
						)
					),

					// Grass Preset
					'Grass'     => array(
						'label'   => __( 'Grass', 'listable' ),
						'preview' => array(
							'color-text'       => '#ffffff',
							'background-card'  => '#64a97b',
							'background-label' => '#213847',
							'font-main'        => 'Copse',
							'font-alt'         => 'Raleway',
						),
						'options' => array(

							// COLORS
							// Site Header
							'header_transparent'          => true,
							'header_background_color'     => '#ffffff',
							'site_title_color'            => '#3a5465',
							'search_color'                => '#231e1c',
							'nav_link_color'              => '#090c11',
							'nav_active_color'            => '#64a97b',
							'nav_button_color'            => '#3d3931',

							// Main Content
							'content_background'          => '#ffffff',
							'page_background'             => '#ffffff',
							'page_titles_color'           => '#2f4859',
							'page_subtitles_color'        => '#86898c',
							'text_color'                  => '#3d3d3d',
							'buttons_color'               => '#64a97b',

							// Cards
							'cards_background'            => '#ffffff',
							'cards_radius'                => '36',
							'thumbs_radius'               => '36',
							'cards_title_color'           => '#3a5465',
							'cards_text_color'            => '#474747',
							'cards_icon_color'            => '#ffffff',
							'cards_icon_border_color'     => '#64a97b',
							'cards_icon_background_color' => '#64a97b',
							'pin_background_color'        => '#ffffff',
							'pin_icon_color'              => '#64a97b',

							// Pre Footer
							'prefooter_background'        => '#64a97b',
							'prefooter_text_color'        => '#ffffff',

							// Footer
							'footer_background'           => '#3a5465',
							'footer_text_color'           => '#ffffff',
							'footer_credits_color'        => '#0c0c0c',

							// Other Colors
							'accent_color'                => '#001f63',
							'fields_color'                => '#848484',
							'micro_color'                 => '#000000',

							// FONTS
							'site_title_font'             => array(
								'font-family'    => 'Copse',
								'font-size'      => 24,
								'font-weight'    => 700,
								'text-transform' => 'none',
								'letter-spacing' => 0,
							),
							'navigation_font'             => array(
								'font-family'    => 'Raleway',
								'font-size'      => 15,
								'font-weight'    => 300,
								'letter-spacing' => 0,
								'text-transform' => 'none',
							),
							'body_font'                   => array(
								'font-family' => 'Raleway',
							),
							'page_titles_font'            => array(
								'font-family' => 'Copse',
								'font-weight' => 700,
							),
							'page_subtitles_font'         => array(
								'font-family' => 'Raleway',
								'font-weight' => 300,
							),
							'card_title_font'             => array(
								'font-family'    => 'Montserrat',
								'font-size'      => 24,
								'font-weight'    => 700,
								'letter-spacing' => 0,
								'text-transform' => 'none',
							),
							'card_font'                   => array(
								'font-family' => 'Raleway',
								'font-weight' => 400,
							),
							'meta_font'                   => array(
								'font-family' => 'Raleway',
								'font-weight' => 400,
							),
						)
					)
				)
			),
		)
	) );

	return $options;
}
