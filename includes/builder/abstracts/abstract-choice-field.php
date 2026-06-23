<?php

namespace CompactForm\Builder\Abstracts;

use CompactForm\Builder\Field_Helpers;

defined( 'ABSPATH' ) || die();

abstract class Choice_Field extends Base_Field {

	abstract protected function get_tag(): string;

	public function get_category(): string {
		return 'choice';
	}

	public function get_preview(): string {
		return $this->get_tag(); // select | radio | checkbox
	}

	protected function is_list(): bool {
		return 'select' !== $this->get_tag();
	}

	public function compile( array $data ): string {
		$tag = $this->get_tag();
		$star = ( 'radio' !== $tag && ! empty( $data['required'] ) ) ? '*' : '';
		$name = Field_Helpers::name( $data );

		$markup = "[{$tag}{$star} {$name}" . Field_Helpers::atts( $data, false );

		// Select's default/first option (CF7 include_blank / first_as_label).
		$prepend  = [];
		$multiple = 'select' === $tag && ! empty( $data['multiple'] );

		if ( $multiple ) {
			$markup .= ' multiple';
		}

		if ( 'select' !== $tag ) {
			$markup .= ' use_label_element';
		}

		if ( 'select' === $tag && ! $multiple ) {
			$first = (string) ( $data['first_option'] ?? '' );
			if ( 'blank' === $first ) {
				$markup .= ' include_blank';
			} elseif ( 'label' === $first ) {
				$text = trim( (string) ( $data['first_option_text'] ?? '' ) );
				if ( '' !== $text ) {
					$markup   .= ' first_as_label';
					$prepend[] = $text;
				}
			}
		}

		$options = Field_Helpers::options( $data );

		if ( 'select' !== $tag ) {
			$indexes = [];
			foreach ( (array) ( $data['default_option'] ?? [] ) as $value ) {
				$pos = array_search( trim( (string) $value ), $options, true );
				if ( false !== $pos ) {
					$indexes[] = $pos + 1;
				}
			}
			if ( $indexes ) {
				$markup .= ' default:' . implode( '_', $indexes );
			}
		}

		foreach ( array_merge( $prepend, $options ) as $option ) {
			$markup .= ' "' . Field_Helpers::q( $option ) . '"';
		}
		$markup .= ']';

		return Field_Helpers::label_wrap( $data, $markup, $this->is_list() );
	}
}
