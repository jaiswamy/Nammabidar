<?php defined( 'ABSPATH' ) or die;

function pixcare_cpt_fields_validate_not_empty( $fieldvalue, $processor ) {
	return ! empty( $fieldvalue );
}
