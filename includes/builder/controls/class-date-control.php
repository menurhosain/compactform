<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

/** A date value (YYYY-MM-DD) — rendered as a native date input. */
class Date_Control extends Base_Control {
	public function get_type(): string {
		return 'date';
	}

	public function get_option_value_stringify( $value ): string {
		return (string) $value;
	}
}
