<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Select_Control extends Base_Control {
	public function get_type(): string {
		return 'select';
	}

	public function sanitize( $value, array $args = [] ) {
		$options = isset( $args['options'] ) ? array_map( 'strval', array_keys( (array) $args['options'] ) ) : [];

		$creatable = ! empty( $args['create'] ) || ! empty( $args['insert_option'] );

		if ( ! empty( $args['multiSelect'] ) || ! empty( $args['multiple'] ) ) {
			$out = [];
			foreach ( (array) $value as $v ) {
				$v = sanitize_text_field( (string) $v );

				// An empty entry is never a real choice — and an unset multi
				// control arrives as the base default `''`, which would otherwise
				// be stored as a one-element array holding nothing.
				if ( '' === $v ) {
					continue;
				}

				$ok = $creatable ? true : ( ! $options || in_array( $v, $options, true ) );
				if ( $ok && ! in_array( $v, $out, true ) ) {
					$out[] = $v;
				}
			}
			return $out;
		}

		$value = sanitize_text_field( (string) $value );
		if ( $creatable || ! $options || in_array( $value, $options, true ) ) {
			return $value;
		}
		return isset( $args['default'] ) ? (string) $args['default'] : ( $options[0] ?? '' );
	}

	public function get_option_value_stringify( $value ): string {
		if ( is_array( $value ) ) {
			return implode( '|', array_map( 'strval', $value ) );
		}
		return (string) $value;
	}
}
