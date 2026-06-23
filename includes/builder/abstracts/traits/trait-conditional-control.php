<?php
/** "Conditional Logic" section (Advanced tab). Self-contained. */

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Conditional_Control {

	protected function register_conditional_controls(): void {
		$this->start_section(
			'conditional',
			[
				'label' => __( 'Conditional Logic', 'compactform' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);
		$this->add_control(
			'conditions',
			[
				'type'  => Controls_Manager::CONDITIONS,
				'label' => __( 'Conditions', 'compactform' ),
			]
		);
		$this->end_section();
	}
}
