<?php
/**
 * This is where we load all the various theme helpers.
 *
 * @link       https://pixelgrade.com
 * @since      1.2.2
 *
 * @package    PixelgradeCare
 * @subpackage PixelgradeCare/ThemeHelpers
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Load our extras functions
 */
require_once( plugin_dir_path( __FILE__ ) . 'theme-helpers/extras.php' );

/*
 * Load our helper shortcodes
 */
require_once( plugin_dir_path( __FILE__ ) . 'theme-helpers/shortcodes.php' );

/*
 * Load our Jetpack settings customization helper class
 */
require_once( plugin_dir_path( __FILE__ ) . 'theme-helpers/jetpack_customization.php' );

/*
 * Load our affiliates related functionality
 */
require_once( plugin_dir_path( __FILE__ ) . 'theme-helpers/affiliates.php' );

/*
 * Load our Customizer helper class
 */
require_once( plugin_dir_path( __FILE__ ) . 'theme-helpers/customizer_helper.php' );

/*
 * Load our theme dependent functionality
 */
require_once( plugin_dir_path( __FILE__ ) . 'theme-helpers/theme-dependent.php' );

/*
 * Load our theme support customization helper class
 */
require_once( plugin_dir_path( __FILE__ ) . 'theme-helpers/theme_support.php' );
