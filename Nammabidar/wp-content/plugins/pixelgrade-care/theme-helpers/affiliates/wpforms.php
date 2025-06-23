<?php
/**
 * The affiliate logic for WPForms.
 *
 * @link       https://pixelgrade.com
 * @since      1.4.7
 *
 * @package    PixelgradeCare
 * @subpackage PixelgradeCare/ThemeHelpers
 */

/**
 * Set the WPForms ShareASale ID.
 *
 * @param string $shareasale_id The the default ShareASale ID.
 *
 * @return string $shareasale_id
 */
function pixcare_wpforms_shareasale_id( $shareasale_id ) {

	// If this WordPress installation already has an WPForms ShareASale ID specified, use that.
	if ( ! empty( $shareasale_id ) ) {
		return $shareasale_id;
	}

	// Define our ShareASale ID to use.
	$shareasale_id = '1843354';

	// This WordPress installation doesn't have an ShareASale ID specified, so
	// set the default ID in the WordPress options and use that.
	update_option( 'wpforms_shareasale_id', $shareasale_id );

	// Return the ShareASale ID.
	return $shareasale_id;
}
add_filter( 'wpforms_shareasale_id', 'pixcare_wpforms_shareasale_id', 10, 1 );
