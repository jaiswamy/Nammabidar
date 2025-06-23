<?php

/**
 *
 * A class to handle conditions.
 */
class PixelgradeCare_Conditions {

	protected static $group_relations = [
		'AND', 'OR',
	];

	protected static $active_theme_details = null;

	/**
	 * Process a set of conditions.
	 *
	 * @param array $conditions
	 *
	 * @return bool|mixed
	 */
	public static function process( $conditions ) {
		$result = self::process_group( $conditions );

		return apply_filters( 'pixelgrade_care_conditions_result', $result, $conditions );
	}

	/**
	 * Process and evaluate a condition group.
	 *
	 * @param array $group_conditions
	 *
	 * @return bool
	 */
	public static function process_group( $group_conditions ) {
		// By default, we will use the AND relation among group rules or subgroups.
		$group_relation = 'AND';
		if ( ! empty( $group_conditions['relation'] ) && in_array( $group_conditions['relation'], self::$group_relations ) ) {
			$group_relation = $group_conditions['relation'];
		}

		// We don't need it further.
		unset( $group_conditions['relation'] );

		// Return true on invalid conditions.
		if ( ! empty( $group_conditions['rules'] ) && ! is_array( $group_conditions['rules'] ) ) {
			return true;
		} else if ( empty( $group_conditions['rules'] ) ) {
			// We will treat the entire group as a set of rules.
			unset( $group_conditions['rules'] );
			$group_conditions['rules'] = $group_conditions;
		}

		switch ( $group_relation ) {
			case 'AND':
				// By default, we assure that the conditions evaluate to true.
				$result = true;
				break;
			case 'OR':
				// By default, we assure that the conditions evaluate to false.
				$result = false;
				break;
			default:
				$result = false;
				break;
		}

		$stop = false;
		foreach ( $group_conditions['rules'] as $key => $rule ) {
			// Determine if it is a simple rule or a subgroup.
			if ( ! empty( $rule['rules'] )
			     || ! empty( $rule['relation'] ) ) {
				$result = self::process_group( $rule );
			} else {
				$result = self::process_rule( $rule, $key );
			}

			// Now evaluate the rule result according to the group relation.
			switch ( $group_relation ) {
				case 'AND':
					if ( false === $result ) {
						// Stop the evaluation.
						$stop = true;
					}
					break;
				case 'OR':
					if ( true === $result ) {
						// Stop the evaluation.
						$stop = true;
					}
					break;
				default:
					// We should not reach this far, but just in case.
					$stop = true;
					break;
			}

			// Stop the rules processing if this is the case.
			if ( true === $stop ) {
				break;
			}
		}

		return apply_filters( 'pixelgrade_care_conditions_group_result', $result, $group_conditions['rules'], $group_relation );
	}

	/**
	 * Process and evaluate a condition rule.
	 *
	 * @param mixed $rule
	 * @param int|string $key
	 *
	 * @return bool
	 */
	public static function process_rule( $rule, $key ) {
		$result = true;

		// First normalize the rule from shorthand, unary conditions.
		if ( is_string( $rule ) && in_array( $key, [ 'userHasAccess', 'isCallable', 'functionExists', 'classExists', 'isDefined' ] ) ) {
			$rule = [
				'type' => $key,
				'value' => $rule,
				'comparison' => 'is_not_empty',
			];
		}

		if ( empty( $rule['type'] ) || empty( $rule['value'] ) ) {
			return $result;
		}

		// Unary conditions use is_not_empty by default.
		if ( in_array( $rule['type'], [ 'userHasAccess', 'isCallable', 'functionExists', 'classExists', 'methodExists', 'isDefined' ] )
			&& ! isset( $rule['comparison'] ) ) {

			$rule['comparison'] = 'is_not_empty';
		}

		// If the comparison is not provided, we will use 'is_not_empty' for missing value and 'equal' when a value is provided.
		if ( empty( $rule['comparison'] ) ) {
			$rule['comparison'] = 'is_not_empty';
			if ( isset( $rule['value'] ) ) {
				$rule['comparison'] = 'equal';
			}
		}

		// Now determine the rule value to compare (the dynamic part of the rule).
		if ( ! method_exists( __CLASS__, 'get_' . $rule['type'] ) ) {
			return $result;
		}
		$dynamic_value = call_user_func( [ __CLASS__, 'get_' . $rule['type'] ], $rule );

		// Now evaluate the expression.
		require_once 'class-pixelgrade_care-logicalexpression.php';
		$result = PixelgradeCare_LogicalExpression::evaluate( $dynamic_value, $rule['comparison'], $rule['value'] );

		return apply_filters( 'pixelgrade_care_conditions_rule_result', $result, $dynamic_value, $rule['comparison'], $rule['value'], $rule );
	}

	public static function evaluate_expression( $left, $operator, $right, $rule ) {

	}

	/* ========================
	 * THE DYNAMIC VALUES GETTERS
	 */

	public static function get_option( $rule = null ) {
		$default = false;
		if ( isset( $rule['default'] ) ) {
			$default = $rule['default'];
		}

		if ( ! isset( $rule['option'] ) ) {
			return $default;
		}

		return get_option( _sanitize_text_fields( $rule['option'] ), $default );
	}

	public static function get_themeMod( $rule = null ) {
		$default = false;
		if ( isset( $rule['default'] ) ) {
			$default = $rule['default'];
		}

		if ( ! isset( $rule['option'] ) ) {
			return $default;
		}

		return get_theme_mod( _sanitize_text_fields( $rule['option'] ), $default );
	}

	public static function get_pixelgradeOption( $rule = null ) {
		$default = false;
		if ( isset( $rule['default'] ) ) {
			$default = $rule['default'];
		}

		if ( ! isset( $rule['option'] ) || ! function_exists( 'pixelgrade_option' ) ) {
			return $default;
		}

		return pixelgrade_option( _sanitize_text_fields( $rule['option'] ), $default );
	}

	public static function get_callback( $rule = null ) {
		if ( ! isset( $rule['callable'] ) || ! is_callable( $rule['callable'] ) ) {
			return false;
		}

		if ( isset( $rule['args'] ) ) {
			return call_user_func( $rule['callable'], $rule['args'] );
		}

		return call_user_func( $rule['callable'] );
	}

	public static function get_constant( $rule = null ) {
		$default = false;
		if ( isset( $rule['default'] ) ) {
			$default = $rule['default'];
		}

		if ( ! defined( $rule['constant'] ) ) {
			return $default;
		}

		return constant( _sanitize_text_fields( $rule['constant'] ) );
	}

	public static function get_userHasAccess( $rule = null ) {
		return pixelgrade_user_has_access( $rule['value'] );
	}

	public static function get_isCallable( $rule = null ) {
		return is_callable( $rule['value'] );
	}

	public static function get_functionExists( $rule = null ) {
		return function_exists( $rule['value'] );
	}

	public static function get_classExists( $rule = null ) {
		return class_exists( $rule['value'] );
	}

	public static function get_methodExists( $rule = null ) {
		if ( ! isset( $rule['class'] ) || ! isset( $rule['method'] ) ) {
			return false;
		}

		return method_exists( $rule['class'], $rule['method'] );
	}

	public static function get_isDefined( $rule = null ) {
		return defined( $rule['value'] );
	}
}
