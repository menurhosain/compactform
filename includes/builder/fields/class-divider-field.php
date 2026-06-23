<?php

namespace CompactForm\Builder\Fields;

use CompactForm\Builder\Controls_Manager;

use CompactForm\Builder\Abstracts\Base_Field;
use CompactForm\Builder\Abstracts\Traits\Width_Control;
use CompactForm\Builder\Abstracts\Traits\Flex_Item_Control;
use CompactForm\Builder\Abstracts\Traits\Advanced_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Control;

defined( 'ABSPATH' ) || die();

class Divider_Field extends Base_Field {

	use Width_Control;
	use Flex_Item_Control;
	use Advanced_Control;
	use Conditional_Control;

	public function get_type(): string { return 'divider'; }
	public function get_title(): string { return __( 'Divider', 'compactform' ); }
	public function get_icon(): string { return 'ri-separator'; }
	public function get_category(): string { return 'layout'; }
	public function get_preview(): string { return 'divider'; }

	protected function register_controls(): void {
		$this->start_section( 'general', [
			'label' => __( 'Divider', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
			$this->add_control( 'width', self::width_control_args() );
			$this->register_flex_item_controls();
		$this->end_section();

		$this->start_section( 'style', [
			'label' => __( 'Style', 'compactform' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
			$this->add_control(
				'divider_pattern',
				[
					'label'       => esc_html__( 'Pattern', 'compactform' ),
					'type'        => Controls_Manager::SELECT,
					'default'     => '',
					'label_block' => false,
					'options'     => self::pattern_options(),
				]
			);
			$this->add_control(
				'divider_type',
				[
					'label' => esc_html__( 'Line Type', 'compactform' ),
					'type' => Controls_Manager::SELECT,
					'default' => 'solid',
					'label_block' => false,
					'options' => [
						'solid'  => __( 'Solid', 'compactform' ),
						'dashed' => __( 'Dashed', 'compactform' ),
						'dotted' => __( 'Dotted', 'compactform' ),
						'double' => __( 'Double', 'compactform' ),
						'groove' => __( 'Groove', 'compactform' ),
						'ridge'  => __( 'Ridge', 'compactform' ),
						'inset'  => __( 'Inset', 'compactform' ),
						'outset' => __( 'Outset', 'compactform' ),
					],
					'condition' => [ 'divider_pattern' => '' ],
					'selectors' => [ '{{WRAPPER}} .fcf7b-divider' => 'border-top-style: {{VALUE}};' ]
				]
			);
			$this->add_control(
				'divider_color', [
					'type' => Controls_Manager::COLOR,
					'label'     => __( 'Line Color', 'compactform' ),
					'selectors' => [ '{{WRAPPER}} .fcf7b-divider' => '--fcf7b-divider-color: {{VALUE}};' ]
				]
			);
			$this->add_control(
				'divider_width', [
					'type' => Controls_Manager::SLIDER,
					'label'     => __( 'Line Thickness', 'compactform' ),
					'min'       => 0,
					'max'       => 100,
					'step'      => 1,
					'units'     => [ 'px', 'em', 'rem', 'custom' ],
					'condition'  => [ 'divider_pattern' => '' ],
					'selectors'  => [ '{{WRAPPER}} .fcf7b-divider' => 'border-top-width: {{SIZE}}{{UNIT}};' ],
					'responsive' => true
				]
			);
			$this->add_control(
				'divider_height', [
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Pattern Height', 'compactform' ),
					'min'        => 1,
					'max'        => 100,
					'step'       => 1,
					'units'      => [ 'px', 'em', 'rem', 'custom' ],
					'condition'  => [ 'divider_pattern!' => '' ],
					'selectors'  => [ '{{WRAPPER}} .fcf7b-divider' => 'height: {{SIZE}}{{UNIT}};' ],
					'responsive' => true
				]
			);
			$this->add_control(
				'divider_size', [
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Pattern Width', 'compactform' ),
					'min'        => 1,
					'max'        => 200,
					'step'       => 1,
					'units'      => [ 'px', 'em', 'rem', 'custom' ],
					'condition'  => [ 'divider_pattern!' => '' ],
					'selectors'  => [ '{{WRAPPER}} .fcf7b-divider' => '--fcf7b-divider-psize: {{SIZE}}{{UNIT}};' ],
					'responsive' => true
				]
			);
		$this->end_section();

		// Advance Controls TAB_ADVANCED
		$this->register_advanced_controls();
		
		// Conditional Controls TAB_ADVANCED
		$this->register_conditional_controls();
	}

	private static function pattern_options(): array {
		return [
			''          => __( 'None (straight line)', 'compactform' ),
			'wavy'      => __( 'Wavy', 'compactform' ),
			'zigzag'    => __( 'Zigzag', 'compactform' ),
			'curved'    => __( 'Curved', 'compactform' ),
			'slashes'   => __( 'Slashes', 'compactform' ),
			'dots'      => __( 'Dots', 'compactform' ),
			'squares'   => __( 'Squares', 'compactform' ),
			'rhombus'   => __( 'Rhombus', 'compactform' ),
			'triangles' => __( 'Triangles', 'compactform' ),
			'arrows'    => __( 'Arrows', 'compactform' ),
			'multiple'  => __( 'Multiple Lines', 'compactform' ),
		];
	}

	public function compile( array $data ): string {
		$cls = 'fcf7b-divider';
		if ( ! empty( $data['cssClass'] ) ) {
			$cls .= ' ' . preg_replace( '/[^A-Za-z0-9_\- ]/', '', trim( (string) $data['cssClass'] ) );
		}

		$pattern = (string) ( $data['divider_pattern'] ?? '' );
		$attr    = ( '' !== $pattern && isset( self::pattern_options()[ $pattern ] ) )
			? ' data-pattern="' . esc_attr( $pattern ) . '"'
			: '';

		return '<div class="' . esc_attr( trim( $cls ) ) . '"' . $attr . '></div>';
	}
}
