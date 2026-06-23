<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Slider_Control extends Base_Control {

	private const DEFAULT_UNITS = [ 'px', '%', 'em', 'rem' ];

	public function get_type(): string {
		return 'slider';
	}

	public function sanitize( $value, array $args = [] ) {
		$units        = ( isset( $args['units'] ) && is_array( $args['units'] ) && $args['units'] ) ? $args['units'] : self::DEFAULT_UNITS;
		$default_unit = $units[0];
		$out          = [ 'size' => '', 'unit' => $default_unit ];

		if ( is_array( $value ) ) {
			$size = $value['size'] ?? '';
			$unit = $value['unit'] ?? $default_unit;
		} elseif ( is_numeric( $value ) ) {
            // back-compat: a plain number becomes { size, default unit }.
			$size = $value; 
			$unit = $default_unit;
		} else {
			return $out;
		}

		$unit = in_array( $unit, $units, true ) ? $unit : $default_unit;

		// The `custom` unit (opt-in via the instance's `units`) turns the size into
		// a free-text CSS value — calc(), clamp(), var(), … — stored as-is.
		if ( 'custom' === $unit ) {
			return [ 'size' => self::css_safe( (string) $size ), 'unit' => 'custom' ];
		}

		$out['unit'] = $unit;
		if ( is_numeric( $size ) ) {
			$n = 0 + $size;
			if ( isset( $args['min'] ) && $n < $args['min'] ) {
				$n = $args['min'];
			}
			if ( isset( $args['max'] ) && $n > $args['max'] ) {
				$n = $args['max'];
			}
			$out['size'] = (string) $n;
		}
		return $out;
	}

	public function get_default() {
		return [ 'size' => '', 'unit' => 'px' ];
	}

	/** Flattened as `{size}{unit}`, e.g. `16px` ('' when size is unset). */
	public function get_option_value_stringify( $value ): string {
		if ( ! is_array( $value ) || '' === ( $value['size'] ?? '' ) ) {
			return '';
		}
		if ( 'custom' === ( $value['unit'] ?? '' ) ) {
			return (string) $value['size']; // already the full CSS value.
		}
		return $value['size'] . ( $value['unit'] ?? 'px' );
	}

	/** Supports `{{SIZE}}` + `{{UNIT}}`, e.g. `font-size: {{SIZE}}{{UNIT}};`. */
	public function get_css( $value, string $template ): string {
		if ( '' === $template || ! is_array( $value ) ) {
			return '';
		}
		$unit = (string) ( $value['unit'] ?? 'px' );

		// `custom` unit: the size is a raw CSS value emitted verbatim, no suffix.
		if ( 'custom' === $unit ) {
			$custom = self::css_safe( (string) ( $value['size'] ?? '' ) );
			if ( '' === $custom ) {
				return '';
			}
			return str_replace( [ '{{SIZE}}', '{{UNIT}}' ], [ $custom, '' ], $template );
		}

		$size = self::css_number( $value['size'] ?? '' );
		if ( '' === $size ) {
			return '';
		}

		return str_replace( [ '{{SIZE}}', '{{UNIT}}' ], [ $size, self::css_safe( $unit ) ], $template );
	}
}
