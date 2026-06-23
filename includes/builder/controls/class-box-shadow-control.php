<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Box_Shadow_Control extends Base_Control {

	private const NUMS = [ 'horizontal', 'vertical', 'blur', 'spread' ];

	public function get_type(): string {
		return 'box_shadow';
	}

	public function sanitize( $value, array $args = [] ) {
		$out = $this->get_default();
		if ( ! is_array( $value ) ) {
			return $out;
		}
		foreach ( self::NUMS as $f ) {
			$v = $value[ $f ] ?? '';
			if ( is_numeric( $v ) ) {
				$out[ $f ] = (string) ( 0 + $v );
			}
		}
		$out['color'] = is_string( $value['color'] ?? null ) ? sanitize_text_field( $value['color'] ) : '';
		$out['inset'] = ! empty( $value['inset'] );
		return $out;
	}

	public function get_default() {
		return [
			'horizontal' => '',
			'vertical'   => '',
			'blur'       => '',
			'spread'     => '',
			'color'      => '',
			'inset'      => false,
		];
	}

	public function get_option_value_stringify( $value ): string {
		return ''; // CSS-only; never compiled into markup.
	}

	/** Emits `box-shadow: [inset ]Hpx Vpx Blurpx Spreadpx color;` (template ignored). */
	public function get_css( $value, string $template ): string {
		if ( ! is_array( $value ) ) {
			return '';
		}

		$color = self::css_safe( (string) ( $value['color'] ?? '' ) );

		$num = function ( $k ) use ( $value ) {
			$n = self::css_number( $value[ $k ] ?? '' );
			return '' === $n ? '0' : $n;
		};

		$any_num = false;
		foreach ( self::NUMS as $f ) {
			if ( '' !== self::css_number( $value[ $f ] ?? '' ) ) {
				$any_num = true;
				break;
			}
		}
		if ( '' === $color && ! $any_num ) {
			return '';
		}

		$inset  = ! empty( $value['inset'] ) ? 'inset ' : '';
		$shadow = $inset
			. $num( 'horizontal' ) . 'px '
			. $num( 'vertical' ) . 'px '
			. $num( 'blur' ) . 'px '
			. $num( 'spread' ) . 'px'
			. ( '' !== $color ? ' ' . $color : '' );

		return 'box-shadow: ' . $shadow . ';';
	}
}
