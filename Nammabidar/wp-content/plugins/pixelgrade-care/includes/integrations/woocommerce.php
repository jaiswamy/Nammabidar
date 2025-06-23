<?php
/**
 * Handle the plugin's behavior when WooCommerce is present.
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pixcare_disable_wc_setup_redirect_in_wizard( $disable_wc_setup ) {
	if ( ! empty( $_GET['page'] ) && in_array($_GET['page'], [ 'pixelgrade_care-setup-wizard', 'install-required-plugins' ] ) ) {
		$disable_wc_setup = true;
	}

	return $disable_wc_setup;
}
add_filter( 'woocommerce_prevent_automatic_wizard_redirect', 'pixcare_disable_wc_setup_redirect_in_wizard', 10, 1 );
