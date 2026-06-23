<?php
/** Common "Field Name" text control (Settings tab, General section). */

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Name_Control {

	protected function register_name_control(): void {
		$this->add_control(
			'name',
			[
				'type'        => Controls_Manager::TEXT,
				'label'       => __( 'Field Name', 'compactform' ),
				'description' => __( 'Used in emails as [name]', 'compactform' ),
				'slug'        => true,
			]
		);
	}
}
