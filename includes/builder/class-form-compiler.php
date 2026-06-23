<?php

namespace CompactForm\Builder;

defined( 'ABSPATH' ) || die();

class Form_Compiler {

	public static function compile( array $schema ): string {
		if ( empty( $schema['fields'] ) || ! is_array( $schema['fields'] ) ) {
			return '';
		}

		$out = [];

		foreach ( $schema['fields'] as $data ) {
			if ( ! is_array( $data ) || ! empty( $data['parentId'] ) ) {
				continue; 
			}

			$data['__siblings'] = $schema['fields'];
			$out[]              = self::compile_field( $data );
		}

		return implode( "\n", array_filter( $out ) );
	}

	public static function compile_field( array $data ): string {
		$field = Fields_Manager::instance()->get( (string) ( $data['type'] ?? '' ) );
		if ( ! $field ) {
			return '';
		}

		return self::field_wrap( $data, $field->compile( $data ) );
	}

	/** The `.fcf7b-field-wrap` div every field gets, wherever it sits. */
	private static function field_wrap( array $data, string $inner ): string {
		$cond = '';
		if ( ! empty( $data['conditions'] ) && is_array( $data['conditions'] ) && ! empty( $data['conditions']['enabled'] ) ) {
			$cond = ' data-fcf7-cond="' . esc_attr( wp_json_encode( $data['conditions'] ) ) . '"';
		}

		return '<div class="fcf7b-field-wrap ' . self::field_class( $data ) . "\"{$cond}>\n{$inner}\n</div>";
	}

	private static function field_id( array $data ): string {
		return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) ( $data['id'] ?? '' ) );
	}

	private static function field_class( array $data ): string {
		$id = self::field_id( $data );
		return 'fcf7b-field-' . ( $id ? $id : 'field' );
	}

}
