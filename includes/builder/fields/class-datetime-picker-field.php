<?php

namespace CompactForm\Builder\Fields;

use CompactForm\Builder\Controls_Manager;

use CompactForm\Builder\Abstracts\Extension_Field;
use CompactForm\Builder\Abstracts\Traits\Label_Control;
use CompactForm\Builder\Abstracts\Traits\Name_Control;
use CompactForm\Builder\Abstracts\Traits\Placeholder_Control;
use CompactForm\Builder\Abstracts\Traits\Required_Control;
use CompactForm\Builder\Abstracts\Traits\Width_Control;
use CompactForm\Builder\Abstracts\Traits\Flex_Item_Control;
use CompactForm\Builder\Abstracts\Traits\Label_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Field_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Advanced_Control;
use CompactForm\Builder\Abstracts\Traits\Field_Icon_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Control;

defined( 'ABSPATH' ) || die();

class Datetime_Picker_Field extends Extension_Field {

	use Label_Control;
	use Name_Control;
	use Placeholder_Control;
	use Required_Control;
	use Width_Control;
	use Flex_Item_Control;
	use Label_Style_Control;
	use Field_Style_Control;
	use Advanced_Control;
	use Field_Icon_Control;
	use Conditional_Control;

	/** Config keys read from the tag options and handed to the frontend init script. */
	private $option_keys = [ 'mode', 'date_format', 'time_format', 'interval', 'min_date', 'max_date', 'default_date', 'default_time' ];

	public function get_type(): string { return 'fcf7_datetime_picker'; }
	public function get_title(): string { return __( 'Date & Time', 'compactform' ); }
	public function get_icon(): string { return 'ri-calendar-schedule-line'; }
	protected function get_tag(): string { return 'fcf7_datetime_picker'; }

	protected function register_controls(): void {
		$this->start_section( 'general', [
			'label' => __( 'General', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
		$this->register_label_control();
		$this->register_name_control();
		$this->register_placeholder_control();
		$this->register_required_control();
		$this->register_width_control();
		$this->register_flex_item_controls();
		$this->end_section();

		$this->start_section( 'date', [
			'label' => __( 'Date', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
		$this->add_control( 'mode', [
			'type' => Controls_Manager::SELECT,
			'label' => __( 'Mode', 'compactform' ),
			'options' => [ 'single' => 'Single', 'multiple' => 'Multiple', 'range' => 'Range' ],
			'default' => 'single'
		] );
		$this->add_control( 'date_format', [
			'type' => Controls_Manager::SELECT,
			'label' => __( 'Date format', 'compactform' ),
			'options' => [ 'd-m-Y' => 'd-m-Y', 'Y-m-d' => 'Y-m-d', 'm/d/Y' => 'm/d/Y', 'd/m/Y' => 'd/m/Y' ],
			'default' => 'd-m-Y'
		] );
		$this->add_control( 'min_date', [
			'type' => Controls_Manager::DATE,
			'label' => __( 'Min date', 'compactform' )
		] );
		$this->add_control( 'max_date', [
			'type' => Controls_Manager::DATE,
			'label' => __( 'Max date', 'compactform' )
		] );
		$this->add_control( 'default_date', [
			'type' => Controls_Manager::DATE,
			'label' => __( 'Default date', 'compactform' )
		] );
		$this->end_section();

		$this->start_section( 'time', [
			'label' => __( 'Time', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
		$this->add_control( 'time_format', [
			'type' => Controls_Manager::SELECT,
			'label' => __( 'Time format', 'compactform' ),
			'options' => [ 'H:i' => 'H:i', 'H:i:s' => 'H:i:s', 'h:i K' => 'h:i K' ],
			'default' => 'H:i'
		] );
		$this->add_control( 'interval', [
			'type' => Controls_Manager::NUMBER,
			'label' => __( 'Interval (min)', 'compactform' )
		] );
		$this->add_control( 'default_time', [
			'type' => Controls_Manager::TIME,
			'label' => __( 'Default time', 'compactform' )
		] );
		$this->end_section();

		$this->register_label_style_controls();
		$this->register_field_style_controls( false, true );
		$this->register_advanced_controls();
		$this->register_field_icon_controls();
		$this->register_conditional_controls();
	}

	protected function option_map(): array {
		return [
			[ 'prop' => 'mode', 'opt' => 'mode' ],
			[ 'prop' => 'date_format', 'opt' => 'date_format' ],
			[ 'prop' => 'time_format', 'opt' => 'time_format' ],
			[ 'prop' => 'interval', 'opt' => 'interval' ],
			[ 'prop' => 'min_date', 'opt' => 'min_date' ],
			[ 'prop' => 'max_date', 'opt' => 'max_date' ],
			[ 'prop' => 'default_date', 'opt' => 'default_date' ],
			[ 'prop' => 'default_time', 'opt' => 'default_time' ],
		];
	}

	protected function register_style(): array {
		return [ 'flatpickr' ];
	}

	protected function register_script(): array {
		wp_register_script( 'fcf7-datetime-picker', FCF7_ASSETS . 'js/datetime-picker.min.js', [ 'flatpickr' ], FCF7_VERSION, true );
		return [ 'fcf7-datetime-picker' ];
	}


	protected function register_preview_style(): array {
		return [ 'flatpickr' ];
	}

	protected function register_preview_script(): array {
		wp_register_script( 'fcf7-datetime-picker', FCF7_ASSETS . 'js/datetime-picker.min.js', [ 'flatpickr', 'jquery' ], FCF7_VERSION, true );
		return [ 'fcf7-datetime-picker' ];
	}


	public function render_front( $tag, $form_id = 0 ) {
		if ( empty( $tag->name ) ) {
			return '';
		}

		$class = wpcf7_form_controls_class( $tag->type );
		$class .= ' fcf7-datetimepicker';

		$atts = [
			'class' => $tag->get_class_option( $class ),
			'id'    => $tag->get_id_option(),
			'name'  => $tag->name,
			'type'  => 'text',
		];

		$placeholder = $tag->get_option( 'placeholder', '', true );
		if ( $placeholder ) {
			$atts['placeholder'] = $placeholder;
		} elseif ( ! empty( $tag->values ) ) {
			$atts['placeholder'] = $tag->values[0];
		}

		if ( $tag->is_required() ) {
			$atts['aria-required'] = 'true';
		}
		$atts['aria-invalid'] = wpcf7_get_validation_error( $tag->name ) ? 'true' : 'false';

		// Collect the picker config from the tag options.
		$config = [];
		foreach ( $this->option_keys as $key ) {
			$val = '';
			if ( ! empty( $tag->options ) ) {
				foreach ( $tag->options as $opt ) {
					if ( strpos( $opt, ':' ) !== false ) {
						list( $k, $v ) = explode( ':', $opt, 2 );
						if ( trim( $k ) === $key ) {
							$val = trim( $v );
							break;
						}
					}
				}
			}
			$config[ $key ] = $val;
		}

		$atts['data-config'] = wp_json_encode( $config );

		$atts = wpcf7_format_atts( $atts );

		$output = sprintf(
			'<span class="wpcf7-form-control-wrap %1$s fcf7-field-picker-wrapper" data-name="%1$s"><input %2$s /></span>',
			esc_attr( $tag->name ),
			$atts
		);

		return apply_filters( 'fcf7_datetime_picker_output', $output, $tag );
	}

	public function validate_req_data( $schema, $tag, array $form_schema = array(), $contact_form = null ) {
		if ( ! $tag->is_required() ) {
			return;
		}

		$schema->add_rule(
			wpcf7_swv_create_rule( 'required', [
				'field' => $tag->name,
				'error' => wpcf7_get_message( 'invalid_required' ),
			] )
		);
	}
}
