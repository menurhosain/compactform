<?php

namespace CompactForm\Builder\Fields;


use CompactForm\Builder\Controls_Manager;
use CompactForm\Builder\Abstracts\Input_Field;
use CompactForm\Builder\Abstracts\Traits\Label_Control;
use CompactForm\Builder\Abstracts\Traits\Name_Control;
use CompactForm\Builder\Abstracts\Traits\Placeholder_Control;
use CompactForm\Builder\Abstracts\Traits\Default_Value_Control;
use CompactForm\Builder\Abstracts\Traits\Description_Control;
use CompactForm\Builder\Abstracts\Traits\Required_Control;
use CompactForm\Builder\Abstracts\Traits\Width_Control;
use CompactForm\Builder\Abstracts\Traits\Flex_Item_Control;
use CompactForm\Builder\Abstracts\Traits\Label_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Field_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Description_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Advanced_Control;
use CompactForm\Builder\Abstracts\Traits\Field_Icon_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Control;

defined( 'ABSPATH' ) || die();

class Number_Field extends Input_Field {

	use Label_Control;
	use Name_Control;
	use Placeholder_Control;
	use Default_Value_Control;
	use Description_Control;
	use Required_Control;
	use Width_Control;
	use Flex_Item_Control;
	use Label_Style_Control;
	use Field_Style_Control;
	use Description_Style_Control;
	use Advanced_Control;
	use Field_Icon_Control;
	use Conditional_Control;

	protected function get_tag(): string { return 'number'; }
	public function get_type(): string { return 'number'; }
	public function get_title(): string { return __( 'Number', 'compactform' ); }
	public function get_icon(): string { return 'ri-hashtag'; }

	protected function register_controls(): void {
		$this->start_section( 'general', [
			'label' => __( 'General', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
		$this->register_label_control();
		$this->register_name_control();
		$this->register_placeholder_control();
		$this->register_default_value_control();
		$this->register_description_control();
		$this->register_required_control();
		$this->register_width_control();
		$this->register_flex_item_controls();
		$this->end_section();

		$this->register_label_style_controls();
		$this->register_field_style_controls( true, true );
		$this->register_description_style_controls();
		$this->register_advanced_controls();
		$this->register_field_icon_controls();
		$this->register_conditional_controls();
	}
}
