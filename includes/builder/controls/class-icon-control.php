<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

/** A Remixicon class (searchable icon picker). */
class Icon_Control extends Base_Control {
	public function get_type(): string {
		return 'icon';
	}

	public function sanitize( $value, array $args = [] ) {
		return trim( preg_replace( '/[^a-z0-9\- ]/i', '', (string) $value ) );
	}

	public function get_option_value_stringify( $value ): string {
		return (string) $value;
	}
}
