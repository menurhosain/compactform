<?php
/** Common "Width" control (Settings tab, General section). */

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Width_Control {

	protected function register_width_control(): void {
		$this->add_control( 'width', self::width_control_args() );
	}

	public static function width_control_args(): array {
		return [
			'type'       => Controls_Manager::SLIDER,
			'label'      => __( 'Width', 'compactform' ),
			'units'      => [ '%', 'px', 'em', 'rem', 'vw', 'custom' ],
			'default'    => [ 'size' => '', 'unit' => '%' ],
			'ranges'     => [
				'%'   => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
				'px'  => [ 'min' => 0, 'max' => 1200, 'step' => 1 ],
				'em'  => [ 'min' => 0, 'max' => 80, 'step' => 0.1 ],
				'rem' => [ 'min' => 0, 'max' => 80, 'step' => 0.1 ],
				'vw'  => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
			],
			'responsive' => true,
			'separator'  => 'before',
			'global'     => false,
			'selectors'  => [
				'{{WRAPPER}}' => 'width: {{SIZE}}{{UNIT}}; max-width: {{SIZE}}{{UNIT}}; flex: 0 1 auto;',
			],
		];
	}
}
