<?php
/** Common "Placeholder" text control (Settings tab, General section). */

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Placeholder_Control {

	protected function register_placeholder_control(): void {
		$this->add_control(
			'placeholder',
			[
				'type'  => Controls_Manager::TEXT,
				'label' => __( 'Placeholder', 'compactform' ),
			]
		);
	}
}
