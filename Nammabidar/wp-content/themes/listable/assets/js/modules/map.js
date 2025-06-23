if ( $( '#map' ).length && typeof L === "object" ) {
	// set Leaflet's default path for images
	L.Icon.Default.imagePath = 'wp-content/themes/listable/assets/img/';
}

// Map module
var Map = (
	function () {
		// create a custom icon class that can be extended for each listing category

		var map, markers, CustomHtmlIcon;

		// initialization - check wether we are on the archive page or on a single listing
		function init() {

			if ( $( '.no_job_listings_found' ).length ) {
				$( '<div class="results">' + listable_params.strings['no_job_listings_found'] + '</div>' ).prependTo( '.showing_jobs, .search-query' );
			}

			if ( ! $( '#map' ).length ) {
				$( '#main .job_listings' ).on( 'updated_results', function ( e, result ) {
					updateCards( result.total_found );
				} );
				return;
			}

			if ( typeof L !== "object" || ! L.hasOwnProperty( 'map' ) ) {
				return;
			}

			map = L.map( 'map', {scrollWheelZoom: false} );
			markers = new L.MarkerClusterGroup( {showCoverageOnHover: false} );
			CustomHtmlIcon = L.HtmlIcon.extend( {
				options: {
					html: "<div class='pin'></div>",
					iconSize: [48, 59], // size of the icon
					iconAnchor: [24, 59], // point of the icon which will correspond to marker's location
					popupAnchor: [0, - 59] 	// point from which the popup should open relative to the iconAnchor
				}
			} );

			$window.on( 'pxg:refreshmap', function () {
				map._onResize();
			} );

			var tileLayer,
				mapboxToken = $( 'body' ).data( 'mapbox-token' ),
				mapboxStyle = $( 'body' ).data( 'mapbox-style' );

			if ( ! empty( mapboxToken ) ) {
				tileLayer = L.tileLayer( 'https://api.mapbox.com/styles/v1/pixelgrade/' + mapboxStyle + '/tiles/{z}/{x}/{y}?access_token=' + mapboxToken, {
					tileSize: 512,
					zoomOffset: -1,
					maxZoom: listable_params.mapbox.maxZoom,
					attribution: '&copy; <a href="http://mapbox.com">Mapbox</a> | &copy; <a href="http://openstreetmap.org">OpenStreetMap</a>',
				} )
			} else {
				tileLayer = L.gridLayer.googleMutant( {
					type: 'roadmap'
				} );
				$( '#map' ).addClass( 'map--google' );
			}

			map.addLayer( tileLayer );

			// if we are on the archive page (#map is not a single listing's map) :D
			// @todo do do doom
			if ( ! $( '#map' ).is( '.listing-map' ) ) {
				$( '#main .job_listings' ).on( 'updated_results', function ( e, result ) {
					updateCards( result.total_found );
				} );

				//This one is for FacetWP
				$( document ).on( 'facetwp-loaded', function ( e, result ) {
					updateCards();
				} );
			} else {
				var $item = $( '.single_job_listing' );
				// add only one marker if we're on the single listing page
				if ( typeof $item.data( 'latitude' ) !== "undefined" && typeof $item.data( 'longitude' ) !== "undefined" ) {

					var zoom = (
						typeof MapWidgetZoom !== "undefined"
					) ? MapWidgetZoom : 13;

					addPinToMap( $item );
					map.addLayer( markers );
					map.setActiveArea( 'active-area' );
					map.setView( [$item.data( 'latitude' ), $item.data( 'longitude' )], zoom );
					$( window ).on( 'update:map', function () {
						map.setView( [$item.data( 'latitude' ), $item.data( 'longitude' )], zoom );
					} );
				} else {
					$( '#map' ).hide();
					$( '.listing-address' ).css( 'marginTop', 0 );
				}
			}

			$( '.js-find-me' ).on( 'click', function ( e ) {
				e.preventDefault();
				e.stopPropagation();
				map.locate( {setView: true, maxZoom: listable_params.mapbox.maxZoom} );
			} );
		}

		function updateCards( $total_found ) {

			var $cards = $( '#main .card' );
			var cardsWithLocation = 0;
			var clusterMarkup = document.querySelector('.cluster-icon-svg' ).innerHTML;

			if ( ! $cards.length ) {
				$( 'body' ).addClass( 'has-no-listings' );
				defaultMapView();
				return;
			}

			//first some cleanup to avoid multiple results being shown - it happens
			$( '.showing_jobs .results' ).remove();

			if ( typeof $total_found !== 'undefined' ) {
				//someone must have blessed us with higher knowledge
				//let's not let it go to waste
				$( '<div class="results"><span class="results-no">' + $total_found + '</span> ' + listable_params.strings['results-no'] + '</div>' ).prependTo( '.showing_jobs, .search-query' );
			} else {
				$( '<div class="results"><span class="results-no">' + $cards.length + '</span> ' + listable_params.strings['results-no'] + '</div>' ).prependTo( '.showing_jobs, .search-query' );
			}

			if ( $( '.map' ).length && typeof map !== "undefined" ) {
				map.removeLayer( markers );
				markers = new L.MarkerClusterGroup( {
					showCoverageOnHover: false,
					spiderfyDistanceMultiplier: 3,
					spiderLegPolylineOptions: { weight: 0 },
					iconCreateFunction: function( cluster ) {
						return new L.DivIcon( {
							html: clusterMarkup + '<span>' + cluster.getChildCount() + '</span>',
							className: 'marker-cluster',
							iconSize: new L.Point(40, 40) });
					}
				} );
				$cards.each( function ( i, obj ) {
					var cardHasLocation = addPinToMap( $( obj ), true );
					if ( cardHasLocation ) {
						cardsWithLocation += 1;
					}
				} );

				if (cardsWithLocation != 0) {
					map.fitBounds(markers.getBounds(), {padding: [50, 50]});
					map.addLayer(markers);

					var mapZoom = map.getZoom();
					var bounds = markers.getBounds();
					var lat = ( bounds._northEast.lat + bounds._southWest.lat ) / 2;
					var lng = ( bounds._northEast.lng + bounds._southWest.lng ) / 2;
					bounds = [lat, lng];

					Cookies.set('pxg-listable-bounds', JSON.stringify(bounds));
					Cookies.set('pxg-listable-mapZoom', mapZoom);
				} else {
					defaultMapView();
				}
			}
		}

		function addPinToMap( $item, archive ) {
			var categories = $item.data( 'categories' ),
				iconClass, m;

			if ( empty( $item.data( 'latitude' ) ) || empty( $item.data( 'longitude' ) ) ) {
				return false;
			}

			if ( typeof categories !== "undefined" && ! categories.length ) {
				iconClass = 'pin pin--empty';
			} else {
				iconClass = 'pin';
			}

			var $icon = $( '.selected-icon-svg' ),
				$tags = $item.find( '.card__tag' ),
				$categories = $item.find( '.category-icon' ),
				$tag, iconHTML = "<div class='" + iconClass + "'>" + $( '.empty-icon-svg' ).html() + "</div>";

			if ( $body.is( '.single-job_listing' ) ) {
				// If we are on a single listing
				if ( $( '.single-listing-map-category-icon' ).length ) {
					iconHTML = "<div class='" + iconClass + "'>" + $icon.html() + "<div class='pin__icon'>" + $( '.single-listing-map-category-icon' ).html() + "</div></div>";
				}
			} else if ( $tags.length ) {
				$tag = $tags.first();
				iconHTML = "<div class='" + iconClass + "'>" + $icon.html() + $tag.html() + "</div>";
			} else if ( $categories.length ) {
				iconHTML = "<div class='" + iconClass + "'>" + $icon.html() + "<div class='pin__icon'>" + $categories.html() + "</div></div>";
			}

			m = L.marker( [$item.data( 'latitude' ), $item.data( 'longitude' )], {
				icon: new CustomHtmlIcon( {
					html: iconHTML
				} )
			} );

			if ( typeof archive !== "undefined" ) {

				$item.hover( function () {
					$( m._icon ).find( '.pin' ).addClass( 'pin--selected' );
				}, function () {
					$( m._icon ).find( '.pin' ).removeClass( 'pin--selected' );
				} );

				var rating = $item.find( '.js-average-rating' ).text(),
					ratingHTML = rating.length ? "<div class='popup__rating'><span>" + rating + "</span></div>" : "",
					address = $item.find( '.card__address' ).text();

				m.bindPopup(
					"<a class='popup' href='" + $item.data( 'permalink' ) + "'>" +
					"<div class='popup__image' style='background-image: url(" + $item.data( 'img' ) + ");'></div>" +
					"<div class='popup__content'>" +
					"<h3 class='popup__title'>" + $item.find( '.card__title' ).html() + "</h3>" +
					"<div class='popup__footer'>" +
					ratingHTML +
					"<div class='popup__address'>" + $item.find( '.card__address' ).html() + "</div>" +
					"</div>" +
					"</div>" +
					"</a>" ).openPopup();
			}

			markers.addLayer( m );

			return true;
		}

		function defaultMapView() {
			var defaultLocation = $( 'body' ).data( 'map-default-location' ),
				defaultCoordinates = [51.5073509, -0.12775829999998223];

			if ( typeof defaultLocation !== "undefined" ) {
				defaultCoordinates = defaultLocation.split( ',' );
				defaultCoordinates = defaultCoordinates.map( function callback( currentValue ) {
					return parseFloat( currentValue );
				} );
			}

			map.removeLayer( markers );
			map.setView( defaultCoordinates, 9 );
		}

		return {
			init: init,
			updateResults: updateCards
        }
	}
)();
