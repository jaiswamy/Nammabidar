<?php defined( 'ABSPATH' ) or die;

/**
 * @package    Pixelgrade Care Metafields
 * @category   core
 * @author     Pixelgrade Team
 */
class PixelgradeCare_MetafieldsHTMLTagImpl implements PixelgradeCare_MetafieldsHTMLTag {

	/** @var array html attributes */
	protected $attrs = null;

	/**
	 * @param array config
	 */
	static function instance( $config = null ) {
		$i = new self;
		$i->configure( $config );

		return $i;
	}

	/**
	 * Apply configuration.
	 */
	protected function configure( $config = null ) {
		$this->attrs = PixelgradeCare_MetafieldsCore::instance( 'PixelgradeCare_MetafieldsMeta', $config );
	}

	/**
	 * @param string key
	 * @param mixed default
	 *
	 * @return mixed
	 */
	function get( $key, $default = null ) {
		return $this->attrs->get( $key, $default );
	}

	/**
	 * @param string key
	 * @param mixed value
	 *
	 * @return static $this
	 */
	function set( $key, $value ) {
		$this->attrs->set( $key, $value );

		return $this;
	}

	/**
	 * @return string attributes
	 */
	function htmlattributes( array $extra = [] ) {
		$attr_segments = [];
		$attributes    = PixelgradeCare_MetafieldsCore::merge( $this->attrs->metadata_array(), $extra );
		foreach ( $attributes as $key => $value ) {
			if ( $value !== false && $value !== null ) {
				if ( ! empty( $value ) ) {
					if ( is_array( $value ) ) {
						$htmlvalue       = implode( ' ', $value );
						$attr_segments[] = "$key=\"$htmlvalue\"";
					} else { // value is not an array
						$attr_segments[] = "$key=\"$value\"";
					}
				} else { // empty html tag; ie. no value html tag
					$attr_segments[] = $key;
				}
			}
		}

		return implode( ' ', $attr_segments );
	}

} # class
