<?php defined( 'ABSPATH' ) or die;

/**
 * @package    Pixelgrade Care Metafields
 * @category   core
 * @author     Pixelgrade Team
 */
class PixelgradeCare_MetafieldsValidatorImpl implements PixelgradeCare_MetafieldsValidator {

	/** @var PixelgradeCare_MetafieldsMeta plugin configuration */
	protected $meta = null;

	/** @var PixelgradeCare_MetafieldsMeta field information */
	protected $fields = null;

	/**
	 * @param array config
	 */
	static function instance( $config = null, $fields = null ) {
		$i = new self;
		$i->configure( $config, $fields );

		return $i;
	}

	/**
	 * Apply configuration.
	 *
	 * Fields array is assumed to be flat. The class will not perform any field
	 * extraction itself.
	 */
	protected function configure( $config = null, $fields = null ) {
		$config !== null or $config = [];
		$fields !== null or $fields = [];

		if ( is_array( $config ) ) {
			$this->meta = PixelgradeCare_MetafieldsCore::instance( 'PixelgradeCare_MetafieldsMeta', $config );
		} else { // non-array; assume meta object
			$this->meta = $config;
		}

		if ( is_array( $fields ) ) {
			$this->fields = PixelgradeCare_MetafieldsCore::instance( 'PixelgradeCare_MetafieldsMeta', $fields );
		} else { // non-array; assume meta object
			$this->fields = $fields;
		}
	}

	/**
	 * Validation will only be performed on input keys not on all field keys to
	 * allow for partial input validation.
	 *
	 * @param array input
	 *
	 * @return array errors (empty if no errors)
	 */
	function validate( $input ) {
		$errors        = [];
		$defaults      = PixelgradeCare_MetafieldsCore::defaults();
		$plugin_checks = $this->meta->get( 'checks', [] );

		foreach ( $input as $key => $value ) {

			$field = $this->fields->get( $key );

			// Calculate validation rules
			// --------------------------

			$rules = [];
			// check pixfields defaults
			if ( isset( $defaults['checks'][ $field['type'] ] ) ) {
				$rules = $defaults['checks'][ $field['type'] ];
			}
			// check theme defaults
			if ( isset( $plugin_checks[ $field['type'] ] ) ) {
				$rules = array_merge( $rules, $plugin_checks[ $field['type'] ] );
			}
			// check field presets
			if ( isset( $field['checks'] ) ) {
				$rules = array_merge( $rules, $field['checks'] );
			}

			// Perform validation
			// ------------------

			foreach ( $rules as $rule ) {
				$callback = PixelgradeCare_MetafieldsCore::callback( $rule, $this->meta );
				$valid    = call_user_func( $callback, $input[ $key ], $field, $this );
				if ( ! $valid ) {
					isset( $errors[ $key ] ) or $errors[ $key ] = [];
					$errors[ $key ][ $rule ] = $this->error_message( $rule );
				}
			}
		}

		return $errors;
	}

	/** @var array error messages */
	protected static $error_message_cache = null;

	/**
	 * @param string rule
	 *
	 * @return string error message
	 */
	function error_message( $rule ) {
		if ( self::$error_message_cache === null ) {
			$defaults                  = PixelgradeCare_MetafieldsCore::defaults();
			$default_errors            = $defaults['errors'];
			$plugin_errors             = $this->meta->get( 'errors', [] );
			self::$error_message_cache = array_merge( $default_errors, $plugin_errors );
		}

		return self::$error_message_cache[ $rule ];
	}

} # class
