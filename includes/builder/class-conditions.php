<?php

namespace CompactForm\Builder;

defined( 'ABSPATH' ) || die();

class Conditions {

	private static function cf7_name( $raw ): string {
		$n = strtolower( (string) $raw );
		$n = preg_replace( '/[^a-z0-9_\-]/', '-', $n );
		$n = trim( $n, '-' );
		return '' !== $n ? $n : 'field';
	}

	/** Does the rule's raw condition test evaluate TRUE against the posted data? */
	public static function passes( array $rule, array $posted ): bool {
		$key      = self::cf7_name( $rule['field'] ?? '' );
		$op       = $rule['operator'] ?? 'is';
		$expected = (string) ( $rule['value'] ?? '' );

		$actual = $posted[ $key ] ?? '';
		$list   = is_array( $actual ) ? array_map( 'strval', $actual ) : [ (string) $actual ];
		$joined = trim( implode( '', $list ) );

		switch ( $op ) {
			case 'is':
				return in_array( $expected, $list, true );
			case 'is_not':
				return ! in_array( $expected, $list, true );
			case 'contains':
				foreach ( $list as $a ) {
					if ( '' !== $expected && false !== strpos( $a, $expected ) ) {
						return true;
					}
				}
				return false;
			case 'not_contains':
				foreach ( $list as $a ) {
					if ( '' !== $expected && false !== strpos( $a, $expected ) ) {
						return false;
					}
				}
				return true;
			case 'gt':
				foreach ( $list as $a ) {
					if ( is_numeric( $a ) && is_numeric( $expected ) && ( 0 + $a ) > ( 0 + $expected ) ) {
						return true;
					}
				}
				return false;
			case 'lt':
				foreach ( $list as $a ) {
					if ( is_numeric( $a ) && is_numeric( $expected ) && ( 0 + $a ) < ( 0 + $expected ) ) {
						return true;
					}
				}
				return false;
			case 'empty':
				return '' === $joined;
			case 'filled':
				return '' !== $joined;
		}
		return true;
	}

	public static function is_visible( $rule, array $posted ): bool {
		if ( ! is_array( $rule ) || empty( $rule['enabled'] ) || empty( $rule['field'] ) ) {
			return true;
		}
		$passes = self::passes( $rule, $posted );
		return ( 'hide' === ( $rule['action'] ?? 'show' ) ) ? ! $passes : $passes;
	}

	public static function hidden_field_names( array $schema, array $posted ): array {
		$hidden = [];

		$repeaters = [];
		foreach ( (array) ( $schema['fields'] ?? [] ) as $f ) {
			if ( is_array( $f ) && 'repeater' === ( $f['type'] ?? '' ) && ! empty( $f['id'] ) ) {
				$repeaters[] = (string) $f['id'];
			}
		}

		foreach ( (array) ( $schema['fields'] ?? [] ) as $f ) {
			if ( ! is_array( $f ) || empty( $f['name'] ) ) {
				continue;
			}
			if ( ! empty( $f['parentId'] ) && in_array( (string) $f['parentId'], $repeaters, true ) ) {
				continue;
			}
			$rule = $f['conditions'] ?? null;
			if ( ! is_array( $rule ) || empty( $rule['enabled'] ) ) {
				continue;
			}
			if ( ! self::is_visible( $rule, $posted ) ) {
				$hidden[] = self::cf7_name( $f['name'] );
			}
		}
		return array_values( array_unique( $hidden ) );
	}
}
