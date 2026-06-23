<?php
/** "Description" style section (Style tab). Self-contained — opens and closes its own section. */

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Description_Style_Control {

	protected function register_description_style_controls(): void {
		$desc_sel = '{{WRAPPER}} .fcf7b-desc';

		$this->start_section(
			'section_description_style',
			[
				'label'     => __( 'Description', 'compactform' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'description!' => '',
				],
			]
		);
		$this->add_control(
			'description_typography',
			[
				'type'       => Controls_Manager::TYPOGRAPHY,
				'label'      => __( 'Typography', 'compactform' ),
				'selector'   => $desc_sel,
				'responsive' => true,
			]
		);
		$this->add_control(
			'description_color',
			[
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Color', 'compactform' ),
				'selectors' => [
					$desc_sel => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_control(
			'description_margin',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Margin', 'compactform' ),
				'selectors'  => [
					$desc_sel => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'responsive' => true,
			]
		);
		$this->end_section();
	}
}
