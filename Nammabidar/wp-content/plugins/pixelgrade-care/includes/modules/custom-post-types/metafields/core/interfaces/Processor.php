<?php defined( 'ABSPATH' ) or die;

/**
 * @package    Pixelgrade Care Metafields
 * @category   core
 * @author     Pixelgrade Team
 */
interface PixelgradeCare_MetafieldsProcessor {

	/**
	 * @return static $this
	 */
	function run();

	/**
	 * @return array
	 */
	function status();

	/**
	 * @return PixelgradeCare_MetafieldsMeta current data (influenced by user submitted data)
	 */
	function data();

	/**
	 * Shorthand.
	 *
	 * @return array
	 */
	function errors();

	/**
	 * Shorthand.
	 *
	 * @return boolean
	 */
	function performed_update();

	/**
	 * @return boolean true if state is nominal
	 */
	function ok();

} # interface
