<?php
/**
 * Handle the plugin's behavior when in lonely mode (no license check).
 *
 * This means that the authentication module is disabled, the support, club (themes) and data gathering modules are disabled.
 * The customer has access to demo data and updates.
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine if we are in lonely mode.
 *
 * @return bool
 */
function pixcare_is_lonely_mode() {
	if ( defined('PIXELGRADE_CARE_LONELY' ) && false !== PIXELGRADE_CARE_LONELY ) {
		return true;
	}

	if ( current_theme_supports('pixelgrade_care_lonely') ) {
		return true;
	}

	return false;
}

function pixcare_lonely_mode_activate_pro_features() {
	if ( pixcare_is_lonely_mode() ) {
		// The priority should be greater than the one in pixelgrade_maybe_enable_theme_features().
		add_filter( 'pixelgrade_enable_pro_features', '__return_true', 999999 );
	}
}
add_action( 'after_setup_theme', 'pixcare_lonely_mode_activate_pro_features', 0 );

/**
 * Do not initialize the club module when in lonely mode.
 *
 * @param bool $enqueue
 *
 * @return bool
 */
function pixcare_lonely_mode_disable_club_module( $enqueue ) {
	if ( pixcare_is_lonely_mode() ) {
		$enqueue = false;
	}

	return $enqueue;
}
add_filter( 'pixcare_allow_club_module', 'pixcare_lonely_mode_disable_club_module', 10, 1 );

/**
 * Do not show the Themes page when in lonely mode.
 *
 * @param bool $allow
 *
 * @return bool
 */
function pixcare_lonely_mode_disable_themes_page( $allow ) {
	if ( pixcare_is_lonely_mode() ) {
		$allow = false;
	}

	return $allow;
}
add_filter( 'pixcare_allow_themes_page', 'pixcare_lonely_mode_disable_themes_page', 10, 1 );

/**
 * Do not initialize the data collector when in lonely mode.
 *
 * @param bool $allow
 *
 * @return bool
 */
function pixcare_lonely_mode_disable_data_collector_module( $allow ) {
	if ( pixcare_is_lonely_mode() ) {
		$allow = false;
	}

	return $allow;
}
add_filter( 'pixcare_allow_data_collector_module', 'pixcare_lonely_mode_disable_data_collector_module', 10, 1 );

/**
 * Do not initialize the support module when in lonely mode.
 *
 * @param bool $allow
 *
 * @return bool
 */
function pixcare_lonely_mode_disable_support_module( $allow ) {
	if ( pixcare_is_lonely_mode() ) {
		$allow = false;
	}

	return $allow;
}
add_filter( 'pixcare_allow_support_module', 'pixcare_lonely_mode_disable_support_module', 10, 1 );

/**
 * Do not show update notice in the dashboard.
 *
 * @param bool $allow
 *
 * @return bool
 */
function pixcare_lonely_mode_disable_dashboard_update_notice( $allow ) {
	if ( pixcare_is_lonely_mode() ) {
		$allow = false;
	}

	return $allow;
}
add_filter( 'pixcare_allow_dashboard_update_notice', 'pixcare_lonely_mode_disable_dashboard_update_notice', 10, 1 );

/**
 * Change the pixcare localized array to fit our needs.
 *
 * @param array $localized_data
 * @param string $script_id
 *
 * @return array
 */
function pixcare_lonely_mode_configure_localized_data( $localized_data, $script_id ) {
	if ( pixcare_is_lonely_mode() ) {
		$localized_data['lonelyMode'] = true;
		// We don't want the authenticator
		unset( $localized_data['themeConfig']['dashboard']['tabs']['general']['blocks']['authenticator'] );
		// We need to show the starter content even if the user is not authenticated
		if ( isset( $localized_data['themeConfig']['dashboard']['tabs']['general']['blocks']['starterContent']['notconnected'] ) ) {
			$localized_data['themeConfig']['dashboard']['tabs']['general']['blocks']['starterContent']['notconnected'] = '';
		}

		// We need to show the starter content even if the user doesn't has an active license.
		if ( isset( $localized_data['themeConfig']['dashboard']['tabs']['general']['blocks']['starterContent']['inactive'] ) ) {
			$localized_data['themeConfig']['dashboard']['tabs']['general']['blocks']['starterContent']['inactive'] = '';
		}

		// We don't want the system status tab
		unset( $localized_data['themeConfig']['dashboard']['tabs']['system-status'] );

		// We don't want the authenticator in the setup wizard
		unset( $localized_data['themeConfig']['setupWizard']['steps']['activation'] );

		// Allow the starter content to go even if the user is not authenticated
		if ( isset( $localized_data['themeConfig']['setupWizard']['steps']['import']['blocks']['importStarterContent']['fields']['starterContent']['notconnected'] ) ) {
			$localized_data['themeConfig']['setupWizard']['steps']['import']['blocks']['importStarterContent']['fields']['starterContent']['notconnected'] = '';
		}

		// Allow the starter content to go even if no active license exists
		if ( isset( $localized_data['themeConfig']['setupWizard']['steps']['import']['blocks']['importStarterContent']['fields']['starterContent']['inactive'] ) ) {
			$localized_data['themeConfig']['setupWizard']['steps']['import']['blocks']['importStarterContent']['fields']['starterContent']['inactive'] = '';
		}
	}

	return $localized_data;
}
add_filter( 'pixcare_localized_data', 'pixcare_lonely_mode_configure_localized_data', 10, 2 );

/**
 * Add custom inline CSS to help us smooth things over.
 */
function pixcare_lonely_mode_custom_css() {
	if ( PixelgradeCare_Admin::is_pixelgrade_care_dashboard() && pixcare_is_lonely_mode() ) {
		$local_plugin = PixelgradeCare();

		$custom_css = '
                #pixelgrade_care_dashboard .header-toolbar .header-toolbar__wing--left .theme__status,  
                #pixelgrade_care_dashboard .header-toolbar .header-toolbar__wing--right {
                        display: none;
                }';
		wp_add_inline_style( $local_plugin->get_plugin_name(), $custom_css );
	}
}
add_action( 'admin_enqueue_scripts', 'pixcare_lonely_mode_custom_css', 100 );

function pixcare_lonely_mode_prevent_notification_bubble( $show_bubble ) {
	if ( pixcare_is_lonely_mode() ) {
		// We will only show the bubble for theme files messing around
		$show_bubble = false;

		$theme_checks = PixelgradeCare_Admin::get_theme_checks();
		if ( $theme_checks['has_tampered_wupdates_code'] || ! $theme_checks['has_original_name'] || ! $theme_checks['has_original_directory'] ) {
			$show_bubble = true;
		}
	}

	return $show_bubble;
}
add_filter( 'pixcare_show_menu_notification_bubble', 'pixcare_lonely_mode_prevent_notification_bubble', 10, 1 );

/**
 * Do not send license data so WUpdates will not attempt to validate the license.
 *
 * Basically, let updates pass.
 *
 * @param $data
 *
 * @return mixed
 */
function pixcare_lonely_mode_prevent_version_check_license_details( $data ) {

	if ( pixcare_is_lonely_mode() ) {
		if ( isset( $data['license_hash'] ) ) {
			unset( $data['license_hash'] );
		}
	}

	return $data;
}
add_filter( 'wupdates_call_data_request', 'pixcare_lonely_mode_prevent_version_check_license_details', 100, 1 );


/**
 * Trick the transient data so there will be no update checks for the current active theme.
 *
 * @param $transient
 *
 * @return mixed
 */
function pixcare_lonely_mode_prevent_update_check( $transient ) {

	if ( pixcare_is_lonely_mode() && PixelgradeCare_Admin::is_pixelgrade_theme() ) {
		// We will mold the transient data so the WUpdates code will not check for update.
		$slug = basename( get_template_directory() );

		unset( $transient->checked[ $slug ] );
	}

	return $transient;
}
add_filter( 'pre_set_site_transient_update_themes', 'pixcare_lonely_mode_prevent_update_check', 0, 1 );
