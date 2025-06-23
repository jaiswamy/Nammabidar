<?php defined( 'ABSPATH' ) or die;

function pixcare_cpt_fields_validate_is_numeric( $fieldvalue, $validator ) {
	return is_int( $fieldvalue ) || preg_match( '/^[0-9\.]+$/', $fieldvalue );
}
