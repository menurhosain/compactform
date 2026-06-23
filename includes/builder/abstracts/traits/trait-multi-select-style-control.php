<?php

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined('ABSPATH') || die();

trait Multi_Select_Style_Control {

	protected function register_multi_select_style_controls(): void {
		$only_multiple = ['multiple!' => ''];

		$chip        = '{{WRAPPER}} .fcf7-ms-chips .fcf7-ms-chip';
		$chip_x      = $chip . ' .fcf7-ms-chip-x';
		$placeholder = '{{WRAPPER}} .fcf7-ms-placeholder';
		$caret       = '{{WRAPPER}} .fcf7-ms-caret';
		$panel       = '{{WRAPPER}} .fcf7-ms-panel';
		$option      = $panel . ' > .fcf7-ms-option';

		/*  The closed control: placeholder, chips, caret.                     */
		$this->start_section('section_ms_control', [
			'label'     => __('Multi Select', 'compactform'),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => $only_multiple,
		]);

		$this->add_control('ms_placeholder_color', [
			'type'      => Controls_Manager::COLOR,
			'label'     => __('Placeholder Color', 'compactform'),
			'selectors' => [$placeholder => 'color: {{VALUE}}; opacity: 1;'],
		]);

		$this->add_control('ms_caret_color', [
			'type'      => Controls_Manager::COLOR,
			'label'     => __('Arrow Color', 'compactform'),
			'selectors' => [$caret => 'border-top-color: {{VALUE}}; opacity: 1;'],
		]);

		$this->add_control('ms_chip_typography', [
			'type'       => Controls_Manager::TYPOGRAPHY,
			'label'      => __('Chip Typography', 'compactform'),
			'selector'   => $chip,
			'responsive' => true,
		]);

		$this->add_control('ms_chip_color', [
			'type'      => Controls_Manager::COLOR,
			'label'     => __('Chip Text', 'compactform'),
			'selectors' => [$chip => 'color: {{VALUE}};'],
		]);

		$this->add_control('ms_chip_background', [
			'type'      => Controls_Manager::COLOR,
			'label'     => __('Chip Background', 'compactform'),
			'selectors' => [$chip => 'background-color: {{VALUE}};'],
		]);

		$this->add_control('ms_chip_remove_color', [
			'type'      => Controls_Manager::COLOR,
			'label'     => __('Chip Remove (×)', 'compactform'),
			'selectors' => [$chip_x => 'color: {{VALUE}}; opacity: 1;'],
		]);

		$this->add_control('ms_chip_padding', [
			'type'       => Controls_Manager::DIMENSIONS,
			'label'      => __('Chip Padding', 'compactform'),
			'selectors'  => [$chip => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
			'responsive' => true,
		]);

		$this->add_control('ms_chip_radius', [
			'type'       => Controls_Manager::DIMENSIONS,
			'label'      => __('Chip Border Radius', 'compactform'),
			'selectors'  => [$chip => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
			'responsive' => true,
		]);

		$this->add_control('ms_chip_border', [
			'type'     => Controls_Manager::BORDER,
			'label'    => __('Chip Border', 'compactform'),
			'selector' => $chip,
		]);

		$this->end_section();

		/*  The open panel and its options.                                    */
		$this->start_section('section_ms_dropdown', [
			'label'     => __('Multi Select Dropdown', 'compactform'),
			'tab'       => Controls_Manager::TAB_STYLE,
			'condition' => $only_multiple,
		]);

		$this->add_control('ms_panel_background', [
			'type'      => Controls_Manager::COLOR,
			'label'     => __('Background', 'compactform'),
			'selectors' => [$panel => 'background-color: {{VALUE}};'],
		]);

		$this->add_control('ms_panel_border', [
			'type'     => Controls_Manager::BORDER,
			'label'    => __('Border', 'compactform'),
			'selector' => $panel,
		]);

		$this->add_control('ms_panel_radius', [
			'type'       => Controls_Manager::DIMENSIONS,
			'label'      => __('Border Radius', 'compactform'),
			'selectors'  => [$panel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
			'responsive' => true,
		]);

		$this->add_control('ms_panel_shadow', [
			'type'     => Controls_Manager::BOX_SHADOW,
			'label'    => __('Box Shadow', 'compactform'),
			'selector' => $panel,
		]);

		$this->add_control('ms_panel_height', [
			'type'      => Controls_Manager::SLIDER,
			'label'     => __('Max Height', 'compactform'),
			'units'     => ['px', 'em', 'rem', 'vh'],
			'min'       => 80,
			'max'       => 600,
			'selectors' => [$panel => 'max-height: {{SIZE}}{{UNIT}};'],
		]);

		$this->add_control('ms_option_typography', [
			'type'       => Controls_Manager::TYPOGRAPHY,
			'label'      => __('Option Typography', 'compactform'),
			'selector'   => $option,
			'responsive' => true,
		]);

		$this->add_control('ms_option_padding', [
			'type'       => Controls_Manager::DIMENSIONS,
			'label'      => __('Option Padding', 'compactform'),
			'selectors'  => [$option => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
			'responsive' => true,
		]);

		$this->start_controls_tabs('ms_option_tabs', ['separator' => 'before']);

		$this->start_controls_tab('ms_option_tab_normal', [
			'label' => __('Normal', 'compactform'),
		]);
		$this->add_control('ms_option_color', [
			'type'      => Controls_Manager::COLOR,
			'label'     => __('Text Color', 'compactform'),
			'selectors' => [$option => 'color: {{VALUE}};'],
		]);
		$this->add_control('ms_option_background', [
			'type'      => Controls_Manager::COLOR,
			'label'     => __('Background', 'compactform'),
			'selectors' => [$option => 'background-color: {{VALUE}};'],
		]);
		$this->end_controls_tab();

		$this->start_controls_tab('ms_option_tab_hover', [
			'label' => __('Hover', 'compactform'),
		]);
		$this->add_control('ms_option_color_hover', [
			'type'      => Controls_Manager::COLOR,
			'label'     => __('Text Color', 'compactform'),
			'selectors' => [$option . ':hover' => 'color: {{VALUE}};'],
		]);
		$this->add_control('ms_option_background_hover', [
			'type'      => Controls_Manager::COLOR,
			'label'     => __('Background', 'compactform'),
			'selectors' => [$option . ':hover' => 'background-color: {{VALUE}};'],
		]);
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_section();
	}
}
