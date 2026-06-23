<?php
/**
 * Common "Required field" toggle + its asterisk marker (Settings tab, General
 * section). Always brought in together — the marker only means anything when
 * required is on, so no field should take one without the other.
 */

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Required_Control {

	protected function register_required_control(): void {
		$this->add_control(
			'required',
			[
				'type'      => Controls_Manager::SWITCHER,
				'label'     => __( 'Required field', 'compactform' ),
				'separator' => 'before',
			]
		);
		$this->add_control(
			'required_asterisk',
			[
				'label'       => esc_html__( 'Marker', 'compactform' ),
				'default'     => __( '*', 'compactform' ),
				'placeholder' => __( '*', 'compactform' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => false,
				'condition'   => [
					'required' => true,
				],
			]
		);
	}
}
