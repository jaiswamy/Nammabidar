/* ====== HELPER FUNCTIONS ====== */


/**
 * Detect what platform are we on (browser, mobile, etc)
 */

function browserSupport() {
	$.support.touch = 'ontouchend' in document;
	$.support.svg = document.implementation.hasFeature( "http://www.w3.org/TR/SVG11/feature#BasicStructure", "1.1" );
	$.support.transform = getSupportedTransform();

	$html
		.addClass( $.support.touch ? 'touch' : 'no-touch' )
		.addClass( $.support.svg ? 'svg' : 'no-svg' )
		.addClass( ! ! $.support.transform ? 'transform' : 'no-transform' );
}

function browserSize() {
	windowHeight = $window.height();
	windowWidth = $window.width();
	documentHeight = $document.height();
	orientation = windowWidth > windowHeight ? 'portrait' : 'landscape';
}

function getSupportedTransform() {
	var prefixes = ['transform', 'WebkitTransform', 'MozTransform', 'OTransform', 'msTransform'];
	for ( var i = 0; i < prefixes.length; i ++ ) {
		if ( document.createElement( 'div' ).style[prefixes[i]] !== undefined ) {
			return prefixes[i];
		}
	}
	return false;
}

/**
 * Handler for the back to top button
 */
function scrollToTop() {
	$( 'a[href="#top"]' ).click( function ( event ) {
		event.preventDefault();
		event.stopPropagation();

		TweenMax.to( $( window ), 1, {
			scrollTo: {
				y: 0,
				autoKill: true
			},
			ease: Power3.easeOut
		} );
	} );
}

/**
 * function similar to PHP's empty function
 */

function empty( data ) {
	if ( typeof( data ) === 'number' || typeof( data ) === 'boolean' ) {
		return false;
	}
	if ( typeof( data ) === 'undefined' || data === null ) {
		return true;
	}
	if ( typeof( data.length ) !== 'undefined' ) {
		return data.length === 0;
	}
	var count = 0;
	for ( var i in data ) {
		// if(data.hasOwnProperty(i))
		//
		// This doesn't work in ie8/ie9 due the fact that hasOwnProperty works only on native objects.
		// http://stackoverflow.com/questions/8157700/object-has-no-hasownproperty-method-i-e-its-undefined-ie8
		//
		// for hosts objects we do this
		if ( Object.prototype.hasOwnProperty.call( data, i ) ) {
			count ++;
		}
	}
	return count === 0;
}

function toggleMenu( e ) {
	if ( e ) {
		e.preventDefault();
		e.stopPropagation();
	}

	$( 'body' ).toggleClass( 'nav-is-open' );

	$( 'body' ).toggleClass( 'overlay-is-open' );

	if ( $( 'body' ).hasClass( 'overlay-is-open' ) ) {
		$( 'body' ).width( $( 'body' ).width() );
		$( 'body' ).css( 'overflow', 'hidden' );
	} else {
		$( 'body' ).removeAttr( 'style' );
	}
}

function closeMenu() {
	$( 'body' ).removeClass( 'nav-is-open' );
	$( 'body' ).removeClass( 'overlay-is-open' );
	$( 'body' ).removeAttr( 'style' );
}

// Set the height of the single listing map
function singleListingMapHeight() {
	if ( windowWidth > 900 ) {
		var $listingMap = $( '.listing-sidebar--top .widget_listing_sidebar_map:first-child .listing-map' );

		if ( $( '.entry-featured-image' ).length && $listingMap.length ) {
			var featuredTop = $( '.entry-featured-image' ).offset().top;
			var featuredHeight = $( '.entry-featured-image' ).height();
			var featuredBottom = featuredTop + featuredHeight;
			var mapFeaturedDistance = $listingMap.offset().top - featuredBottom + 1;
			var headerHeight = $( '.single_job_listing .entry-header' ).outerHeight();
			var mapComputedHeight = headerHeight - mapFeaturedDistance;
			$listingMap.height( mapComputedHeight );

			$window.trigger( 'pxg:refreshmap' );
		}
	}
}


// Move listing sticky sidebar under header on mobile
function moveListingStickySidebar() {

	var $sidebarTop = $( '.listing-sidebar--top' ),
		$sidebarBottom = $( '.listing-sidebar--bottom' ),
		isTop = $sidebarTop.data( 'isTop' );

	if ( ! $sidebarTop.length ) {
		return;
	}

	if ( windowWidth < 900 ) {
		if ( isTop !== true ) {
			$sidebarTop.insertAfter( $( '.entry-header' ) );
			isTop = true;
		}
	} else {
		if ( isTop !== false ) {
			$sidebarTop.insertBefore( $sidebarBottom );
			isTop = false;
		}
	}

	$sidebarTop.data( 'isTop', isTop );
}

// When there's a long menu, prevent it from breaking on two lines
function detectLongMenu() {
	if ( windowWidth > 900 ) {
		var $menuWrapper = $( '.menu-wrapper' );

		if ( $menuWrapper.find( 'ul:first-of-type' ).height() > $menuWrapper.height() ) {
			$menuWrapper.addClass( 'has--long-menu' );
		}

		if ( $menuWrapper.find( 'ul:first-of-type' ).width() < $menuWrapper.width() ) {
			$menuWrapper.removeClass( 'has--long-menu' );
		}
	}
}

function tooltipTrigger() {
	$( '.js-tooltip-trigger' ).on( 'click', function ( e ) {
		e.preventDefault();
		e.stopPropagation();

		$( this ).parent().toggleClass( 'active' );

	} );
}

function searchSuggestionsTrigger() {
	var $searchField = $( '.js-search-suggestions-field' );
	var $searchForm = $( '.js-search-form' );

	function makeActive() {
		$searchForm.addClass( 'is--active' );
	}

	function makeInactive() {
		$searchForm.removeClass( 'is--active' );
	}

	function toggleActive() {
		$searchForm.toggleClass( 'is--active' );
	}

	$searchField.on( 'blur', function () {
		setTimeout( makeInactive, 150 );
	} );

	$searchField.on( 'click', function(e) {
		e.stopPropagation();
		toggleActive();
	} );

	$( '#page' ).on( 'click', function (e) {
		if ( $searchField.is( ':focus' ) ) {
			$searchField.blur();
		}
	} );
}

function handleMobileHeaderSearch() {
	// When clicking on search icon, show the search input
	$( '.js-search-trigger-mobile' ).on( 'click', toggleHeaderSearch );

	// When the search input loses focus, hide the input
	$( '.js-search-mobile-field' ).on( 'blur', function ( e ) {
		setTimeout( function closeMobileSearch() {
			$( '.js-search-form' ).removeClass( 'is--active' );
		}, 150 );
	} );

	function toggleHeaderSearch( e ) {
		e.preventDefault();
		e.stopPropagation();

		if ( ! $( '.js-search-form' ).hasClass( 'is--active' ) ) {
			$( '.js-search-form' ).addClass( 'is--active' );
			$( '.js-search-mobile-field' ).focus();
		}
	}
}

// Detect the submenus that exceed the viewport
// and add a class to make them open vertically
function keepSubmenusInViewport() {
	if ( $( '.primary-menu' ).length ) {
		var headerRightmost = $( '.site-header' ).outerWidth();

		$( '.sub-menu' ).each( function () {
			var submenuRightmost = $( this ).offset().left + $( this ).width();

			// if the sub menu exceeds primary menu's rightmost edge
			if ( submenuRightmost > headerRightmost ) {
				$( this ).addClass( 'is--forced-placed' );

				$( this ).find( '.sub-menu' ).addClass( 'is--forced-placed' );
			}
		} );
	}
}

function moveSingleListingReviews() {
	// On mobile, when focusing on a review field, the keyboard appears thus
	// triggering a resize. When a resize is triggered, trying to move the
	// reviews causes fields to lose focus, hiding the keyboard. Prevent that.
	if ( Modernizr.touchevents && $( 'input, textarea' ).is( ':focus' ) ) {
		return;
	}

	if ( $( '.widget_listing_comments' ).length ) {
		if ( windowWidth < 900 ) {
			if ( $( '.widget_listing_comments' ).parent().hasClass( 'column-sidebar' ) ) {
				return;
			}

			$( '.widget_listing_comments' ).appendTo( $( '.column-sidebar' ) );
		} else {
			$( '.widget_listing_comments' ).appendTo( $reviewsParent );
		}
	}
}

function moveSingleListingClaimWidget() {
	var $claimWidget = $( '.listing-sidebar--bottom .widget_listing_sidebar_claim_listing' );

	if ( $claimWidget.length ) {
		var $parentSidebar = $claimWidget.parent();

		$claimWidget.each( function () {
			if ( $( this ).is( ':first-of-type' ) ) {
				$( this ).insertBefore( $parentSidebar ).addClass( 'is--independent' );
			} else if ( $( this ).is( ':last-of-type' ) ) {
				$( this ).insertAfter( $parentSidebar ).addClass( 'is--independent' );
			}
		} );
	}
}

var listableDocumentCookies = {
	getItem: function ( sKey ) {
		if ( ! sKey ) {
			return null;
		}
		return decodeURIComponent( document.cookie.replace( new RegExp( "(?:(?:^|.*;)\\s*" + encodeURIComponent( sKey ).replace( /[\-\.\+\*]/g, "\\$&" ) + "\\s*\\=\\s*([^;]*).*$)|^.*$" ), "$1" ) ) || null;
	},
	setItem: function ( sKey, sValue, vEnd, sPath, sDomain, bSecure ) {
		if ( ! sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test( sKey ) ) {
			return false;
		}
		var sExpires = "";
		if ( vEnd ) {
			switch ( vEnd.constructor ) {
				case Number:
					sExpires = vEnd === Infinity ? "; expires=Fri, 31 Dec 9999 23:59:59 GMT" : "; max-age=" + vEnd;
					break;
				case String:
					sExpires = "; expires=" + vEnd;
					break;
				case Date:
					sExpires = "; expires=" + vEnd.toUTCString();
					break;
			}
		}
		document.cookie = encodeURIComponent( sKey ) + "=" + encodeURIComponent( sValue ) + sExpires + (
				sDomain ? "; domain=" + sDomain : ""
			) + (
			                  sPath ? "; path=" + sPath : ""
		                  ) + (
			                  bSecure ? "; secure" : ""
		                  );
		return true;
	},
	removeItem: function ( sKey, sPath, sDomain ) {
		if ( ! this.hasItem( sKey ) ) {
			return false;
		}
		document.cookie = encodeURIComponent( sKey ) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + (
				sDomain ? "; domain=" + sDomain : ""
			) + (
			                  sPath ? "; path=" + sPath : ""
		                  );
		return true;
	},
	hasItem: function ( sKey ) {
		if ( ! sKey ) {
			return false;
		}
		return (
			new RegExp( "(?:^|;\\s*)" + encodeURIComponent( sKey ).replace( /[\-\.\+\*]/g, "\\$&" ) + "\\s*\\=" )
		).test( document.cookie );
	},
	keys: function () {
		var aKeys = document.cookie.replace( /((?:^|\s*;)[^\=]+)(?=;|$)|^\s*|\s*(?:\=[^;]*)?(?:\1|$)/g, "" ).split( /\s*(?:\=[^;]*)?;\s*/ );
		for ( var nLen = aKeys.length, nIdx = 0; nIdx < nLen; nIdx ++ ) {
			aKeys[nIdx] = decodeURIComponent( aKeys[nIdx] );
		}
		return aKeys;
	}
};

function frontpageVideoInit() {
	// video resizing
	var $wrapper = $( '.page-template-front_page .entry-header .wp-video' ),
		$video = $( '.page-template-front_page .entry-header .mejs-video' ),
		$header,
		$featured,
		videoWidth,
		videoHeight,
		headerWidth,
		headerHeight,
		newWidth,
		newHeight;

	function stretch() {

		if ( (
		     videoWidth / videoHeight
		     ) > (
		     headerWidth / headerHeight
		     ) ) {
			newHeight = headerHeight;
			newWidth = newHeight * videoWidth / videoHeight;
		} else {
			newWidth = headerWidth;
			newHeight = newWidth * videoHeight / videoWidth;
		}

		$wrapper.css( {
			width: newWidth,
			height: newHeight
		} );
	}

	if ( $wrapper.length ) {
		$header = $( '.page-template-front_page .entry-header' );
		$featured = $( '.page-template-front_page .entry-featured' );
		videoWidth = $video.outerWidth();
		videoHeight = $video.outerHeight();
		headerWidth = $header.outerWidth();
		headerHeight = $header.outerHeight();

		$wrapper.find( 'video' ).prop( 'muted', true )

		stretch();
		$wrapper.addClass( 'is--stretched' ).data( 'ar', newWidth / newHeight );

		$window.on( 'debouncedresize', function () {
			headerWidth = $header.outerWidth();
			headerHeight = $header.outerHeight();
			stretch();
		} );

	}
}

// iOS Multiple Select Bug Fix
if ( navigator.userAgent.match( /iPhone/i ) ) {
	$( 'select[multiple]' ).each( function () {
		var select = $( this ).on( {
			"focusout": function () {
				var values = select.val() || [];
				setTimeout( function () {
					select.val( values.length ? values : [''] ).change();
				}, 1000 );
			}
		} );
		var firstOption = '<option value="" disabled="disabled"';
		firstOption += (
		               select.val() || []
		               ).length > 0 ? '' : ' selected="selected"';
		firstOption += '>' + select.attr( 'data-placeholder' );
		firstOption += '</option>';
		select.prepend( firstOption );
	} );
}

function loginWithAjaxHandlers() {
	if ( $( '.lwa-modal' ).length ) {

		$( '.js-lwa-open-remember-form' ).on( 'click', function ( e ) {
			e.stopPropagation();
			e.preventDefault();

			$( '.js-lwa-login, .js-lwa-remember' ).toggleClass( 'form-visible' );
		} );

		$( '.js-lwa-close-remember-form' ).on( 'click', function () {
			$( '.js-lwa-login, .js-lwa-remember' ).toggleClass( 'form-visible' );
		} );

		$( '.js-lwa-open-register-form' ).on( 'click', function ( e ) {
			e.stopPropagation();
			e.preventDefault();

			$( '.js-lwa-login, .js-lwa-register' ).toggleClass( 'form-visible' );
		} );

		$( '.js-lwa-close-register-form' ).on( 'click', function () {
			$( '.js-lwa-login, .js-lwa-register' ).toggleClass( 'form-visible' );
		} );

		$( '.lwa-login-link' ).on( 'touchstart', function () {
			closeMenu();
		} );
	}
}

var HandleSubmenusOnTouch = (
	function () {

		var $theUsualSuspects,
			$theUsualAnchors,
			initialInit = false,
			isHorizontalInitiated = false,
			isSidebarInitiated = false;

		function init() {
			if ( initialInit ) {
				return;
			}

			$theUsualSuspects = $( 'li[class*=children]' );
			$theUsualAnchors = $theUsualSuspects.find( '> a' );

			bindOuterNavClick();

			initialInit = true;
		}

		// Sub menus will be opened with a click on the parent
		// The second click on the parent will follow parent's link
		function initHorizontalMenu() {
			if ( isHorizontalInitiated ) {
				return;
			}

			init();
			unbind();

			// Make sure there are no open menu items
			$theUsualSuspects.removeClass( 'hover' );

			$theUsualAnchors.on( 'click', function ( e ) {

				if ( ! $( this ).hasClass( 'active' ) ) {
					e.preventDefault();
					e.stopPropagation();

					return;
				}

				$theUsualAnchors.removeClass( 'active' );
				$( this ).addClass( 'active' );

				// When a parent menu item is activated,
				// close other menu items on the same level
				$( this ).parent().siblings().removeClass( 'hover' );

				// Open the sub menu of this parent item
				$( this ).parent().addClass( 'hover' );
			} );

			isHorizontalInitiated = true;
		}

		// Sub menus will be opened on arrow click
		function initSidebarMenu() {
			if ( isSidebarInitiated ) {
				return;
			}

			init();
			unbind();

			$theUsualAnchors.on( 'touchstart click', function ( e ) {
				var posX = e.originalEvent.touches ? e.originalEvent.touches[0].pageX : e.pageX,
					width = $( this ).outerWidth(),
					isRtl = $( 'body' ).is( '.rtl' ),
					ltrTrigger = ! isRtl && ( width - posX < 60 ),
					rtlPos = posX - ( windowWidth - width ),
					rtlTrigger = isRtl && rtlPos < 60 && rtlPos > 0;

				if ( ltrTrigger || rtlTrigger ) {

					e.preventDefault();
					e.stopPropagation();

					if ( $( this ).parent().hasClass( 'hover' ) ) {
						$( this ).parent().removeClass( 'hover' );
					} else {
						$( this ).parent().addClass( 'hover' );
						$( this ).parent().siblings().removeClass( 'hover' );
					}
				}
			} );

			isSidebarInitiated = true;
		}

		function unbind() {
			if ( ! initialInit ) {
				return;
			}
			$theUsualAnchors.unbind();
			isHorizontalInitiated = false;
			isSidebarInitiated = false;
		}

		// When a sub menu is open, close it by a touch on
		// any other part of the viewport than navigation.
		// use case: normal, horizontal menu, touch events,
		// sub menus are not visible.
		function bindOuterNavClick() {
			$( 'body' ).on( 'touchstart', function ( e ) {
				var container = $( '.menu-wrapper' );

				if ( ! container.is( e.target ) // if the target of the click isn't the container...
				     && container.has( e.target ).length === 0 ) // ... nor a descendant of the container
				{
					$theUsualSuspects.removeClass( 'hover' ).removeClass( 'active' );
				}
			} );
		}

		return {
			initHorizontalMenu: initHorizontalMenu,
			initSidebarMenu: initSidebarMenu,
			unbind: unbind
		}
	}()
);

function handleHiddenFacets() {
	if ( ! $body.hasClass( 'is--using-facetwp' ) ) {
		return;
	}

	$( '.js-toggle-hidden-facets' ).on( 'click', function () {
		$body.toggleClass( 'is--showing-hidden-facets' );
		$( '.hidden_facets' ).slideToggle( 300 );
	} )
}

// Check if a sub menu's height is bigger that windows's width
function handleLongSubMenus() {
	if ( Modernizr.touchevents ) {
		return;
	}

	$( 'li[class*="children"] > a' ).on( 'hover', function () {
		var $subMenu = $( this ).siblings( '.sub-menu' );

		var remainingHeight = windowHeight - this.getBoundingClientRect().top;
		if ( remainingHeight < $subMenu.height() ) {
			$subMenu.addClass( 'big-one' );
		}
	} );
}

// Hide the category description after a FacetWP filtering
function hideCategoryDescription() {
	if ( $body.hasClass( 'is--using-facetwp' ) ) {

		checkAndHideForFacet();

		$( document ).on( 'facetwp-refresh', function () {
			setTimeout( function () {
				checkAndHideForFacet();
			}, 1 );
		} );

	} else {
		$( '.job_listings' ).on( 'update_results', function () {
			$( '.listing_category_description.do-hide' ).hide();

			// An 'update_results' event is triggered on page load;
			// hide it only after it gets the class do-hide;
			// (only after the initial 'update_results' event is triggered)
			$( '.listing_category_description' ).addClass( 'do-hide' );
		} );
	}
}

function checkAndHideForFacet() {
	var windowPath = window.location.href;

	if ( windowPath.indexOf( "fwp" ) > - 1 ) {
		$( '.listing_category_description' ).hide();
	} else {
		$( '.listing_category_description' ).show();
	}
}

var handleHiddenFacetFieldsOnMobile = (function() {
	var $container = $( '.header-facet-wrapper' );
	var $target = $( '.search_jobs' );
	var $fields = $container.children().not( '.search-submit' );

	return function() {
		if ( ! $container.length ) {
			return;
		}
		if ( $container.is( ':visible' ) ) {
			if ( $fields.parent().is( $target ) ) {
				$fields.prependTo( $container );
			}
		} else {
			if ( $fields.parent().is( $container ) ) {
				$fields.prependTo( $target );
			}
		}
	}
})();

// as chosen doesn't initialize on most (if not all) mobile devices
// we test if it is enabled by actually trying to initialize it on a dummy select
// instead of duplicating the function used to disable it on mobile devices
// which can always change
function isChosenEnabled() {
	var isEnabled = false;

	if ( ! $.fn.chosen ) {
		return false;
	}

	var $container = $( '<div>' );
	var $select = $( '<select>' );

	$select.appendTo( $container );
	$select.chosen();

	if ( $container.find( '.chosen-container' ).length ) {
		isEnabled = true;

		$select.chosen( "destroy" );
	}

	$container.remove();

	return isEnabled;
}
