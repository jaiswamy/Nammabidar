<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prepend theme color palette to the default color palettes list
add_filter( 'customify_get_color_palettes', 'listable_filter_color_palettes' );

// Add fonts section and options to the Customify config
add_filter( 'customify_filter_fields', 'listable_add_colors_section_to_customify_config', 60, 1 );

function listable_add_colors_section_to_customify_config( $config ) {

	if ( empty( $config['sections'] ) ) {
		$config['sections'] = array();
	}

	$config['sections']['colors'] = array(
		'title'   => '&#x1f3a8; ' . esc_html__( 'Colors', 'listable' ),
		'options' => array(
			// site header
			'site_header_colors_title' => array(
				'type' => 'html',
				'html' => '<span id="section-site-header-colors" class="separator section label large">' . esc_html__( 'Site Header', 'listable' ) . '</span>',
			),
			'header_transparent'          => array(
				'type'    => 'checkbox',
				'default' => true,
				'label'   => esc_html__( 'Transparent on Front Page Hero', 'listable' ),
			),
			'header_background_color'     => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Header Background Color', 'listable' ),
				'live'    => true,
				'default' => SM_LIGHT_PRIMARY,
				'css'     => array(
					array(
						'property' => 'background-color',
						'selector' => '.bar, .bar--fixed, .site-header,
							.primary-menu ul .children, ul.primary-menu .sub-menu, .search-suggestions-menu,
							.search-suggestions-menu .sub-menu,
							.site-header .search-form .search-field,
							.site-header .search-form.is--active,
							.search_jobs--frontpage .chosen-container .chosen-results,
							.header-facet-wrapper .facet-wrapper input, .header-facet-wrapper .facet-wrapper select'
					),
					array(
						'property' => 'border-top-color',
						'selector' => 'ul.primary-menu > .menu-item.menu-item-has-children > .sub-menu:before,
										  .site-header .search-suggestions-menu:before',
					),
					array(
						'property'        => 'border-top-color',
						'media'           => 'only screen and  (min-width: 900px)',
						'selector'        => '.primary-menu ul .children, ul.primary-menu .sub-menu',
						'callback_filter' => 'listable_customify_darker_callback'
					),
					array(
						'property' => 'background-color',
						'media'    => 'not screen and (min-width: 900px)',
						'selector' => '.menu-wrapper, .search-form input.search-field',
					),
					array(
						'property'        => 'background-color',
						'media'           => 'not screen  and (min-width: 900px)',
						'selector'        => '.primary-menu ul .children, ul.primary-menu .sub-menu',
						'callback_filter' => 'listable_customify_darker_callback'
					),
				)
			),
			'site_title_color'            => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Site Title Color', 'listable' ),
				'live'    => true,
				'default' => SM_DARK_PRIMARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.site-header .site-title,
							.menu-trigger, .search-trigger--mobile'
					),
				),
			),
			'search_color'                => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Search Color', 'listable' ),
				'live'    => true,
				'default' => SM_DARK_PRIMARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.search-form .search-field,
							.search-form .search-submit',
					),
				)
			),
			'nav_link_color'              => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Nav Link Color', 'listable' ),
				'live'    => true,
				'default' => SM_DARK_TERTIARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.header--transparent .primary-menu ul .children a,
								.primary-menu ul .header--transparent .children a,
								.header--transparent ul.primary-menu .sub-menu a,
								ul.primary-menu .header--transparent .sub-menu a,
								div.sd-social.sd-social .inner a span'
					),
					array(
						'property' => 'color',
						'selector' => '.primary-menu ul a, ul.primary-menu a, .menu-wrapper a,
								.primary-menu ul .page_item a,
								ul.primary-menu .menu-item a,
								.primary-menu ul .page_item_has_children > a,
								ul.primary-menu .menu-item-has-children > a'
					),
					array(
						'property' => 'border-top-color',
						'selector' => '.sub-menu .primary-menu ul .page_item_has_children:after,
								.primary-menu ul .sub-menu .page_item_has_children:after,
								.sub-menu ul.primary-menu .menu-item-has-children:after,
								ul.primary-menu .sub-menu .menu-item-has-children:after,
								.primary-menu ul .page_item_has_children:after,
								ul.primary-menu .menu-item-has-children:after,
								.primary-menu ul > .cta.page_item:after,
								ul.primary-menu > .cta.menu-item:after',
					),
				)
			),
			'nav_active_color'            => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Nav Active Color', 'listable' ),
				'live'    => true,
				'default' => SM_COLOR_PRIMARY,
				'css'     => array(
					array(
						'property' => 'border-top-color',
						'selector' => '.primary-menu.primary-menu ul .page_item_has_children:hover:after,
											ul.primary-menu.primary-menu .menu-item-has-children:hover:after,
											.hover.menu-item-has-children > a:after, .hover.page_item_has_children > a:after,
											.page-template-front_page .is--active .search-field-wrapper:after',
					),
					array(
						'property' => 'border-left-color',
						'selector' => '.search-suggestions-menu .menu-item-has-children:hover:after',
					),
					array(
						'property' => 'color',
						'selector' => '.primary-menu > ul li:hover > a, ul.primary-menu li:hover > a,
												.search-suggestions-menu li:hover > a,
												.header--transparent .primary-menu ul .page_item_has_children:hover > a,
												.header--transparent .primary-menu ul .page_item:hover > a,
												.header--transparent ul.primary-menu .menu-item-has-children:hover > a,
												.header--transparent ul.primary-menu .menu-item:hover > a,
												.page-listings .select2-results .select2-results__options .select2-results__option:hover,
												.page-listings .select2-container--default .select2-results__option--highlighted[aria-selected]:not(:first-child)'
					),
					array(
						'property' => 'background-color',
						'selector' => '.page-template-front_page .search-suggestions-menu > .menu-item:hover > a,
							.search_jobs--frontpage .chosen-container .chosen-results li:hover,
							.select2-results .select2-results__options .select2-results__option:hover,
							.select2-container--default .select2-results__option--highlighted[aria-selected]:not(:first-child)'
					),
					array(
						'media'    => 'not screen and (min-width: 900px)',
						'selector' => 'ul.primary-menu .hover.menu-item-has-children > a, .primary-menu > ul .hover.page_item_has_children > a',
						'property' => 'color',
					),
				)
			),
			'nav_button_color'            => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Nav Button Color', 'listable' ),
				'live'    => true,
				'default' => SM_LIGHT_SECONDARY,
				'css'     => array(
					array(
						'property' => 'border-color',
						'media'    => 'screen and (min-width: 900px) ',
						'selector' => '
							    .primary-menu ul > .cta.page_item,
							    ul.primary-menu > .cta.menu-item,
							    .search_jobs--frontpage-facetwp .facetwp-facet',
					),
				)
			),
			// main content
			'main_content_colors_title' => array(
				'type' => 'html',
				'html' => '<span id="section-main-content-colors" class="separator section label large">' . esc_html__( 'Main Content', 'listable' ) . '</span>',
			),
			'content_background'          => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Content Background', 'listable' ),
				'live'    => true,
				'default' => SM_LIGHT_PRIMARY,
				'css'     => array(
					array(
						'property' => 'background-color',
						'selector' => 'html, .mce-content-body, .job_filters, .page-package-selection .hentry.hentry, .single-job_listing .entry-header:before,
							.listing-sidebar--main .widget:not(.widget_listing_tags):not(.widget_listing_actions):not(.widget_listing_comments),
							.listing-sidebar--top .widget,
							.listing-sidebar--bottom,
							.listing-sidebar--main .comment-respond,
							.page-add-listing .entry-content,
							.page-add-listing fieldset:first-child,
							.woocommerce-account:not(.logged-in) .entry-content .woocommerce form,
							.post-password-form,
							.page-listings div.job_listings .load_more_jobs strong,
							body.registration .entry-content,
							.search-form .search_jobs--frontpage .search-field,

							.search_jobs select,
							.chosen-container-multi .chosen-choices,
							.chosen-container-single .chosen-single,
							.select2-drop,
							.chosen-container .chosen-drop,

							.chosen-container .chosen-results li.no-results,
							
							input, 
							select, 
							textarea, 
							.select2-container--default .select2-selection--single .select2-selection__rendered, 
							.select2-container--default .select2-selection--multiple .select2-selection__rendered, 
							#page .nf-form-cont textarea, 
							#page .nf-form-cont input:not([type="button"]):not([type="submit"]), 
							#page .nf-form-cont .listmultiselect-wrap select, 
							#page .nf-form-cont .list-select-wrap select, 
							#page .nf-form-cont .listcountry-wrap select, 
							#page .wpforms-form input[type=date], 
							#page .wpforms-form input[type=datetime], 
							#page .wpforms-form input[type=datetime-local], 
							#page .wpforms-form input[type=email], 
							#page .wpforms-form input[type=month], 
							#page .wpforms-form input[type=number], 
							#page .wpforms-form input[type=password], 
							#page .wpforms-form input[type=range], 
							#page .wpforms-form input[type=search], 
							#page .wpforms-form input[type=tel], 
							#page .wpforms-form input[type=text], 
							#page .wpforms-form input[type=time], 
							#page .wpforms-form input[type=url], 
							#page .wpforms-form input[type=week], 
							#page .wpforms-form select, 
							#page .wpforms-form textarea,

							.description_tooltip,
							.description_tooltip:after,

							.woocommerce-account.logged-in .myaccount, 
							.woocommerce-account.logged-in .myaccount:after,
							.entry-content table:not(.cart-totals):not(.ui-datepicker-calendar),
							#job-manager-job-dashboard table,

							.search_jobs--frontpage input,
							.search_jobs--frontpage .chosen-single,
							.search_jobs--frontpage-facetwp.search_jobs--frontpage select,

							.search_jobs--frontpage-facetwp .facetwp-facet,

							.toggle-hidden-facets,
							.myflex.no-map .search_jobs,

							.tooltip, .action--share div.sharedaddy,
							.listing-sidebar--secondary .widget_search form input[type="text"],
							.select2-results__options,
							.select2-container--default .select2-selection--single,
							.select2-container--default .select2-selection--multiple .select2-selection__rendered,
							.job-manager-form .select2-selection--multiple,
							
							.lwa-modal,
							#content nav.job-manager-pagination ul li > a:hover,
							
							ul.job-dashboard-actions a, 
                            ul.job-manager-bookmark-actions a, 
                            .woocommerce-account.logged-in .woocommerce a.button, 
                            .woocommerce-account.logged-in a.edit, 
                            
                            input[type="submit"].secondary, 
                            button[type="submit"].secondary,
                            
                            .page-add-listing .select2-container--default .select2-selection--multiple .select2-selection__choice, 
                            .page-listings .select2-container--default .select2-selection--multiple .select2-selection__choice, 
                            .post-type-archive-job_listing .select2-container--default .select2-selection--multiple .select2-selection__choice,
                            
                            div.wpforms-container-full .wpforms-form input[type=checkbox], 
                            div.wpforms-container-full .wpforms-form input[type=radio],
                            
                            .woocommerce-checkout-payment'
					),
					array(
						'property'        => 'background-color',
						'selector'        => '.chosen-container-multi .chosen-choices li.search-field input[type=text], 
						                      .page-add-listing .select2-container .select2-search--inline .select2-search__field',
						'callback_filter' => 'listable_css_important_callback',
					),
					array(
						'property' => 'border-left-color',
						'selector' => '.lwa-form .button-arrow:after',
					),
					array(
						'property' => 'border-top-color',
						'selector' => '
							    .uploader-btn .spacer:after,
							    .tooltip:before,
							    .action--share div.sharedaddy:before',
					),
					array(
						'property' => 'border-right-color',
						'selector' => '
							    .widget_listing_comments #add_post_rating:not(:empty):before,
							    .uploader-btn .spacer:after',
					),
					array(
						'property' => 'color',
						'selector' => '
							    .page-template-front_page .search_jobs--frontpage .search-submit,
							    .primary-menu.secondary-menu > ul > li,
							    ul.primary-menu.secondary-menu > li,
							    ul.primary-menu.secondary-menu .hover.menu-item-has-children,
							    .primary-menu.secondary-menu > ul > li:hover,
							    ul.primary-menu.secondary-menu > li:hover
							    .lwa-form button[type="submit"],

							    .btn,
							    input[type="submit"],
							    button[type="submit"],
							    .page-template-front_page .search_jobs--frontpage .search-submit,
							    .job-manager-form fieldset .job-manager-uploaded-files .job-manager-uploaded-file .job-manager-uploaded-file-preview a,
							    .woocommerce-account:not(.logged-in) .woocommerce form.login input[type="submit"],
							    body.registration .entry-content #buddypress .standard-form input#signup_submit[type="submit"],
							    .woocommerce .button,
							    .woocommerce-message > a,
							    .fwp-load-more,
							    .card__featured-tag,
							    .product .product__tag,

							    .entry-content .woocommerce a.button, .woocommerce .entry-content a.button, .entry-content .woocommerce-message > a,
							    .entry-content a.btn:hover, .entry-content .page-template-front_page .search_jobs--frontpage a.search-submit:hover,
							    .page-template-front_page .search_jobs--frontpage .entry-content a.search-submit,
							    .page-template-front_page .search_jobs--frontpage .entry-content a.search-submit:hover,
							    .entry-content a.btn,
							    .entry-content .page-template-front_page .search_jobs--frontpage a.search-submit,
							    .entry-content .job-manager-form fieldset .job-manager-uploaded-files .job-manager-uploaded-file .job-manager-uploaded-file-preview a,
							    .job-manager-form fieldset .job-manager-uploaded-files .job-manager-uploaded-file .job-manager-uploaded-file-preview .entry-content a,
							    .job-manager-form fieldset .job-manager-uploaded-files .job-manager-uploaded-file .job-manager-uploaded-file-preview .entry-content a:hover,
							    .entry-content .job-manager-form fieldset .job-manager-uploaded-files .job-manager-uploaded-file .job-manager-uploaded-file-preview a:hover,
							    .entry-content .woocommerce a.button:hover, .woocommerce .entry-content a.button:hover,
							    .entry-content .woocommerce-message > a:hover,

							    .action--favorite.bookmarked .action__icon[class],
                                .wp-job-manager-bookmarks-form[class].has-bookmark .action__icon[class],
								.listing-sidebar--secondary .widget_shopping_cart_content .woocommerce-mini-cart__buttons a,
								.listing-sidebar--secondary .widget_shopping_cart_content .woocommerce-mini-cart__buttons a:hover,
								
								#page .nf-form-cont button, 
								#page .nf-form-cont input[type=button], 
								#page .nf-form-cont input[type=submit], 
								#page .wpforms-form input[type=submit], 
								#page .wpforms-form button[type=submit], 
								#page .wpforms-form .wpforms-page-button',
					),
				),
			),
			'page_background'             => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Page Background', 'listable' ),
				'live'    => true,
				'default' => SM_LIGHT_SECONDARY,
				'css'     => array(
					array(
						'property' => 'background-color',
						'selector' => '.front-page-section:nth-child(2n),
								.blog, .archive, .woocommerce.archive,
								.page-header, .page-header-background,
								.single:not(.single-job_listing) .entry-featured, .page .entry-featured,
								.error404 .entry-header, .search-no-results .entry-header,
								.single-job_listing,
								.page-add-listing .hentry,
								.job_filters .showing_jobs,
								.job_listing_packages,
								.page-listings,
								.tax-job_listing_category,
								.tax-job_listing_tag,
								.single-action-buttons .action__icon,
								.woocommerce-account:not(.logged-in) .site-content,
								.woocommerce-account:not(.logged-in) .entry-content,
								.mobile-buttons, .tabs.wc-tabs,
								.woocommerce-cart,
								.woocommerce-checkout,
								body.registration,
								.woocommerce-account.logged-in .page,
								.page-job-dashboard,
								.page-my-bookmarks,
								.page-add-listing .hentry,
								.page-job-dashboard .hentry,
								.facetwp-pager .facetwp-pager-label,
								.facetwp-pager a.active,
								.facetwp-pager a.active:hover,
								.widgets_area .front-page-section:nth-child(odd) .product_list_widget li,
								.widgets_area .job_listings .job_listing > a:hover,
								.widgets_area .job_listings .job_listing.job_position_featured > a,
								.widgets_area .job_listings .job_listing.job_position_featured > a:hover,
								.listing-sidebar--main .product_list_widget li,
								.listing-sidebar--main .job_listings .job_listing > a:hover,
								.listing-sidebar--main .job_listings .job_listing.job_position_featured > a:hover,
								.listing-sidebar--main .job_listings .job_listing.job_position_featured > a,
								.listing-sidebar--secondary .product_list_widget li,
								.listing-sidebar--secondary .job_listings .job_listing > a:hover,
								.listing-sidebar--secondary .job_listings .job_listing.job_position_featured > a:hover,
								.listing-sidebar--secondary .job_listings .job_listing.job_position_featured > a,
								
                                input[type="submit"].secondary:hover, 
                                button[type="submit"].secondary:hover',
					),
					array(
						'property' => 'background-color',
						// 'media' 		=> 'screen and (min-width: 1000px) ',
						'selector' => '.job_listing_packages',
					),
					array(
						'media'    => 'not screen and (min-width: 480px)',
						'property' => 'background-color',
						'selector' => '.grid.job_listings > .grid__item,
										.job_listings.product-content > .grid__item,
										.product-content.job_listings > *'
					),

				)
			),
			'page_titles_color'           => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Page Titles Color', 'listable' ),
				'live'    => true,
				'default' => SM_DARK_PRIMARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.page-title,
								.widget_title--frontpage,
								.single:not(.single-job_listing) .entry-title, .page .entry-title,

								.card__title.card__title,
								.card__title.card__title a,
								.package__price,
								.product .card__title.card__title,

								h1, h2, h3, h4, h5, h6,
								.results,
								.intro,
								.listing-sidebar .widget_sidebar_title',
					),
				)
			),
			'page_subtitles_color'        => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Page Subtitles Color', 'listable' ),
				'live'    => true,
				'default' => SM_DARK_TERTIARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.widget_subtitle--frontpage',
					),
				)
			),
			'text_color'                  => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Text Color', 'listable' ),
				'live'    => true,
				'default' => SM_DARK_PRIMARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '
							    .entry-content a:hover, 
							    .comment-content a:hover,
							    #page .nf-form-cont .nf-field label, 
                                #page .wpforms-form .wpforms-field-label, 
								label, 
								html,

								.widget--footer .search-form .search-field,
								.entry-content a.listing-contact,
								.single-action-buttons .action__text, .single-action-buttons .action__text--mobile,
								div.sd-social.sd-social > div.sd-content.sd-content ul li > a span,

								.listing-sidebar,
								.widget_listing_content,
								.widget_listing_comments h3.pixrating_title,
								.widget_listing_sidebar_categories .category-text,
								.widget_listing_tags .tag__text,
								ol.comment-list .fn,
								ol.comment-list .fn a,
								ol.comment-list .comment-content,
								ol.comment-list .reply,
								.comment-respond label,
								.page-template-front_page .search-form .search-field,
								.woocommerce-account:not(.logged-in) .woocommerce form.login .form-row .required,
								.mobile-buttons .btn--view, .search_jobs--frontpage .chosen-container .chosen-results li,

								.entry-content_wrapper .widget-area--post .widget_subtitle,
								.entry-content table:not(.cart-totals):not(.ui-datepicker-calendar) td a:not([class*="job-dashboard-action"]),
								#job-manager-job-dashboard table td a:not([class*="job-dashboard-action"]),
								
								.widgets_area .product_list_widget li a .product-title,
								.widgets_area .widget_shopping_cart_content .woocommerce-mini-cart .woocommerce-mini-cart-item a:nth-of-type(2) .card__content,
								.widgets_area .widget_shopping_cart_content .woocommerce-mini-cart__total,
                                .widgets_area .job_listings .content .meta .job-type:hover,
								.widgets_area .widget_shopping_cart_content .woocommerce-mini-cart__buttons a,
								
								.listing-sidebar--main .product_list_widget li a .product-title,
								.listing-sidebar--main .widget_shopping_cart_content .woocommerce-mini-cart .woocommerce-mini-cart-item a:nth-of-type(2) .card__content,
								.listing-sidebar--main .widget_shopping_cart_content .woocommerce-mini-cart__total,
								.listing-sidebar--main .job_listings .content .meta .job-type:hover,
								
								.listing-sidebar--secondary .product_list_widget li a .product-title,
								.listing-sidebar--secondary .widget_shopping_cart_content .woocommerce-mini-cart .woocommerce-mini-cart-item a:nth-of-type(2) .card__content,
								.listing-sidebar--secondary .widget_shopping_cart_content .woocommerce-mini-cart__total,
								.listing-sidebar--secondary .job_listings .content .meta .job-type:hover,
								
                                .btn--apply-coupon.btn--apply-coupon,
                                input.btn--update-cart[type="submit"],
                                .select2-container--default .select2-results__option--highlighted:first-child,
                                
                                .lwa-modal label,
                                
                                ul.job-dashboard-actions a:hover, 
                                ul.job-manager-bookmark-actions a:hover, 
                                .woocommerce-account.logged-in .woocommerce a.button:hover, 
                                .woocommerce-account.logged-in a.edit:hover, 
                                ul.job-dashboard-actions a:focus, 
                                ul.job-manager-bookmark-actions a:focus, 
                                .woocommerce-account.logged-in .woocommerce a.button:focus, 
                                .woocommerce-account.logged-in a.edit:focus,
                                
                                input[type="submit"].secondary:hover, 
                                button[type="submit"].secondary:hover,
                                
                                div.wpforms-container-full .wpforms-form input[type=date], 
							    div.wpforms-container-full .wpforms-form input[type=datetime], 
							    div.wpforms-container-full .wpforms-form input[type=datetime-local], 
							    div.wpforms-container-full .wpforms-form input[type=email], 
							    div.wpforms-container-full .wpforms-form input[type=month], 
							    div.wpforms-container-full .wpforms-form input[type=number], 
							    div.wpforms-container-full .wpforms-form input[type=password], 
							    div.wpforms-container-full .wpforms-form input[type=range], 
							    div.wpforms-container-full .wpforms-form input[type=search], 
							    div.wpforms-container-full .wpforms-form input[type=tel], 
							    div.wpforms-container-full .wpforms-form input[type=text], 
							    div.wpforms-container-full .wpforms-form input[type=time], 
							    div.wpforms-container-full .wpforms-form input[type=url], 
							    div.wpforms-container-full .wpforms-form input[type=week], 
							    div.wpforms-container-full .wpforms-form select, 
							    div.wpforms-container-full .wpforms-form textarea'
					),
					array(
						'property'        => 'border-color',
						'selector'        => '
                                input[type=date], 
                                input[type=datetime], 
                                input[type=datetime-local], 
                                input[type=email], 
                                input[type=month], 
                                input[type=number], 
                                input[type=password], 
                                input[type=range], 
                                input[type=search], 
                                input[type=tel], 
                                input[type=text], 
                                input[type=time], 
                                input[type=url], 
                                input[type=week],
                                input[type="checkbox"],
                                input[type="checkbox"]:focus,
                                .package__btn.package__btn:hover,
                                .facetwp-checkbox.facetwp-checkbox:hover:after,
                                .facetwp-checkbox.facetwp-checkbox.checked:after,
                                select, 
                                textarea, 
                                .select2-container--default .select2-selection--single .select2-selection__rendered, 
                                .select2-container--default .select2-selection--multiple .select2-selection__rendered, 
                                #page .nf-form-cont textarea, 
                                #page .nf-form-cont input:not([type="button"]):not([type="submit"]), 
                                #page .nf-form-cont .listmultiselect-wrap select, 
                                #page .nf-form-cont .list-select-wrap select, 
                                #page .nf-form-cont .listcountry-wrap select, 
                                #page .wpforms-form input[type=date], 
                                #page .wpforms-form input[type=datetime], 
                                #page .wpforms-form input[type=datetime-local], 
                                #page .wpforms-form input[type=email], 
                                #page .wpforms-form input[type=month], 
                                #page .wpforms-form input[type=number], 
                                #page .wpforms-form input[type=password], 
                                #page .wpforms-form input[type=range], 
                                #page .wpforms-form input[type=search], 
                                #page .wpforms-form input[type=tel], 
                                #page .wpforms-form input[type=text], 
                                #page .wpforms-form input[type=time], 
                                #page .wpforms-form input[type=url], 
                                #page .wpforms-form input[type=week], 
                                #page .wpforms-form select, 
                                #page .wpforms-form textarea,
                                #page .wpforms-form input[type="checkbox"],
                                #page .wpforms-form input[type="checkbox"]:focus
                                ',
						'callback_filter' => 'listable_border_opacity_callback',
					),
					array(
						'property'        => 'border-top-color',
						'selector'        => '
                                ul.primary-menu > .menu-item.menu-item-has-children > .sub-menu:after,
                                .description_tooltip:after',
						'callback_filter' => 'listable_border_opacity_callback',
					),
					array(
						'property'        => 'border-right-color',
						'selector'        => '.description_tooltip.left:after',
						'callback_filter' => 'listable_border_opacity_callback',
					),
					array(
						'property'        => 'border-left-color',
						'selector'        => '
                                .description_tooltip.right:after,
                                .uploader-btn .spacer:after',
						'callback_filter' => 'listable_border_opacity_callback',
					),
					array(
						'property'        => 'border-bottom-color',
						'selector'        => '
                                .uploader-btn .spacer:after',
						'callback_filter' => 'listable_border_opacity_callback',
					),
					array(
						'property' => 'background-color',
						'selector' => '
							    .btn:hover,
							    input[type="submit"]:hover,
							    button[type="submit"]:hover,
							    .page-template-front_page .search_jobs--frontpage .search-submit:hover,
							    .lwa-form button[type="submit"]:hover,
							    .job-manager-form fieldset .job-manager-uploaded-files .job-manager-uploaded-file .job-manager-uploaded-file-preview a:hover,
							    .woocommerce-account:not(.logged-in) .woocommerce form.login input[type="submit"]:hover,
							    body.registration .entry-content #buddypress .standard-form input#signup_submit[type="submit"]:hover,
							    .woocommerce .button:hover,
							    .woocommerce-message > a:hover,
							    .fwp-load-more:hover,
							    .btn:focus,
							    input[type="submit"]:focus,
							    button[type="submit"]:focus,
							    .page-template-front_page .search_jobs--frontpage .search-submit:focus,
							    .job-manager-form fieldset .job-manager-uploaded-files .job-manager-uploaded-file .job-manager-uploaded-file-preview a:focus,
							    .woocommerce-account:not(.logged-in) .woocommerce form.login input[type="submit"]:focus,
							    body.registration .entry-content #buddypress .standard-form input#signup_submit[type="submit"]:focus,
							    .woocommerce .button:focus, 
							    .woocommerce-message > a:focus,
							    .fwp-load-more:focus,
							    
							    #page .nf-form-cont button:hover, 
								#page .nf-form-cont input[type=button]:hover, 
								#page .nf-form-cont input[type=submit]:hover, 
								#page .wpforms-form input[type=submit]:hover, 
								#page .wpforms-form button[type=submit]:hover, 
								#page .wpforms-form .wpforms-page-button:hover',
					),
				)
			),
			'buttons_color'               => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Buttons Color', 'listable' ),
				'live'    => true,
				'default' => SM_COLOR_PRIMARY,
				'css'     => array(
					array(
						'property' => 'background-color',
						'selector' => '
							    .btn,
							    input[type="submit"],
								button[type="submit"],
								.page-template-front_page .search-form .search-submit,
								.page-template-front_page .search_jobs--frontpage .search-submit,
								.lwa-form button[type="submit"],
								.job-manager-form fieldset .job-manager-uploaded-files .job-manager-uploaded-file .job-manager-uploaded-file-preview a,
								body.registration .entry-content #buddypress .standard-form input#signup_submit[type="submit"],
								.woocommerce-account:not(.logged-in) .woocommerce form.login input[type="submit"],
								.woocommerce .button,
								.woocommerce-message > a,
								.fwp-load-more,
								.popup__rating,
								.single-action-buttons .action:hover .action__icon,
								.action--favorite.bookmarked .action__icon,
								.wp-job-manager-bookmarks-form[class].has-bookmark .action__icon,
								.package--labeled .package__btn.package__btn,
								.featured-label,
								.product .product__tag,
								.wc-bookings-date-picker .ui-datepicker td > a:hover,
								.wc-bookings-date-picker .ui-datepicker table .bookable-range a,
								.wc-bookings-date-picker .ui-datepicker table .ui-datepicker-current-day a,
								.block-picker > li a.selected,
								.block-picker > li a:hover,
								.lwa-form input[type="submit"]:hover,
								.no-results .clear-results-btn,
								.widgets_area .widget_shopping_cart_content .remove_from_cart_button:after,
								.listing-sidebar--main .widget_shopping_cart_content .remove_from_cart_button:after,
								.listing-sidebar--secondary .widget_shopping_cart_content .remove_from_cart_button:after,
								
								#page .nf-form-cont button, 
								#page .nf-form-cont input[type=button], 
								#page .nf-form-cont input[type=submit], 
								#page .wpforms-form input[type=submit], 
								#page .wpforms-form button[type=submit], 
								#page .wpforms-form .wpforms-page-button
								',
					),
					array(
						'property' => 'color',
						'selector' => '
							    .package__btn.package__btn,
							    .nav-links a:hover,
							    .widgets_area .widget_shopping_cart_content .woocommerce-mini-cart__buttons a:hover,
							    .widgets_area .job_listings .content .meta .job-type,
							    .listing-sidebar--main .job_listings .content .meta .job-type,
							    .listing-sidebar--secondary .job_listings .content .meta .job-type,
							    
							    .btn--apply-coupon.btn--apply-coupon:hover,
							    input.btn--update-cart[type="submit"]:hover',
					),
					array(
						'property' => 'fill',
						'selector' => '.heart.is--bookmarked #hearshape',
					),
					array(
						'property' => 'border-color',
						'selector' => '.btn--apply-coupon.btn--apply-coupon:hover,
							                input.btn--update-cart[type="submit"]:hover,
							                div.wpforms-container-full .wpforms-form input[type=checkbox], 
							                div.wpforms-container-full .wpforms-form input[type=radio]',
					),
				)
			),
			// cards
			'cards_colors_title' => array(
				'type' => 'html',
				'html' => '<span id="section-cards-colors" class="separator section label large">' . esc_html__( 'Cards', 'listable' ) . '</span>',
			),
			'cards_background'            => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Cards Background', 'listable' ),
				'live'    => true,
				'default' => SM_LIGHT_PRIMARY,
				'css'     => array(
					array(
						'property' => 'background-color',
						'selector' => '
							    .card,
							    .package,
							    .leaflet-popup-content,
							    .leaflet-popup-tip,
							    .facetwp-pager > span,
							    .facetwp-pager > a,
							    #content nav.job-manager-pagination ul li > span,
							    #content nav.job-manager-pagination ul li > a,
							    #content nav.job-manager-pagination ul li span.current,
							    .nav-links a,
							    .entry-content_wrapper .widget-area--post .section-wrap,
							    .widgets_area .front-page-section:nth-child(even) .product_list_widget li'
					),
				)
			),
			'cards_radius'                => array(
				'type'        => 'range',
				'label'       => esc_html__( 'Cards Radius', 'listable' ),
				'live'        => true,
				'default'     => 8,
				'input_attrs' => array(
					'min'  => 0,
					'max'  => 36,
					'step' => 2,
				),
				'css'         => array(
					array(
						'selector' => '.card',
						'property' => 'border-radius',
						'unit'     => 'px'
					)
				)
			),
			'thumbs_radius'               => array(
				'type'        => 'range',
				'label'       => esc_html__( 'Thumbs Radius', 'listable' ),
				'live'        => true,
				'default'     => 4,
				'input_attrs' => array(
					'min'  => 0,
					'max'  => 36,
					'step' => 2,
				),
				'css'         => array(
					array(
						'selector' => 'ul.categories--widget .category-cover',
						'property' => 'border-radius',
						'unit'     => 'px'
					)
				)
			),
			'cards_title_color'           => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Title Color', 'listable' ),
				'live'    => true,
				'default' => SM_COLOR_PRIMARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.card--listing .card__title.card__title,
												.card--post a:hover,
												.grid__item--widget .posted-on a:hover,
												.grid__item--widget .card--post a:hover,
												.popup__title.popup__title',
					),
				)
			),
			'cards_text_color'            => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Text Color', 'listable' ),
				'live'    => true,
				'default' => SM_DARK_TERTIARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.card, .card__content, .card--post.sticky,
							.popup__address, .package__description,
							.single-job_listing .entry-subtitle,
							.section-wrap',
					),
					array(
						'property' => 'border-color',
						'selector' => '
							    .facetwp-pager > span:after,
							    .facetwp-pager > a:after,
							    #content nav.job-manager-pagination ul li > span:after,
							    #content nav.job-manager-pagination ul li > a:after,
							    #content nav.job-manager-pagination ul li span.current:after,
							    .nav-links a:after',
					),
				)
			),
			'cards_icon_color'            => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Icons Color', 'listable' ),
				'live'    => true,
				'default' => SM_COLOR_PRIMARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.card .pin,
												.card .pin__icon,
												.card--listing .card__rating.rating,
												.widget_listing_sidebar_categories .category-icon',
					),
				)
			),
			'cards_icon_border_color'     => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Icons Border Color', 'listable' ),
				'live'    => true,
				'default' => SM_COLOR_PRIMARY,
				'css'     => array(
					array(
						'property' => 'border-color',
						'selector' => '.card__tag,
											.card__rating.rating,
											.single-action-buttons .action__icon,
											.widget_listing_sidebar_categories .category-icon',
					),
					array(
						'property' => 'fill',
						'selector' => '.pin--selected #selected',
					),
					array(
						'property' => 'color',
						'selector' => '.card__rating.card__pin',
					),
				)
			),
			'cards_icon_background_color' => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Icon Background Color', 'listable' ),
				'live'    => true,
				'default' => SM_LIGHT_PRIMARY,
				'css'     => array(
					array(
						'property' => 'background-color',
						'selector' => '.card__tag,
								.card__rating.rating,
								.widget_listing_sidebar_categories .category-icon',
					),
				)
			),
			'pin_background_color'        => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Pin Background Color', 'listable' ),
				'live'    => true,
				'default' => SM_LIGHT_PRIMARY,
				'css'     => array(
					array(
						'property' => 'fill',
						'selector' => '.pin #selected,
								.marker-cluster svg #svgCluster2,
								.marker-cluster svg #svgCluster3,
								.marker-cluster svg #svgCluster4,
								.pin #oval',
					),
				)
			),
			'pin_icon_color'              => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Pin Color', 'listable' ),
				'live'    => true,
				'default' => SM_COLOR_PRIMARY,
				'css'     => array(
					array(
						'property' => 'fill',
						'selector' => '.pin--selected #selected,
								.marker-cluster svg #svgCluster1,
								.heart.is--bookmarked #heartshape',
					),
					array(
						'property' => 'color',
						'selector' => '.marker-cluster, .pin__icon',
					),
				)
			),
			// Pre Footer
			'prefooter_colors_title' => array(
				'type' => 'html',
				'html' => '<span id="section-prefooter-colors" class="separator section label large">' . esc_html__( 'Pre-Footer', 'listable' ) . '</span>',
			),
			'prefooter_background'        => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Background', 'listable' ),
				'live'    => true,
				'default' => SM_DARK_SECONDARY,
				'css'     => array(
					array(
						'property' => 'background-color',
						'selector' => '.footer-widget-area'
					),
				)
			),
			'prefooter_text_color'        => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Text Color', 'listable' ),
				'live'    => true,
				'default' => SM_LIGHT_PRIMARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.widget--footer',
					)
				)
			),
			// esc_html__( 'Footer', 'listable' )
			'footer_colors_title' => array(
				'type' => 'html',
				'html' => '<span id="section-footer-colors" class="separator section label large">' . esc_html__( 'Footer', 'listable' ) . '</span>',
			),
			'footer_background'           => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Background', 'listable' ),
				'live'    => true,
				'default' => SM_DARK_PRIMARY,
				'css'     => array(
					array(
						'property' => 'background-color',
						'selector' => '.site-footer'
					),
				)
			),
			'footer_text_color'           => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Text Color', 'listable' ),
				'live'    => true,
				'default' => SM_LIGHT_SECONDARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.site-info',
					),
				)
			),
			'footer_credits_color'        => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Credits Color', 'listable' ),
				'live'    => true,
				'default' => SM_LIGHT_SECONDARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.theme-info',
					),
				)
			),
			// esc_html__( 'Other Colors', 'listable' )
			'other_colors_title' => array(
				'type' => 'html',
				'html' => '<span id="section-other-colors" class="separator section label large">' . esc_html__( 'Other Colors', 'listable' ) . '</span>',
			),
			'accent_color'                => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Accent Color', 'listable' ),
				'live'    => true,
				'default' => SM_COLOR_PRIMARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.not-entry-content a,
                                .comment-content a,
                                .single-categories-breadcrumb a:hover,
                                .single-rating > i, .widget_listing_comments .comment .review_rate > i,
                                .single-action-buttons .action:hover .action__text,
                                .action--favorite.bookmarked .action__icon,
                                .wp-job-manager-bookmarks-form[class].has-bookmark .action__icon,
                                .tags-links a:hover, .tags-links a:focus,
                                .loader,
                                .listing-sidebar .widget_listing_content a,
                                .listing-sidebar a:hover,
                                .listing-sidebar .action__icon,
                                .widget_listing_comments #add_comment_rating_wrap i,
                                ol.comment-list .fn a:hover,
                                .single-job_listing .entry-title,
                                .page-listings div.job_listings .load_more_jobs:hover strong,
                                .tax-job_listing_category div.job_listings .load_more_jobs:hover strong,
                                .tax-job_listing_tag div.job_listings .load_more_jobs:hover strong,
                                .chosen-container-single .chosen-single,
                                .progress,
                                .single-product .stars a:before,
                                .product-content .price,
                                .tabs.wc-tabs li.active a,
                                .color-accent,
                                .entry-content a, .comment-content a,
                                .gallery-arrow,
                                .job-manager-form fieldset .job-manager-uploaded-files .job-manager-uploaded-file .job-manager-uploaded-file-preview a:hover,
                                .job-manager-form fieldset .job-manager-uploaded-files .job-manager-uploaded-file .job-manager-uploaded-file-preview a:focus,
                                .package__btn.package__btn:hover,
                                .site-footer a:hover,
                                .facetwp-pager a:hover,
                                .facetwp-pager a.first-page:hover:before, .facetwp-pager a.first-page:hover:after,
                                .facetwp-pager a.last-page:hover:before, .facetwp-pager a.last-page:hover:after,
                                .widget_listing_sidebar_claim_listing .listing-claim-button, .lwa-form .lwa-action-link,
                                .pac-container .pac-item:hover .pac-item-query,
                                .select2-container--default .select2-results__option:hover,
                                div.wpforms-container-full .wpforms-form label.wpforms-error'
					),
					array(
						'property' => 'background-color',
						'selector' => '.secondary-menu, .secondary-menu-wrapper:before, .product__remove,
                                .page-template-front_page .pac-container .pac-item:hover,
                                .facetwp-type-slider .noUi-connect,
                                .card__featured-tag, .woocommerce-message, .no-results .clear-results-btn',
					),
					array(
						'property' => 'background',
						'selector' => 'progress::-webkit-progress-bar',
					),
					array(
						'property' => 'background',
						'selector' => 'progress::-webkit-progress-value',
					),
					array(
						'property' => 'background',
						'selector' => 'progress::-moz-progress-bar',
					),
					array(
						'property' => 'border-top-color',
						'selector' => '.page-template-front_page .is--active .search-field-wrapper.has--menu:after,
							                ul.secondary-menu > .menu-item.menu-item-has-children > .sub-menu:before,
							                ul.secondary-menu > .menu-item.menu-item-has-children > .sub-menu:after,
							                .search_jobs--frontpage .chosen-with-drop.chosen-container-active .chosen-single:after,
							                .search_jobs--frontpage .search_region .select2-container.select2-container--open:after,
							                .search_jobs--frontpage .search_categories.search-filter-wrapper .chosen-container-single.chosen-with-drop:after',
					)
				)
			),
			'fields_color'                => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Meta Fields Color', 'listable' ),
				'live'    => true,
				'default' => SM_DARK_TERTIARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.description, .tabs.wc-tabs,
                                .single-categories-breadcrumb a,
                                .single-categories-breadcrumb a:after,
                                .single-rating .rating-value, .widget_listing_comments .comment .review_rate .rating-value,
                                div.sd-social.sd-social > div.sd-content.sd-content ul li > a,
                                .sharedaddy div.sd-social-icon .sd-content ul li[class*="share-"].share-press-this a.sd-button,
                                .sharedaddy div.sd-social-icon .sd-content ul li[class*="share-"].share-press-this a.sd-button:before,
                                .tags-links,
                                .tags-links a,
                                .listing-sidebar a,
                                .widget_listing_comments .comment-meta a,
                                .comment-meta a,
                                .single:not(.single-job_listing) .entry-subtitle, .page .entry-subtitle,
                                .single:not(.single-job_listing) .entry-meta a, .page .entry-meta a,
                                .tax-job_listing_category div.job_listings .load_more_jobs strong, .tax-job_listing_tag div.job_listings .load_more_jobs strong,

                                .search_jobs select,
                                .chosen-container-multi .chosen-choices li.search-field input[type=text],
                                .chosen-container-single .chosen-single,
                                .active-tag,
                                .select-tags .chosen-container-multi .chosen-choices,

                                .chosen-results,
                                .job-manager-form .field small.field-description,
                                .uploader-btn .spacer .text,
                                .page-add-listing .chosen-container-multi .chosen-choices li.search-choice,
                                .page-add-listing .select2-selection--multiple .select2-selection__choice,
                                .page-add-listing .select2-selection__rendered,
                                .page-add-listing .select2-results__option,
                                .page-add-listing .select2-results__option[aria-selected],
                                .page-add-listing .select2-container--default .select2-results__option--highlighted:not([aria-selected="true"]):first-child,
                                .woocommerce-account:not(.logged-in) .woocommerce form.login label[for=rememberme],
                                .woocommerce-account:not(.logged-in) .lost_password a,
                                .woocommerce-breadcrumb,
                                .product-content .price del,
                                .mfp-iframe-scaler.mfp-wp-login .mfp-close,
                                .nav-links a, .facetwp-pager a,
                                .job_filters .facetwp-type-fselect .fs-label.fs-label,
                                .page-listings div.job_listings .load_more_jobs strong, .post-type-archive-job_listing div.job_listings .load_more_jobs strong,

                                .search-form .search_jobs--frontpage .search-field,
                                .search_jobs--frontpage .search_location #search_location,
                                .search_jobs--frontpage .select-region-dummy,
                                .search_jobs--frontpage.search_jobs select,
                                .search_jobs--frontpage .chosen-single,
                                .search_jobs--frontpage-facetwp input,
                                .search_jobs--frontpage-facetwp select,

                                .facetwp-pager .facetwp-pager-label,
                                .facetwp-pager a.active,
                                .facetwp-pager a.active:hover,
                                .select2-container--default .select2-selection--single .select2-selection__rendered,
                                .page-listings .select2-results__option,
                                .page-listings .select2-container--default .select2-results__option--highlighted:first-child,
                                .search_jobs--frontpage .select2-container--default .select2-selection--single .select2-selection__rendered,
                                .select2-container--default .select2-selection--single .select2-selection__placeholder,
                                .tax-job_listing_category .select2-results__option,
                                .tax-job_listing_category .select2-container--default .select2-results__option--highlighted:first-child, 
                                .tax-job_listing_category .select2-container--default .select2-results__option--highlighted[aria-selected],
                                .job-manager-form .select2-container--default .select2-selection--single .select2-selection__rendered,
                                
                                
                                ul.job-dashboard-actions a,
                                ul.job-manager-bookmark-actions a, 
                                .woocommerce-account.logged-in .woocommerce a.button, 
                                .woocommerce-account.logged-in a.edit, 
                                ul.job-dashboard-actions a, 
                                ul.job-manager-bookmark-actions a, 
                                .woocommerce-account.logged-in .woocommerce a.button, 
                                .woocommerce-account.logged-in a.edit,
                                
                                input[type="submit"].secondary, 
                                button[type="submit"].secondary,
                                
                                #job-manager-job-dashboard table ul.job-dashboard-actions li .job-dashboard-action-delete, 
                                #job-manager-bookmarks table ul.job-manager-bookmark-actions li .job-manager-bookmark-action-delete,
                                
                                .page-add-listing .select2-container--default .select2-selection--multiple .select2-selection__choice, 
                                .page-listings .select2-container--default .select2-selection--multiple .select2-selection__choice, 
                                .post-type-archive-job_listing .select2-container--default .select2-selection--multiple .select2-selection__choice'
					),
					array(
						'property' => 'color',
						'selector' => '.page-template-front_page .search-form .search-field::-webkit-input-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.page-template-front_page .search-form .search-field::-moz-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.page-template-front_page .search-form .search-field:-moz-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.page-template-front_page .search-form .search-field::-ms-input-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.page-template-front_page .search-form .search-field:-ms-input-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.select-tags .chosen-container-multi .chosen-choices li.search-field::-webkit-input-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.select-tags .chosen-container-multi .chosen-choices li.search-field::-moz-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.select-tags .chosen-container-multi .chosen-choices li.search-field:-moz-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.select-tags .chosen-container-multi .chosen-choices li.search-field::-ms-input-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.select-tags .chosen-container-multi .chosen-choices li.search-field:-ms-input-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.listing-sidebar .widget_search form input[type"text"]::-webkit-input-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.listing-sidebar .widget_search form input[type"text"]::-moz-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.listing-sidebar .widget_search form input[type"text"]:-moz-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.listing-sidebar .widget_search form input[type"text"]::-ms-input-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.listing-sidebar .widget_search form input[type"text"]:-ms-input-placeholder'
					),
					array(
						'property' => 'color',
						'selector' => '.description_tooltip',
					),
				)
			),
			'micro_color'                 => array(
				'type'    => 'color',
				'label'   => esc_html__( 'Micro Elements', 'listable' ),
				'live'    => true,
				'default' => SM_DARK_TERTIARY,
				'css'     => array(
					array(
						'property' => 'color',
						'selector' => '.job_filters .showing_jobs,
								.tax-job_listing_category div.job_listings .load_more_jobs strong,
								.tax-job_listing_tag div.job_listings .load_more_jobs strong,
								.search-suggestions-menu .menu-item-description,
								.widget_listing_comments #add_post_rating,
								.widgets_area .job_listings .content .meta .location,
								.widgets_area .job_listings .content .meta .company,
								.listing-sidebar--main .job_listings .content .meta .company,
								.listing-sidebar--main .job_listings .content .meta .location,
								.listing-sidebar--secondary .job_listings .content .meta .company,
                                .listing-sidebar--secondary .job_listings .content .meta .location'
					),
					array(
						'property' => 'border-top-color',
						'selector' => '
								.chosen-container-single .chosen-single div b:after,
								.select2-container:after,
								.select-tags .chosen-container-multi .chosen-choices:after,
								.tax-job_listing_category .select2-container:after'
					),
					array(
						'property' => 'background-color',
						'selector' => '
								.remove-tag:before,
								.remove-tag:after'
					),
				)
			),
		),
	);

	return $config;
}

function listable_filter_color_palettes( $color_palettes ) {
	$color_palettes = array_merge(array(
		'default' => array(
			'label' => 'Default',
			'preview' => array(
				'background_image_url' => 'https://cloud.pixelgrade.com/wp-content/uploads/2018/06/listable-palette.jpg',
			),
			'options' => array(
				'sm_color_primary' => SM_COLOR_PRIMARY,
				'sm_color_secondary' => SM_COLOR_SECONDARY,
				'sm_color_tertiary' => SM_COLOR_TERTIARY,
				'sm_dark_primary' => SM_DARK_PRIMARY,
				'sm_dark_secondary' => SM_DARK_SECONDARY,
				'sm_dark_tertiary' => SM_DARK_TERTIARY,
				'sm_light_primary' => SM_LIGHT_PRIMARY,
				'sm_light_secondary' => SM_LIGHT_SECONDARY,
				'sm_light_tertiary' => SM_LIGHT_TERTIARY,
			),
		),
	), $color_palettes);

	return $color_palettes;
}
