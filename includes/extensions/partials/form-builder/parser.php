<?php
/**
 * Contact Form 7 markup -> JSON schema parser (reverse direction).
 */

namespace CompactForm\Extensions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Builder_Parser {

	public static function parse( $markup ) {
		$fields = [];

		if ( ! is_string( $markup ) || '' === trim( $markup ) ) {
			return self::schema( $fields );
		}

		$pattern = '/\[([a-z][a-z0-9_]*)(\*?)([^\]]*)\]([^\[\n<]*)/';

		if ( preg_match_all( $pattern, $markup, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $m ) {
				$type      = $m[1];
				$required  = '*' === $m[2];
				$remainder = isset( $m[3] ) ? trim( $m[3] ) : '';
				$after     = isset( $m[4] ) ? trim( $m[4] ) : '';

				if ( 'submit' === $type ) {
					$name     = '';
					$raw_atts = $remainder;
				} elseif ( preg_match( '/^([a-zA-Z0-9_\-]+)(.*)$/s', $remainder, $rm ) ) {
					$name     = $rm[1];
					$raw_atts = $rm[2];
				} else {
					$name     = '';
					$raw_atts = $remainder;
				}

				$built = self::build_field( $type, $required, $name, $raw_atts, $after );
				if ( null !== $built ) {
					$fields[] = $built;
				}
			}
		}

		return self::schema( $fields );
	}

	protected static function build_field( $type, $required, $name, $raw_atts, $after ) {
		if ( 'fcf7_container' === $type ) {
			return null;
		}

		$specs = (array) apply_filters( 'fcf7_builder_fields', [] );
		if ( isset( $specs[ $type ] ) ) {
			$spec  = $specs[ $type ];
			$field = [
				'id'       => self::id(),
				'type'     => $type,
				'name'     => $name,
				'label'    => self::humanize( $name ),
				'required' => $required,
				'width'    => 100,
				'cssClass' => '',
			];
			foreach ( (array) ( isset( $spec['options'] ) ? $spec['options'] : [] ) as $o ) {
				if ( preg_match( '/(?:^|\s)' . preg_quote( $o['opt'], '/' ) . ':([^\s\]]+)/', $raw_atts, $om ) ) {
					$field[ $o['prop'] ] = ( isset( $o['type'] ) && 'bool' === $o['type'] ) ? true : $om[1];
				}
			}
			if ( preg_match_all( '/class:([A-Za-z0-9_\-]+)/', $raw_atts, $cm ) ) {
				$field['cssClass'] = implode( ' ', $cm[1] );
			}
			return $field;
		}

		// Every native CF7 tag the builder has a field for.
		$known = [ 'text', 'email', 'url', 'tel', 'number', 'date', 'textarea', 'select', 'radio', 'checkbox', 'acceptance', 'file', 'quiz', 'submit' ];

		if ( ! in_array( $type, $known, true ) ) {
			// The unknown tag rides through as a Raw HTML field
			return [
				'id'   => self::id(),
				'type' => 'html',
				'name' => $name,
				'html' => "[{$type}" . ( $required ? '*' : '' ) . " {$name}{$raw_atts}]",
			];
		}

		$field = [
			'id'          => self::id(),
			'type'        => $type,
			'name'        => $name,
			'label'       => self::humanize( $name ),
			'required'    => $required,
			'width'       => 100,
			'cssClass'    => '',
			'placeholder' => '',
			'defaultValue'=> '',
			'description' => '',
		];

		$quoted = [];
		if ( preg_match_all( '/"([^"]*)"/', $raw_atts, $qm ) ) {
			$quoted = $qm[1];
		}

		if ( preg_match_all( '/class:([A-Za-z0-9_\-]+)/', $raw_atts, $cm ) ) {
			$field['cssClass'] = implode( ' ', $cm[1] );
		}

		if ( preg_match( '/id:([A-Za-z0-9_\-]+)/', $raw_atts, $im ) ) {
			$field['fieldId'] = $im[1];
		}

		if ( in_array( $type, [ 'select', 'radio', 'checkbox' ], true ) ) {
			$field['options'] = $quoted ? $quoted : [ 'Option 1', 'Option 2' ];
		} elseif ( 'quiz' === $type ) {
			$pairs = [];
			foreach ( $quoted as $pair ) {
				if ( false === strpos( $pair, '|' ) ) {
					continue;
				}
				list( $question, $answer ) = array_map( 'trim', explode( '|', $pair, 2 ) );
				if ( '' !== $question && '' !== $answer ) {
					$pairs[] = $question . ' | ' . $answer;
				}
			}
			$field['options'] = $pairs;
		} elseif ( 'file' === $type ) {
			if ( preg_match( '/filetypes:([^\s\]]+)/i', $raw_atts, $fm ) ) {
				$types = [];
				foreach ( preg_split( '/[|,]/', strtolower( $fm[1] ), -1, PREG_SPLIT_NO_EMPTY ) as $ext ) {
					$ext = preg_replace( '/[^a-z0-9]/', '', $ext );
					if ( '' !== $ext ) {
						$types[ $ext ] = $ext;
					}
				}
				if ( $types ) {
					$field['fileTypes'] = array_values( $types );
				}
			}
			if ( preg_match( '/limit:(\d+)(mb|kb)?/i', $raw_atts, $lm ) ) {
				$field['fileLimit'] = isset( $lm[2] ) && '' !== $lm[2]
					? [ 'size' => $lm[1], 'unit' => strtolower( $lm[2] ) ]
					: [ 'size' => (string) max( 1, (int) round( (int) $lm[1] / 1024 ) ), 'unit' => 'kb' ];
			}
		} elseif ( 'submit' === $type ) {
			$field['label'] = $quoted ? $quoted[0] : 'Send';
		} elseif ( 'acceptance' === $type ) {
			$field['label'] = $quoted ? $quoted[0] : ( $after ? $after : 'I accept the terms and conditions.' );
		} else {
			if ( false !== strpos( $raw_atts, 'placeholder' ) && $quoted ) {
				$field['placeholder'] = $quoted[0];
			} elseif ( $quoted ) {
				$field['defaultValue'] = $quoted[0];
			}
		}

		return $field;
	}

	protected static function schema( $fields ) {
		return [
			'version' => 1,
			'fields'  => $fields,
		];
	}

	protected static function humanize( $name ) {
		$name = str_replace( [ '-', '_' ], ' ', $name );
		$name = preg_replace( '/^your\s+/i', '', $name );
		return ucwords( trim( $name ) );
	}

	protected static function id() {
		return 'f_' . substr( md5( uniqid( '', true ) ), 0, 8 );
	}
}
