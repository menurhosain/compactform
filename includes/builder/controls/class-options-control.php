<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Options_Control extends Base_Control {
	public function get_type(): string {
		return 'options';
	}

	public function sanitize( $value, array $args = [] ) {
		$out = [];
		foreach ( (array) $value as $option ) {
			$option = sanitize_text_field( (string) $option );
			if ( '' !== $option ) {
				$out[] = $option;
			}
		}
		return $out;
	}

	public function get_default() {
		return [];
	}

	public function get_option_value_stringify( $value ): string {
		return implode( "\n", (array) $value );
	}
}
