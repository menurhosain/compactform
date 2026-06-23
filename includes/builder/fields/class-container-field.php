<?php
namespace CompactForm\Builder\Fields;

use CompactForm\Builder\Controls_Manager;
use CompactForm\Builder\Form_Compiler;

use CompactForm\Builder\Abstracts\Base_Field;
use CompactForm\Builder\Abstracts\Traits\Width_Control;
use CompactForm\Builder\Abstracts\Traits\Flex_Item_Control;

defined( 'ABSPATH' ) || die();

class Container_Field extends Base_Field {

	use Width_Control;
	use Flex_Item_Control;

	public function get_type(): string {
		return 'container';
	}

	public function is_globalizable(): bool {
		return false;
	}

	public function get_title(): string {
		return __( 'Container', 'compactform' );
	}

	public function get_icon(): string {
		return 'ri-layout-grid-line';
	}

	public function get_category(): string {
		return 'layout';
	}

	public function get_preview(): string {
		return 'CONTAINER';
	}

	protected function register_controls(): void {
		$this->start_section( 'layout', [
			'label' => __( 'Layout', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
			$this->add_control( 'direction', [
				'type'        => Controls_Manager::CHOOSE,
				'label'       => __( 'Direction', 'compactform' ),
				'label_block' => false,
				'toggle'      => true,
				'default'     => 'row',
				'options'     => [
					'row'            => [ 'title' => __( 'Horizontal', 'compactform' ), 'icon' => 'ri-arrow-right-line' ],
					'column'         => [ 'title' => __( 'Vertical', 'compactform' ), 'icon' => 'ri-arrow-down-line' ],
					'row-reverse'    => [ 'title' => __( 'Horizontal Reversed', 'compactform' ), 'icon' => 'ri-arrow-left-line' ],
					'column-reverse' => [ 'title' => __( 'Vertical Reversed', 'compactform' ), 'icon' => 'ri-arrow-up-line' ],
				],
				'responsive'  => true,
				'selectors'   => [
					'{{WRAPPER}} > .fcf7-container' => 'flex-direction: {{VALUE}};',
				],
			] );

			$this->add_control( 'wrap', [
				'type'        => Controls_Manager::CHOOSE,
				'label'       => __( 'Wrap', 'compactform' ),
				'label_block' => false,
				'toggle'      => true,
				'default'     => 'wrap',
				'options'     => [
					'wrap'   => [ 'title' => __( 'Wrap', 'compactform' ), 'icon' => 'ri-corner-down-left-line' ],
					'nowrap' => [ 'title' => __( 'No Wrap', 'compactform' ), 'icon' => 'ri-arrow-right-wide-line' ],
				],
				'responsive'  => true,
				'selectors'   => [
					'{{WRAPPER}} > .fcf7-container' => 'flex-wrap: {{VALUE}};',
				],
			] );

			$this->add_control( 'columnGap', [
				'type'       => Controls_Manager::SLIDER,
				'label'      => __( 'Horizontal Gap', 'compactform' ),
				'units'      => [ 'px', 'em', 'rem', '%' ],
				'min'        => 0,
				'max'        => 120,
				'responsive' => true,
				'selectors'  => [
					'{{WRAPPER}} > .fcf7-container' => 'column-gap: {{SIZE}}{{UNIT}};',
				],
			] );
			$this->add_control( 'rowGap', [
				'type'       => Controls_Manager::SLIDER,
				'label'      => __( 'Vertical Gap', 'compactform' ),
				'units'      => [ 'px', 'em', 'rem' ],
				'min'        => 0,
				'max'        => 120,
				'responsive' => true,
				'selectors'  => [
					'{{WRAPPER}} > .fcf7-container' => 'row-gap: {{SIZE}}{{UNIT}};',
				],
			] );

			$this->add_control( 'justify', [
				'type'        => Controls_Manager::CHOOSE,
				'label'       => __( 'Justify Content', 'compactform' ),
				'label_block' => true,
				'toggle'      => true,
				'default'     => '',
				'options'     => [
					'flex-start'    => [ 'title' => __( 'Start', 'compactform' ), 'icon' => 'ri-align-item-left-line' ],
					'center'        => [ 'title' => __( 'Center', 'compactform' ), 'icon' => 'ri-align-item-horizontal-center-line' ],
					'flex-end'      => [ 'title' => __( 'End', 'compactform' ), 'icon' => 'ri-align-item-right-line' ],
					'space-between' => [ 'title' => __( 'Space Between', 'compactform' ), 'icon' => 'ri-arrow-left-right-line' ],
					'space-around'  => [ 'title' => __( 'Space Around', 'compactform' ), 'icon' => 'ri-space' ],
					'space-evenly'  => [ 'title' => __( 'Space Evenly', 'compactform' ), 'icon' => 'ri-align-justify' ],
				],
				'responsive'  => true,
				'selectors'   => [
					'{{WRAPPER}} > .fcf7-container' => 'justify-content: {{VALUE}};',
				],
			] );

			$this->add_control( 'alignItems', [
				'type'        => Controls_Manager::CHOOSE,
				'label'       => __( 'Align Items', 'compactform' ),
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
				'selectors'   => [
					'{{WRAPPER}} > .fcf7-container' => 'align-items: {{VALUE}};',
				],
			] );
			$this->add_control( 'minHeight', [
				'type'       => Controls_Manager::SLIDER,
				'label'      => __( 'Minimum Height', 'compactform' ),
				'units'      => [ 'px', 'vh', 'em', 'rem', 'custom' ],
				'min'        => 0,
				'max'        => 1000,
				'responsive' => true,
				'selectors'  => [
					'{{WRAPPER}} > .fcf7-container' => 'min-height: {{SIZE}}{{UNIT}};',
				],
			] );

			$this->register_width_control();
			$this->register_flex_item_controls();
		$this->end_section();

		$this->start_section( 'container_style', [
			'label' => __( 'Container', 'compactform' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
			$this->add_control( 'bgColor', [
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background Color', 'compactform' ),
				'selectors' => [ '{{WRAPPER}} > .fcf7-container' => 'background-color: {{VALUE}};' ],
			] );
			$this->add_control( 'padding', [
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Padding', 'compactform' ),
				'selectors'  => [ '{{WRAPPER}} > .fcf7-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
				'responsive' => true,
			] );
			$this->add_control( 'margin', [
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Margin', 'compactform' ),
				'selectors'  => [ '{{WRAPPER}}' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
				'responsive' => true,
			] );
			$this->add_control( 'border', [
				'type'     => Controls_Manager::BORDER,
				'label'    => __( 'Border', 'compactform' ),
				'selector' => '{{WRAPPER}} > .fcf7-container',
			] );
			$this->add_control( 'radius', [
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Border Radius', 'compactform' ),
				'selectors'  => [ '{{WRAPPER}} > .fcf7-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
				'responsive' => true,
			] );
			$this->add_control( 'boxShadow', [
				'type'     => Controls_Manager::BOX_SHADOW,
				'label'    => __( 'Box Shadow', 'compactform' ),
				'selector' => '{{WRAPPER}} > .fcf7-container',
			] );
		$this->end_section();
	}

	public function compile( array $data ): string {
		$siblings = is_array( $data['__siblings'] ?? null ) ? $data['__siblings'] : [];
		$my_id    = (string) ( $data['id'] ?? '' );

		if ( '' === $my_id ) {
			return '';
		}

		$children = [];
		foreach ( $siblings as $sibling ) {
			if ( ! is_array( $sibling ) || (string) ( $sibling['parentId'] ?? '' ) !== $my_id ) {
				continue;
			}

			$sibling['__siblings'] = $siblings;

			$children[] = Form_Compiler::compile_field( $sibling );
		}

		$children = array_filter( $children );

		return "[fcf7_container]\n" . implode( "\n", $children ) . "\n[/fcf7_container]";
	}
}
