<?php
/** "Advanced" section (Advanced tab): wrapper margin, CSS class, CSS id. Self-contained. */

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Advanced_Control {

	protected function register_advanced_controls(): void {
		$this->start_section(
			'advanced',
			[
				'label' => __( 'Advanced', 'compactform' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);
		$this->add_control(
			'wrapper_margin',
			[
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Margin', 'compactform' ),
				'selectors'  => [
					'{{WRAPPER}}' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'responsive' => true,
			]
		);

		$this->add_control(
			'cssClass',
			[
				'type'  => Controls_Manager::TEXT,
				'label' => __( 'CSS Class', 'compactform' ),
			]
		);
		$this->add_control(
			'fieldId',
			[
				'type'  => Controls_Manager::TEXT,
				'label' => __( 'CSS ID', 'compactform' ),
			]
		);
		$this->end_section();
	}
}
