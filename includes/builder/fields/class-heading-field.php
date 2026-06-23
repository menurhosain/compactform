<?php

namespace CompactForm\Builder\Fields;

use CompactForm\Builder\Controls_Manager;
use CompactForm\Builder\Abstracts\Base_Field;
use CompactForm\Builder\Abstracts\Traits\Width_Control;
use CompactForm\Builder\Abstracts\Traits\Flex_Item_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Control;
use CompactForm\Builder\Field_Helpers;

defined( 'ABSPATH' ) || die();

class Heading_Field extends Base_Field {

	use Width_Control;
	use Flex_Item_Control;
	use Conditional_Control;

	public function get_type(): string {
		return 'heading';
	}

	public function get_title(): string {
		return __( 'Heading', 'compactform' );
	}

	public function get_icon(): string {
		return 'ri-heading';
	}

	public function get_category(): string {
		return 'layout';
	}

	public function get_preview(): string {
		return 'heading';
	}

	protected function register_controls(): void {
		$this->start_section( 'general', [
			'label' => __( 'Heading', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
			$this->add_control(
				'label', [
					'type' => Controls_Manager::TEXT,
					'label' => __( 'Text', 'compactform' )
				]
			);
			$this->add_control( 'level', [
				'type' => Controls_Manager::SELECT,
				'label'   => __( 'Tag', 'compactform' ),
				'label_block' => false,
				'options' => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'p'    => 'P',
					'span' => 'Span'
				],
				'default' => 'h3'
			] );
			$this->add_control( 'width', self::width_control_args() );
			$this->register_flex_item_controls();
		$this->end_section();

		$this->start_section( 'style', [
			'label' => __( 'Style', 'compactform' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
			$this->add_control( 'typography', [
				'type' => Controls_Manager::TYPOGRAPHY,
				'label'    => __( 'Typography', 'compactform' ),
				'selector'   => '{{WRAPPER}} .fcf7b-heading',
				'responsive' => true
			] );
			$this->add_control( 'textColor', [
				'type' => Controls_Manager::COLOR,
				'label'     => __( 'Text Color', 'compactform' ),
				'selectors' => [ '{{WRAPPER}} .fcf7b-heading' => 'color: {{VALUE}};' ]
			] );

			$this->add_control( 'align', [
				'type'        => Controls_Manager::CHOOSE,
				'label'       => __( 'Alignment', 'compactform' ),
				'label_block' => false,
				'toggle'      => true,
				'options'     => [
					'left'   => [
						'title' => __( 'Left', 'compactform' ),
						'icon'  => 'ri-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'compactform' ),
						'icon'  => 'ri-align-center',
					],
					'right'  => [
						'title' => __( 'Right', 'compactform' ),
						'icon'  => 'ri-align-right',
					],
				],
				'responsive' => true,
				'selectors'  => [ '{{WRAPPER}} .fcf7b-heading' => 'text-align: {{VALUE}};' ],
			] );
			$this->add_control(
				'margin',
				[
					'type' => Controls_Manager::DIMENSIONS,
					'label'     => __( 'Margin', 'compactform' ),
					'selectors'  => [
						'{{WRAPPER}}' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
					],
					'responsive' => true
				]
			);
		$this->end_section();

		$this->register_conditional_controls();
	}

	const ALLOWED_TAGS = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span' ];

	public function compile( array $data ): string {
		$level = (string) ( $data['level'] ?? 'h3' );
		$level = in_array( $level, self::ALLOWED_TAGS, true ) ? $level : 'h3';

		return "<{$level} class=\"fcf7b-heading\">" . Field_Helpers::esc( $data['label'] ?? '' ) . "</{$level}>";
	}
}
