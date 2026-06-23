<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

/** Single-line text. */
class Text_Control extends Base_Control {
	public function get_type(): string {
		return 'text';
	}

	public function get_option_value_stringify( $value ): string {
		return (string) $value;
	}

	public function sanitize( $value, array $args = [] ) {
		$value = parent::sanitize( $value, $args );

		if ( ! empty( $args['slug'] ) && '' !== $value ) {
			$value = trim( preg_replace( '/[^a-z0-9_\-]/', '-', strtolower( $value ) ), '-' );
		}

		return $value;
	}
}
