<?php
/**
 * Handle the plugin's behavior when Style Manager is present.
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add customer data to Style Manager cloud request data.
 *
 * @param array $request_data
 *
 * @return array
 */
function pixcare_add_customer_data_to_style_manager_cloud_request_data( $request_data ) {
	// Get the connected pixelgrade user id
	$connection_user = PixelgradeCare_Admin::get_theme_activation_user();
	if ( empty( $connection_user ) || empty( $connection_user->ID ) ) {
		return $request_data;
	}

	$user_id = get_user_meta( $connection_user->ID, 'pixcare_user_ID', true );
	if ( empty( $user_id ) ) {
		// not authenticated
		return $request_data;
	}

	if ( empty( $request_data['customer_data'] ) ) {
		$request_data['customer_data'] = [];
	}
	$request_data['customer_data']['id'] = absint( $user_id );

	return $request_data;
}
add_filter( 'style_manager/pixelgrade_cloud_request_data', 'pixcare_add_customer_data_to_style_manager_cloud_request_data', 10, 1 );

/**
 * Add site data to Style Manager cloud request data.
 *
 * @param array $site_data
 *
 * @return array
 */
function pixcare_add_site_data_to_style_manager_cloud_request_data( $site_data ) {
	if ( empty( $site_data['wp'] ) ) {
		$site_data['wp'] = [];
	}

	$site_data['wp']['language'] = get_bloginfo('language');
	$site_data['wp']['rtl'] = is_rtl();

	return $site_data;
}
add_filter( 'style_manager/get_site_data', 'pixcare_add_site_data_to_style_manager_cloud_request_data', 10, 1 );

if ( ! function_exists( 'pixcare_add_cloud_stats_endpoint' ) ) {
	function pixcare_add_cloud_stats_endpoint( $config ) {
		if ( empty( $config['cloud']['stats'] ) ) {
			$config['cloud']['stats'] = [
				'method' => 'POST',
				'url'    => PIXELGRADE_CLOUD__API_BASE . 'wp-json/pixcloud/v1/front/stats',
			];
		}

		return $config;
	}
}
add_filter( 'style_manager/external_api_endpoints', 'pixcare_add_cloud_stats_endpoint', 10, 1 );
