<?php
/** Common "Label" text control (Settings tab, General section). */

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Label_Control {

	protected function register_label_control(): void {
		$this->add_control(
			'label',
			[
				'type'  => Controls_Manager::TEXT,
				'label' => __( 'Label', 'compactform' ),
			]
		);
	}
}
