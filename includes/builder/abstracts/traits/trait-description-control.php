<?php
/** Common "Description" text control (Settings tab, General section). */

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Description_Control {

	protected function register_description_control(): void {
		$this->add_control(
			'description',
			[
				'type'  => Controls_Manager::TEXT,
				'label' => __( 'Description', 'compactform' ),
			]
		);
	}
}
