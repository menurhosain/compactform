<?php
/** Common "Flex Item" popover group (Settings tab, alongside Width). */

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Flex_Item_Control {

	protected function register_flex_item_controls(): void {
		$this->start_popover( 'flexItem', [
			'label' => __( 'Flex Item', 'compactform' ),
		] );
			$this->add_control( 'alignSelf', [
				'type'        => Controls_Manager::CHOOSE,
				'label'       => __( 'Align Self', 'compactform' ),
				'label_block' => false,
				'toggle'      => true,
				'default'     => '',
				'options'     => [
					'flex-start' => [ 'title' => __( 'Start', 'compactform' ), 'icon' => 'ri-align-item-top-line' ],
					'center'     => [ 'title' => __( 'Center', 'compactform' ), 'icon' => 'ri-align-item-vertical-center-line' ],
					'flex-end'   => [ 'title' => __( 'End', 'compactform' ), 'icon' => 'ri-align-item-bottom-line' ],
					'stretch'    => [ 'title' => __( 'Stretch', 'compactform' ), 'icon' => 'ri-expand-height-line' ],
				],
				'responsive'  => true,
				'selectors'   => [ '{{WRAPPER}}' => 'align-self: {{VALUE}};' ],
			] );
			$this->add_control( 'flexGrow', [
				'type'        => Controls_Manager::NUMBER,
				'label'       => __( 'Grow', 'compactform' ),
				'label_block' => false,
				'min'         => 0,
				'max'         => 12,
				'default'     => '',
				'responsive'  => true,
				'selectors'   => [ '{{WRAPPER}}' => 'flex-grow: {{VALUE}};' ],
			] );
			$this->add_control( 'flexShrink', [
				'type'        => Controls_Manager::NUMBER,
				'label'       => __( 'Shrink', 'compactform' ),
				'label_block' => false,
				'min'         => 0,
				'max'         => 12,
				'default'     => '',
				'responsive'  => true,
				'selectors'   => [ '{{WRAPPER}}' => 'flex-shrink: {{VALUE}};' ],
			] );
			$this->add_control( 'order', [
				'type'        => Controls_Manager::NUMBER,
				'label'       => __( 'Order', 'compactform' ),
				'label_block' => false,
				'min'         => -50,
				'max'         => 50,
				'default'     => '',
				'responsive'  => true,
				'selectors'   => [ '{{WRAPPER}}' => 'order: {{VALUE}};' ],
			] );
		$this->end_popover();
	}
}
