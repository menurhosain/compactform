<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Dimensions_Control extends Base_Control {

	private const UNITS = [ 'px', '%', 'em', 'rem', 'custom' ];
	private const SIDES = [ 'top', 'right', 'bottom', 'left' ];

	public function get_type(): string {
		return 'dimensions';
	}

	public function sanitize( $value, array $args = [] ) {
		$out = $this->get_default();
		if ( ! is_array( $value ) ) {
			return $out;
		}
		$unit          = $value['unit'] ?? 'px';
		$out['unit']   = in_array( $unit, self::UNITS, true ) ? $unit : 'px';
		$out['linked'] = ! empty( $value['linked'] );

		// The `custom` unit replaces the four sides with one free-text shorthand.
		if ( 'custom' === $out['unit'] ) {
			$out['customValue'] = self::css_safe( (string) ( $value['customValue'] ?? '' ) );
			return $out;
		}

		foreach ( self::SIDES as $side ) {
			$v = $value[ $side ] ?? '';
			if ( is_numeric( $v ) ) {
				$out[ $side ] = (string) ( 0 + $v );
			}
		}
		return $out;
	}

	public function get_default() {
		return [
			'top'         => '',
			'right'       => '',
			'bottom'      => '',
			'left'        => '',
			'unit'        => 'px',
			'linked'      => true,
			'customValue' => '',
		];
	}

	/** Flattened as a CSS shorthand, e.g. `10px 5px 10px 5px` (unset sides → 0). */
	public function get_option_value_stringify( $value ): string {
		if ( ! is_array( $value ) ) {
			return '';
		}
		if ( 'custom' === ( $value['unit'] ?? '' ) ) {
			return self::css_safe( (string) ( $value['customValue'] ?? '' ) );
		}
		$unit  = in_array( $value['unit'] ?? 'px', self::UNITS, true ) ? $value['unit'] : 'px';
		$sides = [];
		foreach ( self::SIDES as $side ) {
			$v       = $value[ $side ] ?? '';
			$sides[] = ( '' !== $v ? $v : '0' ) . $unit;
		}
		return implode( ' ', $sides );
	}

	public function get_css( $value, string $template ): string {
		if ( '' === $template || ! is_array( $value ) ) {
			return '';
		}

		// `custom` unit: one raw shorthand replaces the whole four-side value. The
		// emptied side tokens leave gaps, so collapse the runs of spaces they make.
		if ( 'custom' === ( $value['unit'] ?? 'px' ) ) {
			$custom = self::css_safe( (string) ( $value['customValue'] ?? '' ) );
			if ( '' === $custom ) {
				return '';
			}
			$out = str_replace(
				[ '{{TOP}}', '{{RIGHT}}', '{{BOTTOM}}', '{{LEFT}}', '{{UNIT}}' ],
				[ $custom, '', '', '', '' ],
				$template
			);
			return trim( preg_replace( [ '/ {2,}/', '/ ;/' ], [ ' ', ';' ], $out ) );
		}

		$sides = [];
		$any   = false;
		foreach ( self::SIDES as $side ) {
			$n = self::css_number( $value[ $side ] ?? '' );
			if ( '' !== $n ) {
				$any = true;
			}
			$sides[ $side ] = ( '' !== $n ) ? $n : '0';
		}
		if ( ! $any ) {
			return '';
		}

		$unit = in_array( $value['unit'] ?? 'px', self::UNITS, true ) ? $value['unit'] : 'px';

		return str_replace(
			[ '{{TOP}}', '{{RIGHT}}', '{{BOTTOM}}', '{{LEFT}}', '{{UNIT}}' ],
			[ $sides['top'], $sides['right'], $sides['bottom'], $sides['left'], $unit ],
			$template
		);
	}
}
