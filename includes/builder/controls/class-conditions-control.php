<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Conditions_Control extends Base_Control {

	private const OPS     = [ 'is', 'is_not', 'contains', 'not_contains', 'gt', 'lt', 'empty', 'filled' ];
	private const ACTIONS = [ 'show', 'hide' ];

	public function get_type(): string {
		return 'conditions';
	}

	public function sanitize( $value, array $args = [] ) {
		$out = $this->get_default();
		if ( ! is_array( $value ) ) {
			return $out;
		}

		$out['enabled']  = ! empty( $value['enabled'] );
		$out['action']   = in_array( $value['action'] ?? '', self::ACTIONS, true ) ? $value['action'] : 'show';
		$out['operator'] = in_array( $value['operator'] ?? '', self::OPS, true ) ? $value['operator'] : 'is';
		$out['value']    = sanitize_text_field( (string) ( $value['value'] ?? '' ) );

		// Normalise the controlling field name the same way a CF7 tag name is
		// compiled, so it matches the posted-data key / input name attribute.
		$field         = strtolower( (string) ( $value['field'] ?? '' ) );
		$field         = preg_replace( '/[^a-z0-9_\-]/', '-', $field );
		$out['field']  = trim( $field, '-' );

		return $out;
	}

	public function get_default() {
		return [ 'enabled' => false, 'action' => 'show', 'field' => '', 'operator' => 'is', 'value' => '' ];
	}

	/** Flattened to the controlling field name (the closest single "value"). */
	public function get_option_value_stringify( $value ): string {
		return is_array( $value ) ? (string) ( $value['field'] ?? '' ) : '';
	}
}
