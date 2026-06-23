<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

/** A time value (HH:MM) — rendered as a native time input. */
class Time_Control extends Base_Control {
	public function get_type(): string {
		return 'time';
	}

	public function get_option_value_stringify( $value ): string {
		return (string) $value;
	}
}
