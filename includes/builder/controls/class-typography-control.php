<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Typography_Control extends Base_Control {

	private const WEIGHTS     = [ '100', '200', '300', '400', '500', '600', '700', '800', '900' ];
	private const TRANSFORMS  = [ 'none', 'uppercase', 'lowercase', 'capitalize' ];
	private const STYLES      = [ 'normal', 'italic' ];
	private const DECORATIONS = [ 'none', 'underline', 'line-through' ];
	private const UNITS       = [ 'px', '%', 'em', 'rem', 'custom' ];

	public static function families(): array {
		return [
			'system'    => [ 'label' => 'System Default', 'stack' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif' ],
			'arial'     => [ 'label' => 'Arial', 'stack' => 'Arial, Helvetica, sans-serif' ],
			'helvetica' => [ 'label' => 'Helvetica', 'stack' => 'Helvetica, Arial, sans-serif' ],
			'verdana'   => [ 'label' => 'Verdana', 'stack' => 'Verdana, Geneva, sans-serif' ],
			'tahoma'    => [ 'label' => 'Tahoma', 'stack' => 'Tahoma, Geneva, sans-serif' ],
			'trebuchet' => [ 'label' => 'Trebuchet MS', 'stack' => '"Trebuchet MS", Helvetica, sans-serif' ],
			'georgia'   => [ 'label' => 'Georgia', 'stack' => 'Georgia, "Times New Roman", serif' ],
			'times'     => [ 'label' => 'Times New Roman', 'stack' => '"Times New Roman", Times, serif' ],
			'courier'   => [ 'label' => 'Courier New', 'stack' => '"Courier New", Courier, monospace' ],
			'mono'      => [ 'label' => 'Monospace', 'stack' => 'ui-monospace, Menlo, Consolas, monospace' ],
		];
	}

	/** The map handed to JS (dropdown labels + stacks for the live preview). */
	public static function families_for_js(): array {
		$out = [ '' => [ 'label' => __( 'Default', 'compactform' ), 'stack' => '' ] ];
		foreach ( self::families() as $k => $f ) {
			$out[ $k ] = $f;
		}
		return $out;
	}

	/** CSS font-family stack for a key ('' when none/unknown). */
	public static function stack( $key ): string {
		$f = self::families();
		return isset( $f[ (string) $key ] ) ? $f[ (string) $key ]['stack'] : '';
	}

	public function get_type(): string {
		return 'typography';
	}

	public function get_option_value_stringify( $value ): string {
		return is_array( $value ) ? (string) ( $value['family'] ?? '' ) : '';
	}

	private function sub( $v, string $default_unit = 'px' ): array {
		$out = [ 'size' => '', 'unit' => $default_unit ];
		if ( is_array( $v ) ) {
			$u           = $v['unit'] ?? $default_unit;
			$out['unit'] = in_array( $u, self::UNITS, true ) ? $u : $default_unit;
			if ( 'custom' === $out['unit'] ) {
				$out['size'] = self::css_safe( (string) ( $v['size'] ?? '' ) );
			} elseif ( is_numeric( $v['size'] ?? '' ) ) {
				$out['size'] = (string) ( 0 + $v['size'] );
			}
		}

		return $out;
	}

	public function sanitize( $value, array $args = [] ) {
		$out = $this->get_default();
		if ( ! is_array( $value ) ) {
			return $out;
		}

		$fam           = (string) ( $value['family'] ?? '' );
		$out['family'] = array_key_exists( $fam, self::families() ) ? $fam : '';

		$out['weight']     = in_array( (string) ( $value['weight'] ?? '' ), self::WEIGHTS, true ) ? (string) $value['weight'] : '';
		$out['transform']  = in_array( (string) ( $value['transform'] ?? '' ), self::TRANSFORMS, true ) ? (string) $value['transform'] : '';
		$out['style']      = in_array( (string) ( $value['style'] ?? '' ), self::STYLES, true ) ? (string) $value['style'] : '';
		$out['decoration'] = in_array( (string) ( $value['decoration'] ?? '' ), self::DECORATIONS, true ) ? (string) $value['decoration'] : '';

		$out['size']          = $this->sub( $value['size'] ?? null, 'px' );
		$out['lineHeight']    = $this->sub( $value['lineHeight'] ?? null, 'em' );
		$out['letterSpacing'] = $this->sub( $value['letterSpacing'] ?? null, 'px' );

		return $out;
	}

	public function get_default() {
		return [
			'family'        => '',
			'weight'        => '',
			'transform'     => '',
			'style'         => '',
			'decoration'    => '',
			'size'          => [ 'size' => '', 'unit' => 'px' ],
			'lineHeight'    => [ 'size' => '', 'unit' => 'em' ],
			'letterSpacing' => [ 'size' => '', 'unit' => 'px' ],
		];
	}

	private function len( $v ): string {
		if ( ! is_array( $v ) ) {
			return '';
		}
		if ( 'custom' === ( $v['unit'] ?? '' ) ) {
			return self::css_safe( (string) ( $v['size'] ?? '' ) );
		}
		$size = self::css_number( $v['size'] ?? '' );
		if ( '' === $size ) {
			return '';
		}
		$unit = in_array( $v['unit'] ?? 'px', self::UNITS, true ) ? $v['unit'] : 'px';
		return $size . $unit;
	}

	public function get_css( $value, string $template ): string {
		if ( ! is_array( $value ) ) {
			return '';
		}

		$decl = [];

		$family = self::stack( $value['family'] ?? '' );
		if ( '' !== $family ) {
			$decl[] = 'font-family:' . self::css_safe( $family );
		}

		$size = $this->len( $value['size'] ?? null );
		if ( '' !== $size ) {
			$decl[] = 'font-size:' . $size;
		}

		$weight = (string) ( $value['weight'] ?? '' );
		if ( in_array( $weight, self::WEIGHTS, true ) ) {
			$decl[] = 'font-weight:' . $weight;
		}

		$transform = (string) ( $value['transform'] ?? '' );
		if ( in_array( $transform, self::TRANSFORMS, true ) ) {
			$decl[] = 'text-transform:' . $transform;
		}

		$style = (string) ( $value['style'] ?? '' );
		if ( in_array( $style, self::STYLES, true ) ) {
			$decl[] = 'font-style:' . $style;
		}

		$decoration = (string) ( $value['decoration'] ?? '' );
		if ( in_array( $decoration, self::DECORATIONS, true ) ) {
			$decl[] = 'text-decoration:' . $decoration;
		}

		$line_height = $this->len( $value['lineHeight'] ?? null );
		if ( '' !== $line_height ) {
			$decl[] = 'line-height:' . $line_height;
		}

		$letter_spacing = $this->len( $value['letterSpacing'] ?? null );
		if ( '' !== $letter_spacing ) {
			$decl[] = 'letter-spacing:' . $letter_spacing;
		}

		return $decl ? implode( ';', $decl ) . ';' : '';
	}
}
