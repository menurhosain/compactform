<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Time_Ranges_Control extends Base_Control {

	public function get_type(): string {
		return 'time_ranges';
	}

	public function sanitize( $value, array $args = [] ) {
		$out = [];
		foreach ( (array) $value as $range ) {
			if ( ! is_array( $range ) ) {
				continue;
			}

			$from = $this->sanitize_time( $range['from'] ?? '' );
			$to   = $this->sanitize_time( $range['to'] ?? '' );

			if ( '' === $from || '' === $to ) {
				continue;
			}

			$out[] = [ 'from' => $from, 'to' => $to ];
		}
		return $out;
	}

	private function sanitize_time( $value ): string {
		$value = (string) $value;
		return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $value ) ? $value : '';
	}

	public function get_default() {
		return [];
	}

	public function get_option_value_stringify( $value ): string {
		$parts = [];
		foreach ( (array) $value as $range ) {
			if ( empty( $range['from'] ) || empty( $range['to'] ) ) {
				continue;
			}
			$parts[] = $range['from'] . '-' . $range['to'];
		}
		return implode( '|', $parts );
	}
}
