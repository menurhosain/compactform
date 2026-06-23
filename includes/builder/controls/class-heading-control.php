<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Heading_Control extends Base_Control {

	public function get_type(): string {
		return 'heading';
	}

	public function sanitize( $value, array $args = [] ) {
		return ''; // no value to keep.
	}

	public function get_default() {
		return '';
	}

	public function get_option_value_stringify( $value ): string {
		return '';
	}
}
