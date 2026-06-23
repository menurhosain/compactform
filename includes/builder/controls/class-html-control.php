<?php

namespace CompactForm\Builder\Controls;

use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Html_Control extends Base_Control {

	public function get_type(): string {
		return 'html';
	}

	public function sanitize( $value, array $args = [] ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$html = (string) $value;

		return current_user_can( 'unfiltered_html' ) ? $html : wp_kses_post( $html );
	}

	public function get_default() {
		return '';
	}

	public function get_option_value_stringify( $value ): string {
		return (string) $value;
	}

	public function get_css( $value, string $template ): string {
		return '';
	}
}
