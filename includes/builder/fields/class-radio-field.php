<?php

namespace CompactForm\Builder\Fields;


use CompactForm\Builder\Controls_Manager;
use CompactForm\Builder\Abstracts\Choice_Field;
use CompactForm\Builder\Abstracts\Traits\Label_Control;
use CompactForm\Builder\Abstracts\Traits\Name_Control;
use CompactForm\Builder\Abstracts\Traits\Description_Control;
use CompactForm\Builder\Abstracts\Traits\Required_Control;
use CompactForm\Builder\Abstracts\Traits\Width_Control;
use CompactForm\Builder\Abstracts\Traits\Flex_Item_Control;
use CompactForm\Builder\Abstracts\Traits\Choices_Control;
use CompactForm\Builder\Abstracts\Traits\Label_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Description_Style_Control;
use CompactForm\Builder\Abstracts\Traits\List_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Advanced_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Control;

defined( 'ABSPATH' ) || die();

class Radio_Field extends Choice_Field {

	use Label_Control;
	use Name_Control;
	use Description_Control;
	use Required_Control;
	use Width_Control;
	use Flex_Item_Control;
	use Choices_Control;
	use Label_Style_Control;
	use Description_Style_Control;
	use List_Style_Control;
	use Advanced_Control;
	use Conditional_Control;

	protected function get_tag(): string { return 'radio'; }
	public function get_type(): string { return 'radio'; }
	public function get_title(): string { return __( 'Radio', 'compactform' ); }
	public function get_icon(): string { return 'ri-radio-button-line'; }

	protected function register_controls(): void {
		// General TAB_SETTINGS
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

		// Choices TAB_SETTINGS
		$this->register_choices_control();

		$this->start_section( 'choices', [
			'label' => __( 'Options', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );

		do_action( 'fcf7_radio_field_pricing_controls', $this, 'choices' );
		$this->end_section();

		$this->start_section( 'pricing', [
			'label'     => __( 'Pricing', 'compactform' ),
			'tab'       => Controls_Manager::TAB_SETTINGS,
			'condition' => [ 'enable_pricing' => true ],
		] );
		do_action( 'fcf7_radio_field_pricing_controls', $this, 'pricing' );
		$this->end_section();

		// Label TAB_Style
		$this->register_label_style_controls();

		// Description TAB_Style
		$this->register_description_style_controls();

		// List TAB_Style
		$this->register_list_style_controls();

		// Advance Controls TAB_ADVANCED
		$this->register_advanced_controls();
		
		// Conditional Controls TAB_ADVANCED
		$this->register_conditional_controls();
	}

	public function add_pricing_control( string $key, array $args ): void {
		$this->add_control( $key, $args );
	}
}
