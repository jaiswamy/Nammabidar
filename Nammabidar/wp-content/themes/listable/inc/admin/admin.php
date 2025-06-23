<?php
/**
 * Listable Theme admin dashboard logic.
 *
 * @package Listable
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function listable_admin_setup() {

	/**
	 * Load and initialize Pixelgrade Care notice logic.
	 */
	if ( ! class_exists( 'PixelgradeCare_Install_Notice' ) ) {
		require_once 'pixcare-notice/class-notice.php'; // phpcs:ignore
	}
	PixelgradeCare_Install_Notice::init();
}
add_action( 'after_setup_theme', 'listable_admin_setup' );
