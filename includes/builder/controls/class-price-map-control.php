<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Price_Map_Control extends Base_Control {

	public function get_type(): string {
		return 'price_map';
	}

	public function sanitize( $value, array $args = [] ) {
		$out  = [];
		$seen = [];
		foreach ( (array) $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$option = sanitize_text_field( (string) ( $row['option'] ?? '' ) );
			$price  = is_numeric( $row['price'] ?? null ) ? max( 0, (float) $row['price'] ) : null;

			if ( '' === $option || null === $price || isset( $seen[ $option ] ) ) {
				continue;
			}

			$seen[ $option ] = true;
			$out[]           = [ 'option' => $option, 'price' => $price ];
		}

		return $out;
	}

	public function get_default() {
		return [];
	}

	/** "option:price" per row, rows pipe-joined — same shape as Date_Prices_Control. Not used for CSS or a tag option; kept for interface parity. */
	public function get_option_value_stringify( $value ): string {
		$parts = [];
		foreach ( (array) $value as $row ) {
			if ( '' === (string) ( $row['option'] ?? '' ) || ! isset( $row['price'] ) ) {
				continue;
			}
			$parts[] = $row['option'] . ':' . $row['price'];
		}
		return implode( '|', $parts );
	}
}
