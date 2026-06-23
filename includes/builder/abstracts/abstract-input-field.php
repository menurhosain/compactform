<?php

namespace CompactForm\Builder\Abstracts;


use CompactForm\Builder\Field_Helpers;

defined( 'ABSPATH' ) || die();

abstract class Input_Field extends Base_Field {

	abstract protected function get_tag(): string;

	protected function star_supported(): bool {
		return true;
	}

	public function compile( array $data ): string {
		$tag  = $this->get_tag();
		$star = ( $this->star_supported() && ! empty( $data['required'] ) ) ? '*' : '';
		$name = Field_Helpers::name( $data );

		$markup = "[{$tag}{$star} {$name}" . Field_Helpers::atts( $data, true ) . ']';

		return Field_Helpers::label_wrap( $data, $markup );
	}
}
