<?php

namespace CompactForm\Builder\Fields;

use CompactForm\Builder\Controls_Manager;

use CompactForm\Builder\Abstracts\Base_Field;
use CompactForm\Builder\Abstracts\Traits\Label_Control;
use CompactForm\Builder\Abstracts\Traits\Name_Control;
use CompactForm\Builder\Abstracts\Traits\Description_Control;
use CompactForm\Builder\Abstracts\Traits\Required_Control;
use CompactForm\Builder\Abstracts\Traits\Width_Control;
use CompactForm\Builder\Abstracts\Traits\Flex_Item_Control;
use CompactForm\Builder\Abstracts\Traits\Label_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Field_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Description_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Advanced_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Control;
use CompactForm\Builder\Field_Helpers;

defined( 'ABSPATH' ) || die();

class File_Field extends Base_Field {

	use Label_Control;
	use Name_Control;
	use Description_Control;
	use Required_Control;
	use Width_Control;
	use Flex_Item_Control;
	use Label_Style_Control;
	use Field_Style_Control;
	use Description_Style_Control;
	use Advanced_Control;
	use Conditional_Control;

	public function get_type(): string {
		return 'file';
	}

	public function get_title(): string {
		return __( 'File Upload', 'compactform' );
	}

	public function get_icon(): string {
		return 'ri-upload-2-line';
	}

	public function get_category(): string {
		return 'basic';
	}

	public function get_preview(): string {
		return 'file';
	}

	protected function register_controls(): void {
		$this->start_section( 'general', [
			'label' => __( 'General', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
		$this->register_label_control();
		$this->register_name_control();
		$this->register_description_control();
		$this->register_required_control();
		$this->register_width_control();
		$this->register_flex_item_controls();
		$this->end_section();

		$this->start_section( 'upload', [
			'label' => __( 'Upload', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );

		$this->add_control( 'fileTypes', [
			'type'     => Controls_Manager::SELECT,
			'label'    => __( 'Allowed File Types', 'compactform' ),
			'multiple' => true,
			'create' => true,
			'default' => ['jpg', 'jpeg', 'pdf'],
			'options' => [
				'jpg' => esc_html__( 'jpg', 'compactform' ),
				'jpeg' => esc_html__( 'jpeg', 'compactform' ),
				'png' => esc_html__( 'png', 'compactform' ),
				'pdf' => esc_html__( 'pdf', 'compactform' ),
				'doc' => esc_html__( 'doc', 'compactform' ),
			],
		] );

		$this->add_control( 'fileLimit', [
			'type' => Controls_Manager::SLIDER,
			'label' => __( 'Max File Size', 'compactform' ),
			'hint'  => __( 'Blank uses CF7\'s 1 MB default.', 'compactform' ),
			'units' => [ 'mb', 'kb' ],
			'min'   => 0,
			'max'   => 128,
			'step'  => 1
		] );

		$this->end_section();

		$this->register_label_style_controls();
		$this->register_field_style_controls( false, true );
		$this->register_description_style_controls();
		$this->register_advanced_controls();
		$this->register_conditional_controls();
	}

	private function file_types( array $data ): string {
		$raw = $data['fileTypes'] ?? [];
		$list = is_array( $raw ) ? $raw : preg_split( '/[\s,|]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY );

		$clean = [];
		foreach ( (array) $list as $part ) {
			$part = preg_replace( '/[^a-z0-9]/', '', strtolower( trim( (string) $part ) ) );
			if ( '' !== $part ) {
				$clean[ $part ] = $part; 
			}
		}

		return implode( '|', $clean );
	}

	public function compile( array $data ): string {
		$star = ! empty( $data['required'] ) ? '*' : '';
		$name = Field_Helpers::name( $data );

		$markup = '[file' . $star . ' ' . $name;

		$types = $this->file_types( $data );
		if ( '' !== $types ) {
			$markup .= ' filetypes:' . $types;
		}

		$limit = $data['fileLimit'] ?? null;
		if ( is_array( $limit ) && '' !== ( $limit['size'] ?? '' ) && is_numeric( $limit['size'] ) && $limit['size'] > 0 ) {
			$unit    = in_array( $limit['unit'] ?? 'mb', [ 'mb', 'kb' ], true ) ? $limit['unit'] : 'mb';
			$markup .= ' limit:' . ( 0 + $limit['size'] ) . $unit;
		}

		$markup .= Field_Helpers::atts( $data, false ) . ']';

		return Field_Helpers::label_wrap( $data, $markup );
	}
}
