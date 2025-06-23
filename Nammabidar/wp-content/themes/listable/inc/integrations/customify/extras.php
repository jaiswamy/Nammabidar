<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'customize_register', 'listabe_adjust_customizer_settings', 35 );
add_action( 'customize_preview_init', 'listable_border_opacity_callback_customizer_preview', 20 );
add_action( 'customize_preview_init', 'listable_update_header_height_customizer_preview', 11 );

if ( ! function_exists( 'isLight' ) ) {
	function isLight( $color = '#ffffff' ) {
		// Get our color
		$color = ( $color ) ? $color : '#ffffff';
		// Calculate straight from rbg
		$r = hexdec( $color[0] . $color[1] );
		$g = hexdec( $color[2] . $color[3] );
		$b = hexdec( $color[4] . $color[5] );

		return ( ( $r * 299 + $g * 587 + $b * 114 ) / 1000 > 90 );
	}
}

if ( ! function_exists( 'listable_color_darken' ) ) {
	function listable_color_darken( $color, $dif = 20 ) {

		$color = str_replace( '#', '', $color );
		if ( strlen( $color ) != 6 ) {
			return '000000';
		}
		$rgb = '';

		for ( $x = 0; $x < 3; $x ++ ) {
			$c   = hexdec( substr( $color, ( 2 * $x ), 2 ) ) - $dif;
			$c   = ( $c < 0 ) ? 0 : dechex( $c );
			$rgb .= ( strlen( $c ) < 2 ) ? '0' . $c : $c;
		}

		return '#' . $rgb;
	}
}

if ( ! function_exists( 'listable_customify_darker_callback' ) ) {
	function listable_customify_darker_callback( $value, $selector, $property, $unit ) {
		$darkenValue = 30;
		if ( $value == '#ffffff' ) {
			$darkenValue = 6;
		} // #FFFFFF -> #F9F9F9
		$output = $selector . '{' . $property . ': ' . listable_color_darken( $value, $darkenValue ) . '}';

		return $output;
	}
}

/**
 * Use this function to move or reorder options between sections
 *
 * @param $wp_customize
 */
function listabe_adjust_customizer_settings( $wp_customize ) {

	// move the `logo_invert` option to the title_tagline section(just to keep the well grouped)
	$logo_invert = $wp_customize->get_control( 'listable_options[logo_invert]_control' );

	if ( ! empty( $logo_invert ) ) {
		$logo_invert->section  = 'title_tagline';
		$logo_invert->priority = 9;
	}
}

if ( ! function_exists( 'listable_css_important_callback' ) ) {
	function listable_css_important_callback( $value, $selector, $property, $unit ) {
		$output = $selector . '{' . $property . ': ' . $value . $unit . ' !important }';

		return $output;
	}
}

if ( ! function_exists( 'listable_border_opacity_callback' ) ) {
	function listable_border_opacity_callback( $value, $selector, $property, $unit ) {
		// css3 colors support transparency in hex: #RRGGBBAA
		// 33 is hex for 25 ~ 20% of 255
		$output = $selector . '{' . $property . ': ' . $value . '33 }';

		return $output;
	}
}
if ( ! function_exists( 'listable_border_opacity_callback_customizer_preview' ) ) {
	/**
	 * Outputs the inline JS code used in the Customizer for the aspect ratio live preview.
	 */
	function listable_border_opacity_callback_customizer_preview() {

		$js = "
		    function makeSafeForCSS(name) {
                return name.replace(/[^a-z0-9]/g, function(s) {
                    var c = s.charCodeAt(0);
                    if (c == 32) return '-';
                    if (c >= 65 && c <= 90) return '_' + s.toLowerCase();
                    return '__' + ('000' + c.toString(16)).slice(-4);
                });
            }

            String.prototype.hashCode = function() {
                var hash = 0, i, chr;

                if ( this.length === 0 ) return hash;

                for (i = 0; i < this.length; i++) {
                    chr   = this.charCodeAt(i);
                    hash  = ((hash << 5) - hash) + chr;
                    hash |= 0; // Convert to 32bit integer
                }
                return hash;
            };

			function listable_border_opacity_callback( value, selector, property, unit ) {

			    var css = '',
			        id = 'listable_border_opacity_callback_style_tag_' + makeSafeForCSS( property + selector ).hashCode();
			        style = document.getElementById(id),
			        head = document.head || document.getElementsByTagName('head')[0];

			    css += selector + ' {' +
			        property + ': ' + value + '33' +
		        '}';

			    if ( style !== null ) {
			        style.innerHTML = css;
			    } else {
			        style = document.createElement('style');
			        style.setAttribute('id', id);

			        style.type = 'text/css';
			        if ( style.styleSheet ) {
			            style.styleSheet.cssText = css;
			        } else {
			            style.appendChild(document.createTextNode(css));
			        }

			        head.appendChild(style);
			    }
			}" . PHP_EOL;

		wp_add_inline_script( 'customify-previewer-scripts', $js );
	}
}

if ( ! function_exists( 'listable_update_header_height' ) ) {
	function listable_update_header_height( $value, $selector, $property, $unit ) {
		$output = $selector . '{' . $property . ': ' . $value . $unit . '}';

		return $output;
	}
}

if ( ! function_exists( 'listable_update_header_height_customizer_preview' ) ) {
	function listable_update_header_height_customizer_preview() {
		/**
		 * The WP-Job-Manager ajax requests capture this output for some reason, which is wrong
		 * But also WP-Job-Manager defines the DOING_AJAX constant too late for the customizer preview hook
		 * As solution we can check the request uri if it contains a `/jm-ajax/`. It is definitely a job manager ajax request
		 */
		if ( strpos( $_SERVER['REQUEST_URI'], '/jm-ajax/' ) !== false ) {
			return;
		}

		$js = '
		function listable_update_header_height( value, selector, property, unit ) {
		
            ( function( $ ) {
                $( selector ).css( property, value + unit );

                var $header = $( ".site-header" );
                var headerPaddingBottom = parseInt( $header.css( "paddingTop" ) ) + $( ".secondary-menu" ).outerHeight();

                $header.css( "paddingBottom", headerPaddingBottom );

                var $updatable = $( ".page-listings .js-header-height-padding-top" ),
                    $map = $( ".map" ),
                    $jobFilters = $( " .job_filters .search_jobs div.search_location" ),
                    $findMeButton = $( ".findme" ),
                    headerHeight = $header.outerHeight();

                // set padding top to certain elements which is equal to the header height
                $updatable.css( "paddingTop", "" );
                $updatable.css( "paddingTop", headerHeight );

                if ( $( "#wpadminbar" ).length ) {
                    headerHeight += $( "#wpadminbar" ).outerHeight();
                }

                $map.css( "top", headerHeight );
                $jobFilters.css( "top", headerHeight );
                $findMeButton.css( "top", headerHeight + 70 );
            
            } )( jQuery );
            
        }' . PHP_EOL;

		wp_add_inline_script( 'customify-previewer-scripts', $js );
	}
}
