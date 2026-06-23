<?php
namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait List_Style_Control {

	protected function register_list_style_controls(): void {
		$group = '{{WRAPPER}} .wpcf7-form-control';
		$item  = '{{WRAPPER}} .wpcf7-list-item';
		$input = '{{WRAPPER}} .wpcf7-list-item input';

		$this->start_section(
			'choice_options',
			[
				'label' => __( 'Options', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
			$this->add_control(
				'options_wrapper_style_ctrl_heading',
				[
					'label' => esc_html__( 'Wrapper', 'compactform' ),
					'type' => Controls_Manager::HEADING,
				]
			);
			$this->start_popover(
				'options_wrapper_layout',
				[
					'label' => __( 'Layout', 'compactform' ),
				]
			);
				$this->add_control(
					'options_wrapper_display',
					[
						'type'        => Controls_Manager::SELECT,
						'label'       => __( 'Display', 'compactform' ),
						'label_block' => false,
						'options'     => [
							''             => __( 'Default', 'compactform' ),
							'block'        => 'block',
							'inline-block' => 'inline-block',
							'flex'         => 'flex',
							'inline-flex'  => 'inline-flex',
						],
						'default'     => '',
						'responsive'  => true,
						'selectors'   => [ $group => 'display: {{VALUE}};' ],
					]
				);
				$flex = [ 'condition' => [ 'options_wrapper_display' => [ 'flex', 'inline-flex' ] ] ];
				$this->add_control(
					'options_wrapper_direction',
					[
						'type'        => Controls_Manager::SELECT,
						'label'       => __( 'Direction', 'compactform' ),
						'label_block' => false,
						'options'     => [
							''               => __( 'Default', 'compactform' ),
							'row'            => __( 'Row', 'compactform' ),
							'row-reverse'    => __( 'Row reversed', 'compactform' ),
							'column'         => __( 'Column', 'compactform' ),
							'column-reverse' => __( 'Column reversed', 'compactform' ),
						],
						'default'     => '',
						'responsive'  => true,
						'selectors'   => [ $group => 'flex-direction: {{VALUE}};' ],
					] + $flex
				);
				$this->add_control(
					'options_wrapper_flex_wrap',
					[
						'type'        => Controls_Manager::SELECT,
						'label'       => __( 'Wrap', 'compactform' ),
						'label_block' => false,
						'options'     => [
							''             => __( 'Default', 'compactform' ),
							'nowrap'       => __( 'No wrap', 'compactform' ),
							'wrap'         => __( 'Wrap', 'compactform' ),
							'wrap-reverse' => __( 'Wrap reversed', 'compactform' ),
						],
						'default'     => '',
						'responsive'  => true,
						'selectors'   => [ $group => 'flex-wrap: {{VALUE}};' ],
					] + $flex
				);
				$this->add_control(
					'options_wrapper_flex_gap',
					[
						'type'       => Controls_Manager::SLIDER,
						'label'      => __( 'Gap', 'compactform' ),
						'units'      => [ 'px', 'em', 'rem', 'custom' ],
						'min'        => 0,
						'max'        => 100,
						'responsive' => true,
						'selectors'  => [ $group => 'gap: {{SIZE}}{{UNIT}};' ],
					] + $flex
				);
				$this->add_control(
					'options_wrapper_align_items',
					[
						'type'        => Controls_Manager::SELECT,
						'label'       => __( 'Align Items', 'compactform' ),
						'label_block' => false,
						'options'     => [
							''           => __( 'Default', 'compactform' ),
							'flex-start' => __( 'Start', 'compactform' ),
							'center'     => __( 'Center', 'compactform' ),
							'flex-end'   => __( 'End', 'compactform' ),
							'stretch'    => __( 'Stretch', 'compactform' ),
							'baseline'   => __( 'Baseline', 'compactform' ),
						],
						'default'     => '',
						'responsive'  => true,
						'selectors'   => [ $group => 'align-items: {{VALUE}};' ],
					] + $flex
				);
				$this->add_control(
					'options_wrapper_justify_content',
					[
						'type'        => Controls_Manager::SELECT,
						'label'       => __( 'Justify Content', 'compactform' ),
						'label_block' => false,
						'options'     => [
							''              => __( 'Default', 'compactform' ),
							'flex-start'    => __( 'Start', 'compactform' ),
							'center'        => __( 'Center', 'compactform' ),
							'flex-end'      => __( 'End', 'compactform' ),
							'space-between' => __( 'Space between', 'compactform' ),
							'space-around'  => __( 'Space around', 'compactform' ),
							'space-evenly'  => __( 'Space evenly', 'compactform' ),
						],
						'default'     => '',
						'responsive'  => true,
						'selectors'   => [ $group => 'justify-content: {{VALUE}};' ],
					] + $flex
				);
			$this->end_popover();

			$this->add_control(
				'options_style_ctrl_heading',
				[
					'label' => esc_html__( 'Options', 'compactform' ),
					'type' => Controls_Manager::HEADING,
					'separator' => 'before'
				]
			);
			$this->start_popover(
				'options_layout',
				[
					'label' => __( 'Layout', 'compactform' ),
				]
			);
				$this->add_control(
					'options_display',
					[
						'type'        => Controls_Manager::SELECT,
						'label'       => __( 'Display', 'compactform' ),
						'label_block' => false,
						'options'     => [
							''             => __( 'Default', 'compactform' ),
							'block'        => 'block',
							'inline-block' => 'inline-block',
							'flex'         => 'flex',
							'inline-flex'  => 'inline-flex',
						],
						'default'     => '',
						'responsive'  => true,
						'selectors'   => [ $item => 'display: {{VALUE}};' ],
					]
				);
				$flex = [ 'condition' => [ 'options_display' => [ 'flex', 'inline-flex' ] ] ];
				$this->add_control(
					'options_direction',
					[
						'type'        => Controls_Manager::SELECT,
						'label'       => __( 'Direction', 'compactform' ),
						'label_block' => false,
						'options'     => [
							''               => __( 'Default', 'compactform' ),
							'row'            => __( 'Row', 'compactform' ),
							'row-reverse'    => __( 'Row reversed', 'compactform' ),
							'column'         => __( 'Column', 'compactform' ),
							'column-reverse' => __( 'Column reversed', 'compactform' ),
						],
						'default'     => '',
						'responsive'  => true,
						'selectors'   => [ $item => 'flex-direction: {{VALUE}};' ],
					] + $flex
				);
				$this->add_control(
					'options_flex_wrap',
					[
						'type'        => Controls_Manager::SELECT,
						'label'       => __( 'Wrap', 'compactform' ),
						'label_block' => false,
						'options'     => [
							''             => __( 'Default', 'compactform' ),
							'nowrap'       => __( 'No wrap', 'compactform' ),
							'wrap'         => __( 'Wrap', 'compactform' ),
							'wrap-reverse' => __( 'Wrap reversed', 'compactform' ),
						],
						'default'     => '',
						'responsive'  => true,
						'selectors'   => [ $item => 'flex-wrap: {{VALUE}};' ],
					] + $flex
				);
				$this->add_control(
					'options_flex_gap',
					[
						'type'       => Controls_Manager::SLIDER,
						'label'      => __( 'Gap', 'compactform' ),
						'units'      => [ 'px', 'em', 'rem', 'custom' ],
						'min'        => 0,
						'max'        => 100,
						'responsive' => true,
						'selectors'  => [ $item => 'gap: {{SIZE}}{{UNIT}};' ],
					] + $flex
				);
				$this->add_control(
					'options_align_items',
					[
						'type'        => Controls_Manager::SELECT,
						'label'       => __( 'Align Items', 'compactform' ),
						'label_block' => false,
						'options'     => [
							''           => __( 'Default', 'compactform' ),
							'flex-start' => __( 'Start', 'compactform' ),
							'center'     => __( 'Center', 'compactform' ),
							'flex-end'   => __( 'End', 'compactform' ),
							'stretch'    => __( 'Stretch', 'compactform' ),
							'baseline'   => __( 'Baseline', 'compactform' ),
						],
						'default'     => '',
						'responsive'  => true,
						'selectors'   => [ $item => 'align-items: {{VALUE}};' ],
					] + $flex
				);
				$this->add_control(
					'options_justify_content',
					[
						'type'        => Controls_Manager::SELECT,
						'label'       => __( 'Justify Content', 'compactform' ),
						'label_block' => false,
						'options'     => [
							''              => __( 'Default', 'compactform' ),
							'flex-start'    => __( 'Start', 'compactform' ),
							'center'        => __( 'Center', 'compactform' ),
							'flex-end'      => __( 'End', 'compactform' ),
							'space-between' => __( 'Space between', 'compactform' ),
							'space-around'  => __( 'Space around', 'compactform' ),
							'space-evenly'  => __( 'Space evenly', 'compactform' ),
						],
						'default'     => '',
						'responsive'  => true,
						'selectors'   => [ $item => 'justify-content: {{VALUE}};' ],
					] + $flex
				);
			$this->end_popover();

			$this->add_control(
				'options_box_style_ctrl_heading',
				[
					'label'     => esc_html__( 'Item Box', 'compactform' ),
					'type'      => Controls_Manager::HEADING,
					'separator' => 'before',
				]
			);
			$this->add_control(
				'options_padding',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Padding', 'compactform' ),
					'selectors'  => [
						$item => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'options_radius',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Border Radius', 'compactform' ),
					'selectors'  => [
						$item => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'options_width',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Width', 'compactform' ),
					'units'      => [ 'px', '%', 'em', 'rem', 'custom' ],
					'min'        => 0,
					'max'        => 100,
					'responsive' => true,
					'default' => [
						'unit' => '%'
					],
					'selectors'  => [
						$item => 'max-width: {{SIZE}}{{UNIT}}; width: 100%;',
					],
				]
			);

			$this->start_controls_tabs( 'options_box_style_tabs' );
				$this->start_controls_tab(
					'options_box_tab_normal',
					[
						'label' => __( 'Normal', 'compactform' ),
					]
				);
					$this->add_control(
						'options_background_color',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Background Color', 'compactform' ),
							'selectors' => [
								$item => 'background-color: {{VALUE}};',
							],
						]
					);
					$this->add_control(
						'options_border',
						[
							'type'     => Controls_Manager::BORDER,
							'label'    => __( 'Border', 'compactform' ),
							'selector' => $item,
						]
					);
					$this->add_control(
						'options_box_shadow',
						[
							'type'     => Controls_Manager::BOX_SHADOW,
							'label'    => __( 'Box Shadow', 'compactform' ),
							'selector' => $item,
						]
					);
				$this->end_controls_tab();
				$this->start_controls_tab(
					'options_box_tab_checked',
					[
						'label' => __( 'Checked', 'compactform' ),
					]
				);
					$this->add_control(
						'options_background_color_checked',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Background Color', 'compactform' ),
							'selectors' => [
								$item . ':has(:checked)' => 'background-color: {{VALUE}};',
							],
						]
					);
					$this->add_control(
						'options_border_color_checked',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Border Color', 'compactform' ),
							'selectors' => [
								$item . ':has(:checked)' => 'border-color: {{VALUE}} !important;',
							],
							'condition' => [
								'options_border[style]!' => '',
							],
						]
					);
					$this->add_control(
						'options_box_shadow_checked',
						[
							'type'     => Controls_Manager::BOX_SHADOW,
							'label'    => __( 'Box Shadow', 'compactform' ),
							'selector' => $item . ':has(:checked)',
						]
					);
				$this->end_controls_tab();
			$this->end_controls_tabs();
		$this->end_section();

		$this->start_section(
			'choice_input',
			[
				'label' => __( 'Input', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
			$this->add_control(
				'input_size',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Size', 'compactform' ),
					'units'      => [ 'px', 'em', 'rem', 'custom' ],
					'min'        => 0,
					'max'        => 60,
					'responsive' => true,
					'selectors'  => [
						$input => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					],
				]
			);
			$this->add_control(
				'input_gap',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Gap', 'compactform' ),
					'units'      => [ 'px', 'em', 'rem', 'custom' ],
					'min'        => 0,
					'max'        => 50,
					'responsive' => true,
					'selectors'  => [
						$item . ' .wpcf7-list-item-label' => 'margin-inline-start: {{SIZE}}{{UNIT}};',
					],
				]
			);
			$this->add_control(
				'input_accent',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Accent Color', 'compactform' ),
					'selectors' => [ $input => 'accent-color: {{VALUE}};' ],
				]
			);
		$this->end_section();

		$this->start_section(
			'choice_text',
			[
				'label' => __( 'Text', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
			$this->add_control(
				'choice_text_typography',
				[
					'label'     => __( 'Typography', 'compactform' ),
					'type'      => Controls_Manager::TYPOGRAPHY,
					'selector' => $item . ' ' . '.wpcf7-list-item-label',
				]
			);
			$this->start_controls_tabs( 'choice_text_style_tabs' );
				$this->start_controls_tab(
					'choice_text_tab_normal',
					[
						'label' => __( 'Normal', 'compactform' ),
					]
				);
					$this->add_control(
						'choice_text_color',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Color', 'compactform' ),
							'selectors' => [
								$item => 'color: {{VALUE}};',
							],
						]
					);
				$this->end_controls_tab();
				$this->start_controls_tab(
					'choice_text_tab_checked',
					[
						'label' => __( 'Checked', 'compactform' ),
					]
				);
					$this->add_control(
						'choice_text_color_checked',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Color', 'compactform' ),
							'selectors' => [
								$item . ':has(:checked)' => 'color: {{VALUE}};',
							],
						]
					);
				$this->end_controls_tab();
			$this->end_controls_tabs();
		$this->end_section();
	}
}
