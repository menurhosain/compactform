<?php

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Label_Style_Control {

	protected function register_label_style_controls(): void {
		$label_sel = '{{WRAPPER}} .fcf7b-field-label';

		$this->start_section(
			'section_label_style',
			[
				'label'     => __( 'Label', 'compactform' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'label!' => '',
				],
			]
		);

		$this->add_control(
			'label_typography',
			[
				'type'       => Controls_Manager::TYPOGRAPHY,
				'label'      => __( 'Typography', 'compactform' ),
				'selector'   => $label_sel,
				'responsive' => true,
			]
		);
		$this->add_control(
			'label_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'compactform' ),
				'selectors' => [
					$label_sel => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'label_align',
			[
				'type'        => Controls_Manager::CHOOSE,
				'label'       => __( 'Alignment', 'compactform' ),
				'label_block' => false,
				'toggle'      => true,
				'options'     => [
					'left'   => [
						'title' => __( 'Left', 'compactform' ),
						'icon'  => 'ri-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'compactform' ),
						'icon'  => 'ri-align-center',
					],
					'right'  => [
						'title' => __( 'Right', 'compactform' ),
						'icon'  => 'ri-align-right',
					],
				],
				'responsive' => true,
				'selectors'  => [
					$label_sel => 'text-align: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'label_margin',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Margin', 'compactform' ),
				'selectors'  => [
					$label_sel => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'responsive' => true,
			]
		);
		$this->add_control(
			'label_asterisk_heading',
			[
				'type'      => Controls_Manager::HEADING,
				'label'     => __( 'Marker (Required)', 'compactform' ),
				'separator' => 'before',
				'condition' => [
					'required' => true,
				],
			]
		);
		$this->add_control(
			'label_asterisk_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'compactform' ),
				'selectors' => [
					$label_sel . ' .fcf7b-req' => 'color: {{VALUE}};',
				],
				'condition' => [
					'required' => true,
				],
			]
		);
		$this->add_control(
			'label_asterisk_gap',
			[
				'type'       => Controls_Manager::SLIDER,
				'label'      => __( 'Gap', 'compactform' ),
				'units'      => [ 'px', 'em', 'rem', 'custom' ],
				'min'        => 0,
				'max'        => 50,
				'responsive' => true,
				'condition'  => [
					'required' => true,
				],
				'selectors'  => [
					$label_sel . ' .fcf7b-req' => 'margin-inline-start: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_section();
	}
}
