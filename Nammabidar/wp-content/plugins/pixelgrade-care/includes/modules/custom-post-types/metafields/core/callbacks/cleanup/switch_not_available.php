<?php defined( 'ABSPATH' ) or die;

function pixcare_cpt_fields_cleanup_switch_not_available( $fieldvalue, $meta, $processor ) {
	return $fieldvalue !== null ? $fieldvalue : false;
}
