<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

/** Numeric value, clamped to optional min/max. */
class Number_Control extends Base_Control {
	public function get_type(): string {
		return 'number';
	}

	public function sanitize( $value, array $args = [] ) {
		if ( '' === $value || null === $value || ! is_numeric( $value ) ) {
			return '';
		}
		$n = 0 + $value;
		if ( isset( $args['min'] ) && $n < $args['min'] ) {
			$n = $args['min'];
		}
		if ( isset( $args['max'] ) && $n > $args['max'] ) {
			$n = $args['max'];
		}
		return $n;
	}

	public function get_option_value_stringify( $value ): string {
		return is_numeric( $value ) ? (string) $value : '';
	}
}
