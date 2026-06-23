<?php
/** "Field Icon" section (Advanced tab). Self-contained — opens and closes its own section. */

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Field_Icon_Control {

	protected function register_field_icon_controls(): void {
		$this->start_section(
			'section_field_icon',
			[
				'label' => __( 'Field Icon', 'compactform' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);
		$this->add_control(
			'field_icon',
			[
				'type'  => Controls_Manager::ICON,
				'label' => __( 'Icon', 'compactform' ),
			]
		);

		$this->add_control(
			'field_icon_color',
			[
				'label'     => esc_html__( 'Color', 'compactform' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .fcf7-field-icon i' => 'color: {{VALUE}};',
				],
				'condition' => [
					'field_icon!' => '',
				],
			]
		);
		$this->add_control(
			'field_icon_size',
			[
				'type'       => Controls_Manager::SLIDER,
				'label'      => __( 'Size', 'compactform' ),
				'units'      => [ 'px', 'em', 'rem', 'custom' ],
				'min'        => 0,
				'max'        => 100,
				'responsive' => true,
				'condition'  => [
					'field_icon!' => '',
				],
				'selectors'  => [
					'{{WRAPPER}} .fcf7-field-icon i' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_popover(
			'field_icon_position_popover',
			[
				'label'     => __( 'Position', 'compactform' ),
				'condition' => [
					'field_icon!' => '',
				],
			]
		);
		$this->add_control(
			'field_icon_position',
			[
				'type'        => Controls_Manager::SELECT,
				'label'       => __( 'Position', 'compactform' ),
				'label_block' => false,
				'options'     => [
					''         => __( 'Default', 'compactform' ),
					'relative' => __( 'Relative', 'compactform' ),
					'absolute' => __( 'Absolute', 'compactform' ),
					'unset'    => __( 'Unset', 'compactform' ),
				],
				'default'     => 'absolute',
				'responsive'  => true,
				'selectors'   => [
					'{{WRAPPER}} .fcf7-field-icon i' => 'position: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'field_icon_p_left',
			[
				'type'       => Controls_Manager::SLIDER,
				'label'      => __( 'Left', 'compactform' ),
				'units'      => [ 'px', 'em', 'rem', 'custom' ],
				'min'        => 0,
				'max'        => 100,
				'responsive' => true,
				'condition'  => [
					'field_icon_position' => [ 'relative', 'absolute' ],
				],
				'selectors'  => [
					'{{WRAPPER}} .fcf7-field-icon i' => 'inset-inline-start: {{SIZE}}{{UNIT}}; transform: unset;',
				],
			]
		);
		$this->add_control(
			'field_icon_p_top',
			[
				'type'       => Controls_Manager::SLIDER,
				'label'      => __( 'Top', 'compactform' ),
				'units'      => [ 'px', 'em', 'rem', 'custom' ],
				'min'        => 0,
				'max'        => 100,
				'responsive' => true,
				'condition'  => [
					'field_icon_position' => [ 'relative', 'absolute' ],
				],
				'selectors'  => [
					'{{WRAPPER}} .fcf7-field-icon i' => 'inset-block-start: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'field_icon_p_right',
			[
				'type'       => Controls_Manager::SLIDER,
				'label'      => __( 'Right', 'compactform' ),
				'units'      => [ 'px', 'em', 'rem', 'custom' ],
				'min'        => 0,
				'max'        => 100,
				'responsive' => true,
				'condition'  => [
					'field_icon_position' => [ 'relative', 'absolute' ],
				],
				'selectors'  => [
					'{{WRAPPER}} .fcf7-field-icon i' => 'inset-inline-end: {{SIZE}}{{UNIT}};',
				],
			]
		);
		$this->add_control(
			'field_icon_p_bottom',
			[
				'type'       => Controls_Manager::SLIDER,
				'label'      => __( 'Bottom', 'compactform' ),
				'units'      => [ 'px', 'em', 'rem', 'custom' ],
				'min'        => 0,
				'max'        => 100,
				'responsive' => true,
				'condition'  => [
					'field_icon_position' => [ 'relative', 'absolute' ],
				],
				'selectors'  => [
					'{{WRAPPER}} .fcf7-field-icon i' => 'inset-block-start: {{SIZE}}{{UNIT}}; transform: unset;',
				],
			]
		);
		$this->end_popover();
		$this->end_section();
	}
}
