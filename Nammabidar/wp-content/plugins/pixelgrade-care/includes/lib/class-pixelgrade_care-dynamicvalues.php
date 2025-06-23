<?php

/**
 *
 * A class to handle dynamic values in configuration array.
 */
class PixelgradeCare_DynamicValues {

	/**
	 * Process a configuration array and replace any dynamic values with the resulting value.
	 *
	 * @param array $config
	 */
	public static function process( &$config ) {
		if ( ! is_array( $config ) ) {
			return;
		}

		foreach ( $config as &$value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}

			if ( ! empty( $value['type'] ) && 'dynamicValue' === $value['type'] ) {
				$value = self::process_value( $value );
			} else {
				self::process( $value );
			}
		}
	}



	/**
	 * Process and return a dynamic value.
	 *
	 * @param mixed $value
	 *
	 * @return mixed|null null is return on anything invalid.
	 */
	public static function process_value( $value ) {
		// If we have no valid source, return null.
		if ( ! isset( $value['source'] ) ||
		    ! in_array( $value['source'], [ 'callback', 'pixelgradeOption', 'option', 'themeMod', 'constant' ] ) ) {
			return null;
		}

		if ( ! method_exists( __CLASS__, 'get_' . $value['source'] ) ) {
			return null;
		}

		return call_user_func( [ __CLASS__, 'get_' . $value['source'] ], $value );
	}

	/* ========================
	 * THE DYNAMIC VALUES GETTERS
	 */

	public static function get_option( $value = null ) {
		$default = false;
		if ( isset( $value['default'] ) ) {
			$default = $value['default'];
		}

		return get_option( _sanitize_text_fields( $value['option'] ), $default );
	}

	public static function get_themeMod( $value = null ) {
		$default = false;
		if ( isset( $value['default'] ) ) {
			$default = $value['default'];
		}

		return get_theme_mod( _sanitize_text_fields( $value['option'] ), $default );
	}

	public static function get_pixelgradeOption( $value = null ) {
		$default = false;
		if ( isset( $value['default'] ) ) {
			$default = $value['default'];
		}

		if ( ! function_exists( 'pixelgrade_option' ) ) {
			return $default;
		}

		return pixelgrade_option( _sanitize_text_fields( $value['option'] ), $default );
	}

	public static function get_callback( $value = null ) {
		if ( ! is_callable( $value['callable'] ) ) {
			return false;
		}

		if ( isset( $value['args'] ) ) {
			return call_user_func( $value['callable'], $value['args'] );
		}

		return call_user_func( $value['callable'] );
	}

	public static function get_constant( $value = null ) {
		$default = false;
		if ( isset( $value['default'] ) ) {
			$default = $value['default'];
		}

		if ( ! defined( $value['constant'] ) ) {
			return $default;
		}

		return constant( _sanitize_text_fields( $value['constant'] ) );
	}
}
