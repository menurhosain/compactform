<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Switcher_Control extends Base_Control {
	public function get_type(): string {
		return 'switcher';
	}

	public function sanitize( $value, array $args = [] ) {
		return (bool) $value;
	}

	public function get_default() {
		return false;
	}

	public function get_option_value_stringify( $value ): string {
		return $value ? '1' : '';
	}
}
