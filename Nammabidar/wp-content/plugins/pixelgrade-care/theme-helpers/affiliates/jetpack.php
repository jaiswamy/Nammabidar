<?php
/**
 * The affiliate logic for Jetpack.
 *
 * @link       https://pixelgrade.com
 * @since      1.5.3
 *
 * @package    PixelgradeCare
 * @subpackage PixelgradeCare/ThemeHelpers
 */

/**
 * Set the Jetpack ID.
 */
function pixcare_put_jetpack_id( $id ) {

	$id = '20740';

	return $id;
}
add_filter( 'jetpack_affiliate_code', 'pixcare_put_jetpack_id', 999, 1 );
