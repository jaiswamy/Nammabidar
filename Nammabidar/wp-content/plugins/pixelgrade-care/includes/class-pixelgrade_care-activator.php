<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Fired during plugin activation
 *
 * @link       https://pixelgrade.com
 * @since      1.0.0
 *
 * @package    PixelgradeCare
 * @subpackage PixelgradeCare/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    PixelgradeCare
 * @subpackage PixelgradeCare/includes
 * @author     Pixelgrade <help@pixelgrade.com>
 */
class PixelgradeCareActivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		if ( defined( 'PIXELGRADE_ASSISTANT__PLUGIN_FILE' ) && class_exists( 'PixelgradeAssistant' ) ) {
			deactivate_plugins( plugin_basename( PIXELGRADE_ASSISTANT__PLUGIN_FILE ) );
		}

		// Also reset theme updates transients to be sure that any logic introduced by the plugin can kick in.
		delete_site_transient( 'update_themes' );
	}
}
