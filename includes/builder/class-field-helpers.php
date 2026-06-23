<?php

namespace CompactForm\Builder;

defined( 'ABSPATH' ) || die();

class Field_Helpers {

	public static function name( array $d ): string {
		$name = strtolower( (string) ( $d['name'] ?? '' ) );
		$name = preg_replace( '/[^a-z0-9_\-]/', '-', $name );
		$name = trim( $name, '-' );
		return $name ? $name : 'field';
	}

	public static function q( $value ): string {
		return str_replace( '"', '', (string) $value );
	}

	/** Trimmed, non-empty choice options. */
	public static function options( array $d ): array {
		$out = [];
		foreach ( (array) ( $d['options'] ?? [] ) as $o ) {
			$o = trim( (string) $o );
			if ( '' !== $o ) {
				$out[] = $o;
			}
		}
		return $out;
	}

	public static function esc( $value ): string {
		return esc_html( (string) $value );
	}

	/** placeholder / default value + class:/id: attributes for a CF7 tag. */
	public static function atts( array $d, bool $allow_value ): string {
		$s = '';

		if ( $allow_value ) {
			$ph = trim( (string) ( $d['placeholder'] ?? '' ) );
			$dv = trim( (string) ( $d['defaultValue'] ?? '' ) );
			if ( '' !== $ph ) {
				$s .= ' placeholder "' . self::q( $ph ) . '"';
			} elseif ( '' !== $dv ) {
				$s .= ' "' . self::q( $dv ) . '"';
			}
		}

		if ( ! empty( $d['cssClass'] ) ) {
			foreach ( preg_split( '/\s+/', trim( (string) $d['cssClass'] ) ) as $c ) {
				$c = preg_replace( '/[^A-Za-z0-9_\-]/', '', $c );
				if ( $c ) {
					$s .= ' class:' . $c;
				}
			}
		}

		if ( ! empty( $d['fieldId'] ) ) {
			$id = preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $d['fieldId'] );
			if ( $id ) {
				$s .= ' id:' . $id;
			}
		}

		return $s;
	}

	public static function label_wrap( array $d, string $tag, bool $group = false ): string {
		$label = (string) ( $d['label'] ?? '' );
		// The marker is the `required_asterisk` control, defaulting to '*' when a
		// field predates it or it's left blank.
		$mark  = ( '' !== (string) ( $d['required_asterisk'] ?? '' ) ) ? (string) $d['required_asterisk'] : '*';
		$req   = ! empty( $d['required'] ) ? ' <span class="fcf7b-req">' . self::esc( $mark ) . '</span>' : '';

		$el  = $group ? 'div' : 'label';
		$out = '<' . $el . ' class="fcf7b-field' . ( $group ? ' fcf7b-field-group' : '' ) . '">';
		if ( '' !== $label ) {
			$out .= '<span class="fcf7b-field-label">' . self::esc( $label ) . $req . '</span>';
		}

		$ctl = $tag;
		if ( ! empty( $d['field_icon'] ) ) {
			$ic = trim( preg_replace( '/[^a-z0-9\- ]/i', '', (string) $d['field_icon'] ) );
			if ( '' !== $ic ) {
				$ctl = '<span class="fcf7-field-icon"><i class="' . esc_attr( $ic ) . '"></i>' . $ctl . '</span>';
			}
		}
		$out .= "\n" . $ctl . "\n</" . $el . '>';

		$desc = trim( (string) ( $d['description'] ?? '' ) );
		if ( '' !== $desc ) {
			$out .= "\n" . '<small class="fcf7b-desc">' . self::esc( $desc ) . '</small>';
		}

		return $out;
	}

	public static function get_form_schema( $contact_form ): array {
		static $cache = array();

		$id = $contact_form ? (int) $contact_form->id() : 0;
		if ( ! $id ) {
			return array();
		}
		if ( isset( $cache[ $id ] ) ) {
			return $cache[ $id ];
		}

		$decoded = json_decode( (string) get_post_meta( $id, '_fcf7_builder_schema', true ), true );
		$cache[ $id ] = is_array( $decoded ) ? $decoded : array();

		return $cache[ $id ];
	}
}
