<?php defined( 'ABSPATH' ) or die;

/**
 * @package    Pixelgrade Care Metafields
 * @category   core
 * @author     Pixelgrade Team
 */
interface PixelgradeCare_MetafieldsHTMLElement extends PixelgradeCare_MetafieldsHTMLTag {

	/**
	 * @param string meta key
	 *
	 * @return boolean true if key exists, false otherwise
	 */
	function hasmeta( $key );

	/**
	 * @return mixed value or default
	 */
	function getmeta( $key, $default = null );

	/**
	 * @return static $this
	 */
	function setmeta( $key, $value );

	/**
	 * Set the key if it's not already set.
	 *
	 * @param string key
	 * @param string value
	 */
	function ensuremeta( $key, $value );

	/**
	 * If the key is currently a non-array value it will be converted to an
	 * array maintaning the previous value (along with the new one).
	 *
	 * @param  string name
	 * @param  mixed  value
	 *
	 * @return static $this
	 */
	function addmeta( $name, $value );

	/**
	 * @return PixelgradeCare_MetafieldsMeta form meta
	 */
	function meta();

} # interface
