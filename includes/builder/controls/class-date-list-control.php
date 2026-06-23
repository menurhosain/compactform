<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

/** A set of specific calendar dates (YYYY-MM-DD each) — e.g. holidays to block. */
class Date_List_Control extends Base_Control {
	public function get_type(): string {
		return 'date_list';
	}

	public function sanitize( $value, array $args = [] ) {
		$out = [];
		foreach ( (array) $value as $date ) {
			$date = sanitize_text_field( (string) $date );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && ! in_array( $date, $out, true ) ) {
				$out[] = $date;
			}
		}
		sort( $out );
		return $out;
	}

	public function get_default() {
		return [];
	}

	public function get_option_value_stringify( $value ): string {
		return implode( '|', (array) $value );
	}
}
