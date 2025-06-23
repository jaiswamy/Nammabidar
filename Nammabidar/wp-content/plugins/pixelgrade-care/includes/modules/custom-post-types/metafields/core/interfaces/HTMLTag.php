<?php defined( 'ABSPATH' ) or die;

/**
 * @package    Pixelgrade Care Metafields
 * @category   core
 * @author     Pixelgrade Team
 */
interface PixelgradeCare_MetafieldsHTMLTag {

	/**
	 * @param string key
	 * @param mixed default
	 *
	 * @return mixed
	 */
	function get( $key, $default = null );

	/**
	 * @param string key
	 * @param mixed value
	 *
	 * @return static $this
	 */
	function set( $key, $value );

	/**
	 * @return string
	 */
	function htmlattributes( array $extra = [] );

} # interface
