<?php defined( 'ABSPATH' ) or die;

/**
 * @package    Pixelgrade Care Metafields
 * @category   core
 * @author     Pixelgrade Team
 */
interface PixelgradeCare_MetafieldsValidator {

	/**
	 * @return array errors
	 */
	function validate( $input );

	/**
	 * @param string rule
	 *
	 * @return string error message
	 */
	function error_message( $rule );

} # interface
