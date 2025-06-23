<?php
/**
 * Handle the plugin's behavior when NovaBlocks is present.
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pixcare_novablocks_setup' ) ) {
	function pixcare_novablocks_setup() {
		if ( ! function_exists( 'novablocks_plugin_setup' ) ) {
			return;
		}

		// Get the PixelgradeCare configuration.
		$pixcare_config = PixelgradeCare_Admin::get_theme_config();
		if ( empty( $pixcare_config['novablocks']['blocks'] ) || ! is_array( $pixcare_config['novablocks']['blocks'] ) ) {
			return;
		}

		/**
		 * Apply the configured theme_support for NovaBlocks.
		 */
		$theme_support = [];
		foreach ( $pixcare_config['novablocks']['blocks'] as $block_config_id => $block_config ) {
			if ( ! is_array( $block_config ) || empty( $block_config['name'] ) ) {
				continue;
			}

			// A little safety.
			$block_config['name'] = _sanitize_text_fields( $block_config['name'] );

			// Blocks are enabled by default.
			if ( ! isset( $block_config['enabled'] ) ) {
				$block_config['enabled'] = true;
			}

			$theme_support[ $block_config_id ] = $block_config;
		}

		ksort( $theme_support );

		// Get any current theme_support and standardize it.
		$current_theme_support = get_theme_support( 'novablocks' );
		if ( empty( $current_theme_support ) ) {
			$current_theme_support = [];
		}
		if ( is_array( $current_theme_support ) && is_array( $current_theme_support[0] ) ) {
			$current_theme_support = $current_theme_support[0];
		}

		if ( function_exists( 'novablocks_normalize_theme_support' ) ) {
			$current_theme_support = novablocks_normalize_theme_support( $current_theme_support );
		} else {
			foreach ( $current_theme_support as $key => $value ) {
				if ( is_numeric( $key ) && is_string( $value ) ) {
					// We have a shorthand block name.
					$current_theme_support[ $value ] = [
						'name'     => $value,
						'enabled'  => true,
						'supports' => [],
					];
					unset( $current_theme_support[ $key ] );
					continue;
				}

				if ( is_string( $key ) && is_bool( $value ) ) {
					// Another shorthand block name.
					$current_theme_support[ $key ] = [
						'name'     => $key,
						'enabled'  => $value,
						'supports' => [],
					];
					continue;
				}

				if ( is_array( $value ) && ! empty( $value['name'] ) ) {
					if ( is_numeric( $key ) ) {
						$current_theme_support[ $value['name'] ] = $value;
						unset( $current_theme_support[ $key ] );

						$key = $value['name'];
					}

					$current_theme_support[ $key ] = array_merge( [
						'name'     => '',
						'enabled'  => true,
						'supports' => [],
					], $current_theme_support[ $key ] );

					continue;
				}
			}
			ksort( $current_theme_support );
		}

		// Now merge into any existing theme_support.
		$new_theme_support = PixelgradeCare_Admin::array_merge_recursive_distinct( $current_theme_support, $theme_support );

		ksort( $new_theme_support );

		/**
		 * Filters the modified 'novablocks' theme support before evaluating conditions.
		 *
		 * @param array $new_theme_support
		 * @param array $pixcare_theme_support
		 * @param array $old_theme_support This has been standardized.
		 *
		 */
		$new_theme_support = apply_filters( 'pixcare/novablocks/raw_theme_support', $new_theme_support, $theme_support, $current_theme_support );

		// Evaluate any conditions that may be present.
		require_once dirname( plugin_dir_path( __FILE__ ) ) . '/lib/class-pixelgrade_care-conditions.php';
		foreach ( $new_theme_support as $key => $block_config ) {
			if ( empty( $block_config['conditions'] ) ) {
				continue;
			}

			// Evaluate the conditions.
			// On a false result we remove the block config.
			if ( ! $block_config['enabled'] || ! PixelgradeCare_Conditions::process( $block_config['conditions'] ) ) {
				unset( $new_theme_support[ $key ] );
			} else {
				// We remove the conditions as they are not needed anymore.
				unset( $new_theme_support[ $key ]['conditions'] );
			}
		}

		/**
		 * Filters the modified 'novablocks' theme support before setting it.
		 *
		 * @param array $new_theme_support
		 * @param array $pixcare_theme_support
		 * @param array $old_theme_support This has been standardized.
		 *
		 */
		$new_theme_support = apply_filters( 'pixcare/novablocks/theme_support', $new_theme_support, $theme_support, $current_theme_support );

		// Save the new theme_support.
		add_theme_support( 'novablocks', $new_theme_support );
	}
}
// Go with a 12 priority to come after themes, but not too late.
add_action( 'after_setup_theme', 'pixcare_novablocks_setup', 12 );

if ( ! function_exists( 'pixcare_alter_novablocks_settings' ) ) {
	function pixcare_alter_novablocks_settings( $settings ) {
		// Get the PixelgradeCare configuration.
		$pixcare_config = PixelgradeCare_Admin::get_theme_config();

		/**
		 * Apply the block editor settings changes for NovaBlocks.
		 */
		if ( ! empty( $pixcare_config['novablocks']['blockEditorSettings'] ) && is_array( $pixcare_config['novablocks']['blockEditorSettings'] ) ) {
			// First, process recursively any dynamic values that might be configured.
			require_once dirname( plugin_dir_path( __FILE__ ) ) . '/lib/class-pixelgrade_care-dynamicvalues.php';
			$blockeditor_settings = $pixcare_config['novablocks']['blockEditorSettings'];
			PixelgradeCare_DynamicValues::process( $blockeditor_settings );

			// Now merge into existing settings.
			$settings = PixelgradeCare_Admin::array_merge_recursive_distinct( $settings, $blockeditor_settings );
		}

		return $settings;
	}
}
add_filter( 'novablocks_block_editor_settings', 'pixcare_alter_novablocks_settings', 12 );
