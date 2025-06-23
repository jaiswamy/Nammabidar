<?php
/**
 * The affiliate logic for HubSpot.
 *
 * @link       https://pixelgrade.com
 * @since      1.4.9.1
 *
 * @package    PixelgradeCare
 * @subpackage PixelgradeCare/ThemeHelpers
 */

/**
 * Set the HubSpot ID.
 */
function pixcare_put_hubspot_id() {

	update_option( 'hubspot_affiliate_code', 'http://mbsy.co/qh2d8' );
}
register_activation_hook( PIXELGRADE_CARE__PLUGIN_FILE, 'pixcare_put_hubspot_id' );
