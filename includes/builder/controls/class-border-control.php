<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Border_Control extends Base_Control {

	private const STYLES = [ 'none', 'solid', 'dashed', 'dotted', 'double', 'groove' ];

	/** Devices the width sub-value may carry. Desktop is the base. */
	private const DEVICES = [ 'desktop', 'tablet', 'mobile' ];

	public function get_type(): string {
		return 'border';
	}

	/** The style choices offered by the panel (with the "none" entry first). */
	public static function styles_for_js(): array {
		return [
			''       => __( 'Default', 'compactform' ),
			'none'   => __( 'None', 'compactform' ),
			'solid'  => __( 'Solid', 'compactform' ),
			'dashed' => __( 'Dashed', 'compactform' ),
			'dotted' => __( 'Dotted', 'compactform' ),
			'double' => __( 'Double', 'compactform' ),
			'groove' => __( 'Groove', 'compactform' ),
		];
	}

	/** Reuse the dimensions control for the width box — one implementation. */
	private function width_control(): Dimensions_Control {
		return new Dimensions_Control();
	}

	public function sanitize( $value, array $args = [] ) {
		$out = $this->get_default();
		if ( ! is_array( $value ) ) {
			return $out;
		}

		$style        = (string) ( $value['style'] ?? '' );
		$out['style'] = in_array( $style, self::STYLES, true ) ? $style : '';
		$out['color'] = ( new Color_Control() )->sanitize( $value['color'] ?? '' );
		$out['width'] = $this->sanitize_width( $value['width'] ?? null );

		return $out;
	}

	/** The width sub-value: device => dimensions, always with a desktop base. */
	private function sanitize_width( $width ): array {
		$dims = $this->width_control();
		$out  = [];

		if ( is_array( $width ) ) {
			foreach ( self::DEVICES as $device ) {
				if ( isset( $width[ $device ] ) ) {
					$out[ $device ] = $dims->sanitize( $width[ $device ] );
				}
			}
			// A bare dimensions object (no device keys) is the desktop value.
			if ( ! $out && ( isset( $width['unit'] ) || isset( $width['top'] ) ) ) {
				$out['desktop'] = $dims->sanitize( $width );
			}
		}

		if ( ! isset( $out['desktop'] ) ) {
			$out = [ 'desktop' => $dims->get_default() ] + $out;
		}

		return $out;
	}

	public function get_default() {
		return [
			'style' => '',
			'color' => '',
			'width' => [ 'desktop' => ( new Dimensions_Control() )->get_default() ],
		];
	}

	/** Flattened to the border style — the value that decides if there is one. */
	public function get_option_value_stringify( $value ): string {
		return is_array( $value ) ? (string) ( $value['style'] ?? '' ) : '';
	}

	/** One device's dimensions as a `border-width` declaration ('' when unset). */
	private function width_css( $dimensions ): string {
		$css = $this->width_control()->get_css(
			$dimensions,
			'border-width:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
		);
		return rtrim( trim( $css ), ';' );
	}

	/**
	 * Render per device, because only the width is responsive.
	 *
	 * Desktop gets the whole border block; a tablet/mobile entry emits *only*
	 * `border-width`, since style and colour are inherited from the desktop rule
	 * and repeating them inside a media query would be pure bloat.
	 */
	public function get_css_devices( $value, string $template, array $devices ): ?array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$style = (string) ( $value['style'] ?? '' );
		if ( '' === $style ) {
			return []; // Default: nothing declared, so the border inherits whatever the theme/parent already sets.
		}
		if ( ! in_array( $style, self::STYLES, true ) ) {
			return [];
		}
		if ( 'none' === $style ) {
			// Explicit removal — width/colour are meaningless without a visible edge, so skip them (and any responsive width override) entirely.
			return [ 'desktop' => 'border-style:none;' ];
		}

		$widths = is_array( $value['width'] ?? null ) ? $value['width'] : [];
		$out    = [];

		$decl  = [ 'border-style:' . $style ];
		$width = $this->width_css( $widths['desktop'] ?? null );
		if ( '' !== $width ) {
			$decl[] = $width;
		}
		$color = self::css_safe( (string) ( $value['color'] ?? '' ) );
		if ( '' !== $color ) {
			$decl[] = 'border-color:' . $color;
		}
		$out['desktop'] = implode( ';', $decl ) . ';';

		foreach ( $devices as $device ) {
			if ( 'desktop' === $device || ! isset( $widths[ $device ] ) ) {
				continue;
			}
			$width = $this->width_css( $widths[ $device ] );
			if ( '' !== $width ) {
				$out[ $device ] = $width . ';';
			}
		}

		return $out;
	}

	/**
	 * A composite control: used with the singular `selector` arg, so it emits the
	 * whole border block itself (the template is ignored). This is the desktop
	 * base; the generator uses get_css_devices() for the responsive width.
	 */
	public function get_css( $value, string $template ): string {
		$devices = $this->get_css_devices( $value, $template, [ 'desktop' ] );
		return $devices['desktop'] ?? '';
	}
}
