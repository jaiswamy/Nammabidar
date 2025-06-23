<?php
/**
 * Load various logic for specific migrations.
 */

namespace Pixelgrade\PixelgradeCare\Migrations;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the logic that keeps in sync certain versions of Rosa2+Style Manager+Nova Blocks.
 */
require plugin_dir_path( __FILE__ ) . '/sync-versions_rosa2-style_manager-nova_blocks.php';
$theThreeMusketeers = new SyncTheThreeMusketeers();
$theThreeMusketeers->init();
