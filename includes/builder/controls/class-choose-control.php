<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Choose_Control extends Base_Control {

	public function get_type(): string {
		return 'choose';
	}

	public function sanitize( $value, array $args = [] ) {
		$value = sanitize_text_field( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		$options = isset( $args['options'] ) ? array_map( 'strval', array_keys( (array) $args['options'] ) ) : [];
		if ( ! $options || in_array( $value, $options, true ) ) {
			return $value;
		}
		return isset( $args['default'] ) ? (string) $args['default'] : ( $options[0] ?? '' );
	}

	public function get_option_value_stringify( $value ): string {
		return (string) $value;
	}
}
