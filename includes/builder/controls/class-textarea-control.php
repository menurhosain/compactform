<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

/** Multi-line text. */
class Textarea_Control extends Base_Control {
	public function get_type(): string {
		return 'textarea';
	}

	public function sanitize( $value, array $args = [] ) {
		return sanitize_textarea_field( (string) $value );
	}

	public function get_option_value_stringify( $value ): string {
		return (string) $value;
	}
}
