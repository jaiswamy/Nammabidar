// /* ====== ON DOCUMENT READY ====== */
$( document ).ready( function () {
	init();

	$( '.job_filters' ).bindFirst( 'click', '.reset', function () {
		$( '.active-tags' ).empty();
		$( '.tags-select' ).find( ':selected' ).each( function ( i, obj ) {
			$( obj ).attr( 'selected', false );
		} );
		$( '.tags-select' ).trigger( "chosen:updated" );

		$( 'input[name="search_keywords"]' ).each( function ( i, obj ) {
			$( obj ).val( '' ).trigger( 'chosen:updated' );
		} );
	} );

    $('.wc-social-login').attr('data-string', listable_params.strings.social_login_string);
} );

// [name] is the name of the event "click", "mouseover", ..
// same as you'd pass it to bind()
// [fn] is the handler function
$.fn.bindFirst = function ( name, selector, fn ) {
	// bind as you normally would
	// don't want to miss out on any jQuery magic
	this.on( name, selector, fn );

	// Thanks to a comment by @Martin, adding support for
	// namespaced events too.
	this.each( function () {
		var handlers = $._data( this, 'events' )[name.split( '.' )[0]];
		// take out the handler we just inserted from the end
		var handler = handlers.pop();
		// move it at the beginning
		handlers.splice( 0, 0, handler );
	} );
};

function customizerOptionsPadding() {
    var $updatable = $( '.page-listings .js-header-height-padding-top' ),
        $map = $( '.map' ),
        $jobFilters = $( ' .job_filters .search_jobs div.search_location' ),
        $findMeButton = $( '.findme' ),
        headerHeight = $( '.site-header' ).outerHeight();

    // set padding top to certain elements which is equal to the header height

    $updatable.css( 'paddingTop', '' );
    $updatable.css( 'paddingTop', headerHeight );

    if ( $( '#wpadminbar' ).length ) {
        headerHeight += $( '#wpadminbar' ).outerHeight();
    }

    $map.css( 'top', headerHeight );
    $jobFilters.css( 'top', headerHeight );
    $findMeButton.css( 'top', headerHeight + 70);
}

function init() {
	platformDetect();
	browserSupport();
	browserSize();

	eventHandlers();

    customizerOptionsPadding();
    handleHiddenFacetFieldsOnMobile();
	handleSearchFunctionality();

	$( 'html' ).addClass( 'is--ready' );

    var $body = $( 'body' );
    var withChosen = isChosenEnabled();

    $body.toggleClass( 'with-chosen', withChosen );
    $body.toggleClass( 'without-chosen', ! withChosen );

	var $email = $( 'input#account_email' ),
		$target = $( '.field.account-sign-in' ),
		$fieldset;

	if ( $email.length && $target.length ) {
		$fieldset = $email.closest( 'fieldset' );
		$email.insertAfter( $target );
		$fieldset.remove();
	}

	var $uploader = $( '.wp-job-manager-file-upload' );

	$uploader.each( function ( i, obj ) {
		var $input = $( obj ),
			id = $( obj ).attr( 'id' ),
			$label = $( 'label[for="' + id + '"]' ),
			$btn = $( '<div class="uploader-btn"><div class="spacer"><div class="text">' + listable_params.strings['wp-job-manager-file-upload'] + '</div></div></div>' ).insertAfter( $input );

		$btn.on( 'click', function () {
			$label.trigger( 'click' );
		} );
	} );

	$( '#main_image' ).on( 'change', function ( e ) {
		var self = this;
		var this_logo = $( '#company_logo' ).val();

		if ( this_logo === '' ) {
			var url = self.value;
		}
	} );

	if ( $( '#job_preview_wrapper' ).length ) {
		$body.addClass( 'single-job_listing single-job_listing_preview' ).removeClass( 'page-add-listing' );
		$( '.page' ).removeClass( 'page' );
		$( '.listing-map' ).css( {
			display: '',
			height: ''
		} );
		singleListingMapHeight();
		$window.trigger( 'pxg:refreshmap' );
		$( '#job_preview_wrapper' ).css( 'opacity', 1 );
	}

	$( '.btn--filter' ).on( 'click', function ( e ) {
		e.preventDefault();
		e.stopPropagation();

		if ( $body.hasClass( 'show-filters' ) ) {
			$window.scrollTop( 0 );
		}
		$body.toggleClass( 'show-filters' );
	} );

	$( '.btn--view' ).on( 'click', function ( e ) {
		e.preventDefault();
		e.stopPropagation();
		$body.toggleClass( 'show-map' );
		$( 'html, body' ).scrollTop( 0 );
		setTimeout( function () {
			$window.trigger( 'pxg:refreshmap' );
		} );
	} );

	if ( $( '#job_package_selection' ).length ) {
		$body.addClass( 'page-package-selection' );

		var $nopackages = $( '.no-packages' );

		if ( $nopackages.length ) {
			var $form = $nopackages.closest( '#job_package_selection' );

			if ( $form.length ) {
				$nopackages.insertAfter( $form );
				$form.remove();
			}
		}
	}

	Map.init();

	detectLongMenu();
	moveListingStickySidebar();
	singleListingMapHeight();
	moveSingleListingReviews();
	moveSingleListingClaimWidget();

	if ( $( '.search-field-wrapper.has--menu' ).length ) {
		searchSuggestionsTrigger();
	}

	$reviewsParent = $( '.widget_listing_comments' ).parent();

	$( '.showlogin' ).off( 'click' ).on( 'click', function () {
		$( '.login-container' ).slideToggle();
	} );
}

// /* ====== ON WINDOW LOAD ====== */
$window.on('load', function () {

	$( 'html' ).addClass( 'is--loaded' );

	Carousel.init();

	// if we're on the listings archive do this shit
	// @todo do do doom
	if ( $( '.tags-select' ).length && ! $( '.listing-map' ).length ) {
		var $tags = $( '.tags-select' ).chosen(),
			updateTags = function () {
				$( '.active-tags' ).empty();
				$tags.find( ':selected' ).each( function ( i, obj ) {
					if ( empty( obj.value ) ) {
						return;
					}

					$( '<div class="active-tag">' + obj.value + '<div class="remove-tag"></div></div>' ).appendTo( '.active-tags' ).on( 'click', function () {
						$( this ).remove();
						$( obj ).attr( 'selected', false );
						$tags.trigger( "chosen:updated" );
						$( '.active-tags input[value="' + obj.value + '"]' ).remove();
						$( '.job_listings' ).triggerHandler( 'update_results', [1, false] );
					} );

					$( '<input type="hidden" name="job_tag[]" value="' + obj.value + '" />' ).appendTo( '.active-tags' );
				} );
				$( '.job_listings' ).triggerHandler( 'update_results', [1, false] );
			};

		$tags.on( 'change', updateTags );

		var $categories = $( '#search_categories' ),
			updateCategories = function () {
				$( '.active-categories' ).empty();
				$categories.find( ':selected' ).each( function ( i, obj ) {
					$( '<div class="active-category">' + $( obj ).text() + '<div class="remove-tag"></div></div>' ).appendTo( '.active-categories' ).on( 'click', function () {
						$( obj ).attr( 'selected', false );
						$categories.trigger( "chosen:updated" );
						$( this ).remove();
						$( '.job_listings' ).triggerHandler( 'update_results', [1, false] );
					} );
				} );
				updateTags();
			};

		// updateCategories();
		$categories.on( 'change', updateCategories );
	}

	if ( $().chosen ) {
		$( '.search_jobs--frontpage .job-manager-category-dropdown' ).chosen();
	}

	tooltipTrigger();
	keepSubmenusInViewport();

	$( '.js-menu-trigger' ).on( 'touchstart click', toggleMenu );

	HandleSubmenusOnTouch.unbind();
	if ( windowWidth < 900 ) {
		HandleSubmenusOnTouch.initSidebarMenu();
	} else {
		if ( Modernizr.touchevents ) {
			HandleSubmenusOnTouch.initHorizontalMenu();
		}
	}

	if ( $( '.site-header .search-form' ).is( ':visible' ) ) {
		handleMobileHeaderSearch();
	}

	frontpageVideoInit();

	loginWithAjaxHandlers();

	var $featuredVideo = $( '.entry-featured video' );

	if ( $featuredVideo.length && Modernizr.touchevents ) {
		enableInlineVideo( $featuredVideo.get( 0 ), { everywhere: true } );
	}

	$window.trigger( 'pxg:refreshmap' );
} );

//for search listings we need to make some magic to make it behave like the categories and tags archives
function handleSearchFunctionality() {

	var $searchField = $( '#search_keywords' ),
		$searchFieldPlaceholder = $( '#search_keywords_placeholder' ),
		$form = $searchFieldPlaceholder.closest( 'form' ),
		$jobListings = $( '.job_listings' );

	if ( $body.is('.page-listings' ) ) {

		$jobListings.data( 'disable-form-state-storage', true );
		$jobListings.on( 'updated_results', function() {
			$jobListings.data( 'disable-form-state-storage', '' );
		} );

		$searchFieldPlaceholder.on( 'input', function() {
			$searchField.val( $searchFieldPlaceholder.val() );
		} );

		$form.on( 'submit', function(e) {
			e.preventDefault();
			$('.job_listings').triggerHandler( 'update_results', [ 1, false ] );
		} );
	}
}

function eventHandlers() {

	$window.on( 'debouncedresize', function () {
		browserSize();
		detectLongMenu();
		moveListingStickySidebar();
		singleListingMapHeight();
		moveSingleListingReviews();
		handleHiddenFacetFieldsOnMobile();
        customizerOptionsPadding();

		setTimeout( function () {
			$window.trigger( 'update:map' );
			$window.trigger( 'pxg:refreshmap' );
		} );

		if ( windowWidth < 900 ) {
			$( '.site-header' ).css( 'paddingBottom', '' );
		} else {
			var headerPaddingBottom = parseInt( $( '.site-header' ).css( 'paddingTop' ) ) + $( '.secondary-menu' ).outerHeight();
			$( '.site-header' ).css( 'paddingBottom', headerPaddingBottom );
		}

		HandleSubmenusOnTouch.unbind();
		if ( windowWidth < 900 ) {
			HandleSubmenusOnTouch.initSidebarMenu();
		} else {
			if ( Modernizr.touchevents ) {
				HandleSubmenusOnTouch.initHorizontalMenu();
			}
		}
	} );

	$window.on( 'scroll', function () {
		latestKnownScrollY = $window.scrollTop();
		latestKnownScrollX = $window.scrollLeft();
	} );

	$( window ).on( 'mousemove', function ( e ) {
		latestKnownMouseX = e.clientX;
		latestKnownMouseY = e.clientY;
	} );

	$( window ).on( 'deviceorientation', function ( e ) {
		latestDeviceAlpha = e.originalEvent.alpha;
		latestDeviceBeta = e.originalEvent.beta;
		latestDeviceGamma = e.originalEvent.gamma;
	} );

	handleHiddenFacets();
	handleLongSubMenus();
	hideCategoryDescription();

	// After FacetWP fetches new items,
	// scroll listings page to top to see
	// all new loaded items.
	if ( $body.is( '.page-listings' ) ) {

		var $facetWpLoadMore = $( '.fwp-load-more' ).first();
		$facetWpLoadMore.insertAfter( '.facetwp-template' );
		$facetWpLoadMore.wrap( '<div class="fwp-load-more-wrapper">' );

		$facetWpLoadMore.on( 'click', function() {

			$( document ).one( 'facetwp-loaded', function() {
				// hacking starts here
				var $lists = $( '.job_listings.list' );

				if ( $lists.length <= 1 ) {
					TweenLite.to( window, 1, {scrollTo: 0} );
					console.log('here');
				} else {
					var $firstList = $lists.first();
					var $otherLists = $lists.not( $firstList );

					$otherLists.children().appendTo( $firstList );
					$otherLists.remove();
				}
			} );

		} );
	}
}
