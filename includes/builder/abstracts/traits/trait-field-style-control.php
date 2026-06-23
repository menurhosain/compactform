<?php

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Field_Style_Control {

	protected function register_field_style_controls( bool $with_placeholder = false, bool $with_box = false ): void {
		$control_sel = '{{WRAPPER}} .wpcf7-form-control';

		$this->start_section(
			'section_field_style',
			[
				'label' => __( 'Field', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'field_typography',
			[
				'type'       => Controls_Manager::TYPOGRAPHY,
				'label'      => __( 'Typography', 'compactform' ),
				'selector'   => $control_sel,
				'responsive' => true,
			]
		);

		$this->add_control(
			'field_align',
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
					$control_sel => 'text-align: {{VALUE}};',
				],
			]
		);

		if ( $with_placeholder ) {
			$this->add_control(
				'placeholder_color',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Placeholder', 'compactform' ),
					'selectors' => [
						'{{WRAPPER}} .wpcf7-form-control::placeholder' => 'color: {{VALUE}};',
					],
					'condition' => [
						'placeholder!' => '',
					],
				]
			);
		}

		if ( $with_box ) {
			$this->add_control(
				'field_padding',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Padding', 'compactform' ),
					'selectors'  => [
						$control_sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'field_radius',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Border Radius', 'compactform' ),
					'selectors'  => [
						$control_sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'field_height',
				[
					'label' => esc_html__( 'Height', 'compactform' ),
					'type' => Controls_Manager::SLIDER,
					'units'      => [ 'px', 'em', 'rem', '%', 'custom' ],
					'ranges'     => [
						'px'  => [ 'min' => 0, 'max' => 300 ],
						'em'  => [ 'min' => 0, 'max' => 300 ],
						'rem' => [ 'min' => 0, 'max' => 300 ],
						'%'   => [ 'min' => 0, 'max' => 100 ],
					],
					'selectors' => [
						$control_sel => 'height: {{SIZE}}{{UNIT}}; min-height: {{SIZE}}{{UNIT}}; max-height: {{SIZE}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
		}

		$this->start_controls_tabs(
			'field_style_tabs',
			[
				'separator' => 'before',
			]
		);

		$this->start_controls_tab(
			'field_tab_normal',
			[
				'label' => __( 'Normal', 'compactform' ),
			]
		);
		$this->add_control(
			'field_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'compactform' ),
				'selectors' => [
					$control_sel => 'color: {{VALUE}};',
				],
			]
		);
		if ( $with_box ) {
			$this->add_control(
				'field_background_color',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Background Color', 'compactform' ),
					'selectors' => [
						$control_sel => 'background-color: {{VALUE}};',
					],
				]
			);
			$this->add_control(
				'field_border',
				[
					'type'     => Controls_Manager::BORDER,
					'label'    => __( 'Border', 'compactform' ),
					'selector' => $control_sel,
				]
			);
			$this->add_control(
				'field_box_shadow',
				[
					'type'     => Controls_Manager::BOX_SHADOW,
					'label'    => __( 'Box Shadow', 'compactform' ),
					'selector' => $control_sel,
				]
			);
		}
		$this->end_controls_tab();

		$this->start_controls_tab(
			'field_tab_focus',
			[
				'label' => __( 'Focus', 'compactform' ),
			]
		);
		$this->add_control(
			'field_color_focus',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'compactform' ),
				'selectors' => [
					$control_sel . ':focus, ' . $control_sel . ':focus-visible' => 'color: {{VALUE}};',
				],
			]
		);
		if ( $with_box ) {
			$this->add_control(
				'field_background_color_focus',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Background Color', 'compactform' ),
					'selectors' => [
						$control_sel . ':focus, ' . $control_sel . ':focus-visible' => 'background-color: {{VALUE}};',
					],
				]
			);
			$this->add_control(
				'field_border_color_focus',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Border Color', 'compactform' ),
					'selectors' => [
						$control_sel . ':focus, ' . $control_sel . ':focus-visible' => 'border-color: {{VALUE}} !important;',
					],
					'condition' => [
						'field_border[style]!' => '',
					],
				]
			);
			$this->add_control(
				'field_box_shadow_focus',
				[
					'type'     => Controls_Manager::BOX_SHADOW,
					'label'    => __( 'Box Shadow', 'compactform' ),
					'selector' => $control_sel . ':focus, ' . $control_sel . ':focus-visible',
				]
			);
		}
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_section();
	}
}
