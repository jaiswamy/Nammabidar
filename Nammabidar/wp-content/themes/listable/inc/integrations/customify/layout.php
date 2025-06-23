<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add fonts section and options to the Customify config
add_filter( 'customify_filter_fields', 'listable_add_layout_section_to_customify_config', 60, 1 );

function listable_add_layout_section_to_customify_config( $config ) {

	if ( empty( $config['sections'] ) ) {
		$config['sections'] = array();
	}

	$config['sections']['layout'] = array(
		'title'    => '&#x1f4bb; ' . esc_html__( 'Layout', 'listable' ),
		'options' => array(
			// Header
			'header_layout_title' => array(
				'type' => 'html',
				'html' => '<span id="section-header-layout" class="separator section label large">' . esc_html__( 'Header', 'listable' ) . '</span>',
			),
			'header_logo_height' => array(
				'type'        => 'range',
				'label'       => esc_html__( 'Logo Height', 'listable' ),
				'desc'        => esc_html__( 'This setting only applies to images', 'listable' ),
				'default'     => 32,
				'live'        => true,
				'input_attrs' => array(
					'min'          => 20,
					'max'          => 100,
					'step'         => 1,
					'data-preview' => true
				),
				'css'         => array(
					array(
						'property'        => 'max-height',
						'selector'        => '.site-branding img',
						'unit'            => 'px',
						'callback_filter' => 'listable_update_header_height'
					)
				)
			),
			'header_vertical_margins' => array(
				'type'        => 'range',
				'label'       => esc_html__( 'Header Vertical Margins', 'listable' ),
				'default'     => 0,
				'live'        => true,
				'input_attrs' => array(
					'min'          => 0,
					'max'          => 100,
					'step'         => 1,
					'data-preview' => true
				),
				'css'         => array(
					array(
						'property' => 'padding-top',
						'selector' => '.site-header',
						'unit'     => 'px',
						'media'    => ' screen and (min-width: 900px)',
					),
					array(
						'property'        => 'padding-bottom',
						'selector'        => '.site-header',
						'unit'            => 'px',
						'media'           => 'screen and (min-width: 900px) ',
						'callback_filter' => 'listable_update_header_height'
					)
				)
			),
			'navigation_menu_items_spacing' => array(
				'type'        => 'range',
				'label'       => esc_html__( 'Menu Items Spacing', 'listable' ),
				'default'     => 24,
				'live'        => true,
				'input_attrs' => array(
					'min'          => 12,
					'max'          => 40,
					'step'         => 1,
					'data-preview' => true
				),
				'css'         => array(
					array(
						'property' => 'padding-left',
						'selector' => 'ul.primary-menu > .menu-item > a, .header--transparent ul.primary-menu > .menu-item > a',
						'unit'     => 'px',
						'media'    => ' screen and (min-width: 900px)'
					),
					array(
						'property' => 'padding-right',
						'selector' => 'ul.primary-menu > .menu-item > a',
						'unit'     => 'px',
						'media'    => 'screen and (min-width: 900px) '
					)
				)
			),
			// Frontpage
			'frontpage_layout_title' => array(
				'type' => 'html',
				'html' => '<span id="section-frontpage-layout" class="separator section label large">' . esc_html__( 'Frontpage', 'listable' ) . '</span>',
			),
			'frontpage_content_width'   => array(
				'type'        => 'range',
				'label'       => esc_html__( 'Container Width', 'listable' ),
				// 'desc'        => __( 'Set the width of the container.', 'listable' ),
				'live'        => true,
				'default'     => 1100,
				'input_attrs' => array(
					'min'          => 600,
					'max'          => 2700,
					'step'         => 10,
					'data-preview' => true
				),
				'css'         => array(
					array(
						'property' => 'max-width',
						'selector' => '.section-wrap',
						'unit'     => 'px',
					)
				)
			),
			'sections_vertical_margins' => array(
				'type'        => 'range',
				'label'       => esc_html__( 'Sections Vertical Margins', 'listable' ),
				'live'        => true,
				'default'     => 90,
				'input_attrs' => array(
					'min'          => 30,
					'max'          => 120,
					'step'         => 6,
					'data-preview' => true
				),
				'css'         => array(
					array(
						'property' => 'padding-top',
						'selector' => '.front-page-section',
						'unit'     => 'px',
						'media'    => ' only screen and (min-width: 900px)',
					),
					array(
						'property' => 'padding-bottom',
						'selector' => '.front-page-section',
						'unit'     => 'px',
						'media'    => 'only screen and (min-width: 900px) ',
					)
				)
			),
			// Content
			'content_layout_title' => array(
				'type' => 'html',
				'html' => '<span id="section-content-layout" class="separator section label large">' . esc_html__( 'Content', 'listable' ) . '</span>',
			),
			'content_width' => array(
				'type'        => 'range',
				'label'       => esc_html__( 'Container Width', 'listable' ),
				// 'desc'        => __( 'Set the width of the container.', 'listable' ),
				'live'        => true,
				'default'     => 760,
				'input_attrs' => array(
					'min'          => 600,
					'max'          => 2700,
					'step'         => 10,
					'data-preview' => true
				),
				'css'         => array(
					array(
						'property' => 'max-width',
						'selector' => '
						    .single:not(.single-job_listing) .header-content,
						    .single:not(.single-job_listing) .entry-content,
						    .single:not(.single-job_listing) .entry-footer,
						    .single:not(.single-job_listing) .comments-area,
						    .single:not(.single-job_listing) .post-navigation,
						    .page .header-content,
						    body:not(.single):not(.woocommerce-checkout):not(.page-template-full_width) .entry-content,
						    .page .entry-footer,
						    .page .comments-area,
						    .page .post-navigation,
						    .secondary-menu,
						    .error404 .header-content, .error404 .entry-content,
						    .search-no-results .header-content, .search-no-results .entry-content,
						    .upsells, .related',
						'unit'     => 'px',
					),
				)
			),
		),
	);

	return $config;

}