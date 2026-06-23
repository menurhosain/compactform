<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Steps_Control extends Base_Control {

	public function get_type(): string {
		return 'steps';
	}

	public function sanitize( $value, array $args = [] ) {
		$out = [];
		foreach ( (array) $value as $step ) {
			if ( ! is_array( $step ) ) {
				continue;
			}

			$id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $step['id'] ?? '' ) );
			if ( '' === $id ) {
				$id = 'step_' . substr( md5( uniqid( '', true ) ), 0, 8 );
			}

			$label = sanitize_text_field( (string) ( $step['label'] ?? '' ) );

			$out[] = [ 'id' => $id, 'label' => '' !== $label ? $label : __( 'Step', 'compactform' ) ];
		}

		return $out ? $out : $this->get_default();
	}

	public function get_default() {
		return [ [ 'id' => 'step_1', 'label' => __( 'Step 1', 'compactform' ) ] ];
	}

	public function get_option_value_stringify( $value ): string {
		return sprintf(
			/* translators: %d: number of steps */
			_n( '%d step', '%d steps', is_array( $value ) ? count( $value ) : 0, 'compactform' ),
			is_array( $value ) ? count( $value ) : 0
		);
	}
}
