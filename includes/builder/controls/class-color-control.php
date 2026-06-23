<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined('ABSPATH') || die();

/** A hex colour (or empty = inherit). */
class Color_Control extends Base_Control {
	public function get_type(): string {
		return 'color';
	}

	public function sanitize($value, array $args = []) {
		$value = trim((string) $value);
		return preg_match('/^#[a-f0-9]{3,8}$/i', $value) ? $value : '';
	}

	public function get_option_value_stringify($value): string {
		return $value;
	}
}
