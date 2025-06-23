<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add theme fonts to the font field options list
add_filter( 'customify_theme_fonts', 'listable_add_customify_theme_fonts' );

// Add fonts section and options to the Customify config
add_filter( 'customify_filter_fields', 'listable_add_fonts_section_to_customify_config', 60, 1 );

function listable_add_customify_theme_fonts( $fonts ) {

	$fonts['Hanken'] = array(
		'family'   => 'Hanken',
		'src'      => get_template_directory_uri() . '/assets/fonts/hanken/stylesheet.css',
		'variants' => array( '400', '700' )
	);

	$fonts['Reforma1969'] = array(
		'family'   => 'Reforma1969',
		'src'      => '//pxgcdn.com/fonts/reforma1969/stylesheet.css',
		'variants' => array( 300, 500, 700 ),
	);

	$fonts['Reforma2018'] = array(
		'family'   => 'Reforma2018',
		'src'      => '//pxgcdn.com/fonts/reforma2018/stylesheet.css',
		'variants' => array( 300, 500, 700, 900 ),
	);

	$fonts['League Spartan'] = array(
		'family'   => 'League Spartan',
		'src'      => '//pxgcdn.com/fonts/league-spartan/stylesheet.css',
		'variants' => array()
	);

	$fonts['HK Grotesk'] = array(
		'family'   => 'HK Grotesk',
		'src'      => '//pxgcdn.com/fonts/hk-grotesk/stylesheet.css',
		'variants' => array()
	);

	$fonts['YoungSerif'] = array(
		'family'   => 'YoungSerif',
		'src'      => '//pxgcdn.com/fonts/young-serif/stylesheet.css',
		'variants' => array()
	);

	$fonts['Billy Ohio'] = array(
		'family'   => 'Billy Ohio',
		'src'      => '//pxgcdn.com/fonts/billy-ohio/stylesheet.css',
		'variants' => array()
	);

	$fonts['Mellony Dry Brush'] = array(
		'family'   => 'Mellony Dry Brush',
		'src'      => '//pxgcdn.com/fonts/mellony-dry-brush/stylesheet.css',
		'variants' => array()
	);

	$fonts['Jandys Dua'] = array(
		'family'   => 'Jandys Dua',
		'src'      => '//pxgcdn.com/fonts/jandys-dua/stylesheet.css',
		'variants' => array()
	);

	$fonts['Nermola Script'] = array(
		'family'   => 'Nermola Script',
		'src'      => '//pxgcdn.com/fonts/nermola-script/stylesheet.css',
		'variants' => array()
	);

	return $fonts;
}

function listable_add_fonts_section_to_customify_config( $config ) {

	$font_size_config = array(
		'min'  => 10,
		'max'  => 48,
		'step' => 0.1,
		'unit' => 'px',
	);

	$line_height_config = array(
		'min' => 0.8,
		'max' => 2,
		'step' => 0.05,
		'unit' => 'em',
	);

	$letter_spacing_config = array(
		'min'  => -0.125,
		'max'  => 1.25,
		'step' => 0.125,
		'unit' => 'em',
	);

	$fields_config = array(
		'font-size'      => $font_size_config,
		'line-height'    => $line_height_config,
		'letter-spacing' => $letter_spacing_config,
		'text-align'     => false,
	);

	// Recommended Fonts List
	// Headings
	$recommended_headings_fonts = array(
		'Playfair Display',
		'Oswald',
		'Lato',
		'Open Sans',
		'Exo',
		'PT Sans',
		'Ubuntu',
		'Vollkorn',
		'Lora',
		'Arvo',
		'Josefin Slab',
		'Crete Round',
		'Kreon',
		'Bubblegum Sans',
		'The Girl Next Door',
		'Pacifico',
		'Handlee',
		'Satify',
		'Pompiere'
	);

	// Body
	$recommended_body_fonts = array(
		'Source Sans Pro',
		'Lato',
		'Open Sans',
		'PT Sans',
		'Cabin',
		'Gentium Book Basic',
		'PT Serif',
		'Droid Serif'
	);

	if ( empty( $config['sections'] ) ) {
		$config['sections'] = array();
	}

	$config['sections']['fonts'] = array(
		'title'   => '&#x1f4dd; ' . esc_html__( 'Fonts', 'listable' ),
		'options' => array(
			'site_title_font'     => array(
				'type'        => 'font',
				'label'       => esc_html__( 'Site Title Font', 'listable' ),
				'selector'    => '.site-header .site-title',
				'default'     => array(
					'font-family'    => 'Hanken',
					'font-size'      => 24,
					'font-weight'    => 700,
					'text-transform' => 'none',
					'letter-spacing' => 0,
				),
				'recommended' => $recommended_headings_fonts,
				'fields'      => array_merge( $fields_config, array(
					'line-height'    => false,
					'text-transform' => true,
				) )
			),
			'navigation_font'     => array(
				'type'        => 'font',
				'label'       => esc_html__( 'Navigation Font', 'listable' ),
				'selector'    => '
						.search-suggestions-menu li a,
						.primary-menu > ul a, 
						.secondary-menu > ul a, 
						ul.primary-menu a, 
						ul.secondary-menu a',
				'default'     => array(
					'font-family'    => 'Hanken',
					'font-size'      => 15,
					'font-weight'    => 400,
					'letter-spacing' => 0,
					'text-transform' => 'capitalize',
				),
				'recommended' => $recommended_body_fonts,
				'fields'      => array_merge( $fields_config, array(
					'line-height'    => false,
					'text-transform' => true,
				) )
			),
			'body_font'           => array(
				'type'        => 'font',
				'label'       => esc_html__( 'Body Font', 'listable' ),
				'selector'    => '
						input,
						textarea,
						label,
						html,
						.entry-content blockquote cite,
						.comment-content blockquote cite,
						.card--feature .card__content,
						.rating,
						.widget--footer .search-form .search-field,
						.featured-label,
						.package__description,
						.footer-text-area,
						.widget_listing_comments h3.pixrating_title,
						.chosen-container-multi .chosen-choices,
						.chosen-container-single .chosen-single,
						.product .product__price,
						.product .product__tag,
						.entry-content_wrapper .widget-area--post .widget_title,
						.entry-content_wrapper .widget-area--post .widget-title,
						.widgets_area .product_list_widget li .product__price',
				'default'     => array(
					'font-family' => 'Source Sans Pro',
				),
				'recommended' => $recommended_body_fonts,
				'fields'      => array(
					'font-weight' => false,
				),
			),
			'page_titles_font'    => array(
				'type'        => 'font',
				'label'       => esc_html__( 'Headings', 'listable' ),
				'selector'    => '
						.entry-title, 
						.archive-title,
						h1, h2, h3,
						.card--feature .card__title,
						.results,
						.page-title,
						.search_location input,
						.package__title, .package__price,
						.package__subscription-period,
						h2.comments-title,
						.page-add-listing fieldset:first-child label,
						.product-content .price',
				'default'     => array(
					'font-family'    => 'Hanken',
					'font-weight'    => 700,
				),
				'recommended' => $recommended_headings_fonts,
			),
			'page_subtitles_font' => array(
				'type'        => 'font',
				'label'       => esc_html__( 'Page Subtitles', 'listable' ),
				'selector'    => '
						.intro,
						.description,
						.tabs.wc-tabs,
						.job-manager-form fieldset .job-manager-uploaded-files .job-manager-uploaded-file .job-manager-uploaded-file-preview a,
						.widget_subtitle--frontpage,
						.category-list a,
						.single:not(.single-job_listing) .entry-subtitle,
						.blog .entry-subtitle,
						.page .entry-subtitle,
						.single-job_listing .entry-subtitle',
				'default'     => array(
					'font-family'    => 'Hanken',
					'font-weight'    => 400,
				),
				'recommended' => $recommended_headings_fonts
			),
			'card_title_font'     => array(
				'type'        => 'font',
				'label'       => esc_html__( 'Card Title Font', 'listable' ),
				'selector'    => '
						.card__title.card__title, 
						ul.categories--widget .category-count,
						ul.categories--widget .category-text',
				'default'     => array(
					'font-family'    => 'Hanken',
					'font-size'      => 24,
					'font-weight'    => 700,
					'letter-spacing' => 0,
					'text-transform' => 'none',
				),
				'recommended' => $recommended_body_fonts,
				'fields'      => array_merge( $fields_config, array(
					'line-height'    => false,
					'text-transform' => true,
				) )
			),
			'card_font'           => array(
				'type'             => 'font',
				'label'            => esc_html__( 'Card Font', 'listable' ),
				'selector'         => '
						.card,
                        .widgets_area .product_list_widget li',
				'load_all_weights' => false,
				'default'          => array(
					'font-family' => 'Hanken',
					'font-weight' => 400,
				),
				'recommended'      => $recommended_body_fonts
			),
			'meta_font'           => array(
				'type'        => 'font',
				'label'       => esc_html__( 'Meta & Forms', 'listable' ),
				'selector'    => '
						.single:not(.single-job_listing) .entry-meta,
						.page .entry-meta,
						.single:not(.single-job_listing) div.sd-social.sd-social > div.sd-content.sd-content ul li > a,
						.page div.sd-social.sd-social > div.sd-content.sd-content ul li > a,
						.search_jobs select,
						.search-form .search-field,
						.search_jobs--frontpage .chosen-container .chosen-results li,
						.search_jobs--frontpage .chosen-container-multi .chosen-choices li.search-field input[type=text],
						.search_jobs--frontpage .chosen-container-single .chosen-single,
						.search_jobs .chosen-container .chosen-results li,
						.search_jobs .chosen-container-multi .chosen-choices li.search-field input[type=text],
						.search_jobs .chosen-container-single .chosen-single,
						.search_jobs--frontpage-facetwp,
						.search_jobs--frontpage-facetwp input,
						.search_jobs--frontpage-facetwp select,
						.search_jobs--frontpage-facetwp .facetwp-filter-title,
						.header-facet-wrapper .facetwp-facet input,
						.header-facet-wrapper .facetwp-facet select,
						.header-facet-wrapper .facetwp-facet label,
						.active-tag,
						.chosen-results,
						.job_filters .search_jobs div.search_location input,
						.search-suggestions-menu li a,
						.page-template-front_page .search-form .search-submit,
						.btn,
						input[type="submit"],
						button[type="submit"],
						.woocommerce-account:not(.logged-in) .woocommerce form.login input[type="submit"],
						.woocommerce .button,
						.search_jobs--frontpage #search_location,
						.select2-container--default .select2-selection--single .select2-selection__rendered,
						#page .nf-form-cont button, 
                        #page .nf-form-cont input[type=button], 
                        #page .nf-form-cont input[type=submit], 
                        #page .wpforms-form input[type=submit], 
                        #page .wpforms-form button[type=submit], 
                        #page .wpforms-form .wpforms-page-button',
				'default'     => array(
					'font-family' => 'Hanken',
					'font-weight' => 400,
				),
				'recommended' => $recommended_body_fonts
			),
		)
	);

	return $config;
}
