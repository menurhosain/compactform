<?php
/** Common "Default Value" text control (Settings tab, General section). */

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Default_Value_Control {

	protected function register_default_value_control(): void {
		$this->add_control(
			'defaultValue',
			[
				'type'  => Controls_Manager::TEXT,
				'label' => __( 'Default Value', 'compactform' ),
			]
		);
	}
}
