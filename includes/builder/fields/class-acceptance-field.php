<?php

namespace CompactForm\Builder\Fields;

use CompactForm\Builder\Controls_Manager;

use CompactForm\Builder\Abstracts\Base_Field;
use CompactForm\Builder\Abstracts\Traits\Label_Control;
use CompactForm\Builder\Abstracts\Traits\Name_Control;
use CompactForm\Builder\Abstracts\Traits\Width_Control;
use CompactForm\Builder\Abstracts\Traits\Flex_Item_Control;
use CompactForm\Builder\Abstracts\Traits\Label_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Advanced_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Control;
use CompactForm\Builder\Field_Helpers;

defined( 'ABSPATH' ) || die();

class Acceptance_Field extends Base_Field {

	use Label_Control;
	use Name_Control;
	use Width_Control;
	use Flex_Item_Control;
	use Label_Style_Control;
	use Advanced_Control;
	use Conditional_Control;

	public function get_type(): string { return 'acceptance'; }
	public function get_title(): string { return __( 'Acceptance', 'compactform' ); }
	public function get_icon(): string { return 'ri-check-line'; }
	public function get_category(): string { return 'choice'; }
	public function get_preview(): string { return 'acceptance'; }

	protected function register_controls(): void {
		$this->start_section(
			'general',
			[
				'label' => __( 'Acceptance', 'compactform' ),
				'tab'   => Controls_Manager::TAB_SETTINGS,
			]
		);
			$this->register_label_control();

			$this->add_control(
				'text',
				[
					'type'  => Controls_Manager::TEXT,
					'label' => __( 'Consent Text', 'compactform' ),
				]
			);

			$this->register_name_control();

			$this->add_control(
				'required',
				[
					'type'      => Controls_Manager::SWITCHER,
					'label'     => __( 'Required field', 'compactform' ),
					'default'   => true,
					'separator' => 'before',
				]
			);

			$this->add_control( 'width', self::width_control_args() );
			$this->register_flex_item_controls();
		$this->end_section();

		$this->register_label_style_controls();
		$this->register_acceptance_style_controls();

		// Advance Controls TAB_ADVANCED
		$this->register_advanced_controls();

		// Conditional Controls TAB_ADVANCED
		$this->register_conditional_controls();
	}

	protected function register_acceptance_style_controls(): void {
		$item  = '{{WRAPPER}} .wpcf7-list-item';
		$input = '{{WRAPPER}} .wpcf7-list-item input';
		$label = '{{WRAPPER}} .wpcf7-list-item-label';

		$this->start_section(
			'acceptance_box',
			[
				'label' => __( 'Box', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
			$this->add_control(
				'box_padding',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Padding', 'compactform' ),
					'selectors'  => [
						$item => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'box_radius',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Border Radius', 'compactform' ),
					'selectors'  => [
						$item => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);

			$this->start_controls_tabs( 'box_style_tabs' );
				$this->start_controls_tab(
					'box_tab_normal',
					[
						'label' => __( 'Normal', 'compactform' ),
					]
				);
					$this->add_control(
						'box_background_color',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Background Color', 'compactform' ),
							'selectors' => [ $item => 'background-color: {{VALUE}};' ],
						]
					);
					$this->add_control(
						'box_border',
						[
							'type'     => Controls_Manager::BORDER,
							'label'    => __( 'Border', 'compactform' ),
							'selector' => $item,
						]
					);
					$this->add_control(
						'box_box_shadow',
						[
							'type'     => Controls_Manager::BOX_SHADOW,
							'label'    => __( 'Box Shadow', 'compactform' ),
							'selector' => $item,
						]
					);
				$this->end_controls_tab();
				$this->start_controls_tab(
					'box_tab_checked',
					[
						'label' => __( 'Checked', 'compactform' ),
					]
				);
					$this->add_control(
						'box_background_color_checked',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Background Color', 'compactform' ),
							'selectors' => [ $item . ':has(:checked)' => 'background-color: {{VALUE}};' ],
						]
					);
					$this->add_control(
						'box_border_color_checked',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Border Color', 'compactform' ),
							'selectors' => [ $item . ':has(:checked)' => 'border-color: {{VALUE}} !important;' ],
							'condition' => [ 'box_border[style]!' => '' ],
						]
					);
					$this->add_control(
						'box_box_shadow_checked',
						[
							'type'     => Controls_Manager::BOX_SHADOW,
							'label'    => __( 'Box Shadow', 'compactform' ),
							'selector' => $item . ':has(:checked)',
						]
					);
				$this->end_controls_tab();
			$this->end_controls_tabs();
		$this->end_section();

		$this->start_section(
			'acceptance_input',
			[
				'label' => __( 'Input', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
			$this->add_control(
				'input_size',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Size', 'compactform' ),
					'units'      => [ 'px', 'em', 'rem', 'custom' ],
					'min'        => 0,
					'max'        => 60,
					'responsive' => true,
					'selectors'  => [ $input => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ],
				]
			);
			$this->add_control(
				'input_gap',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Gap', 'compactform' ),
					'units'      => [ 'px', 'em', 'rem', 'custom' ],
					'min'        => 0,
					'max'        => 50,
					'responsive' => true,
					'selectors'  => [ $label => 'margin-inline-start: {{SIZE}}{{UNIT}};' ],
				]
			);
			$this->add_control(
				'input_accent',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Accent Color', 'compactform' ),
					'selectors' => [ $input => 'accent-color: {{VALUE}};' ],
				]
			);
		$this->end_section();

		$this->start_section(
			'acceptance_text',
			[
				'label' => __( 'Text', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
			$this->add_control(
				'text_typography',
				[
					'type'     => Controls_Manager::TYPOGRAPHY,
					'label'    => __( 'Typography', 'compactform' ),
					'selector' => $label,
				]
			);
			$this->start_controls_tabs( 'text_style_tabs' );
				$this->start_controls_tab(
					'text_tab_normal',
					[
						'label' => __( 'Normal', 'compactform' ),
					]
				);
					$this->add_control(
						'text_color',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Color', 'compactform' ),
							'selectors' => [ $label => 'color: {{VALUE}};' ],
						]
					);
				$this->end_controls_tab();
				$this->start_controls_tab(
					'text_tab_checked',
					[
						'label' => __( 'Checked', 'compactform' ),
					]
				);
					$this->add_control(
						'text_color_checked',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Color', 'compactform' ),
							'selectors' => [ $item . ':has(:checked) .wpcf7-list-item-label' => 'color: {{VALUE}};' ],
						]
					);
				$this->end_controls_tab();
			$this->end_controls_tabs();
		$this->end_section();
	}

	public function compile( array $data ): string {
		// Forms saved before the `label` control existed stored the checkbox
		// wording under `label` itself (no `text` key at all). Read it from
		// there and keep it out of the new field-label heading.
		$legacy = ! array_key_exists( 'text', $data );
		$text   = trim( (string) ( $legacy ? ( $data['label'] ?? '' ) : $data['text'] ) );
		if ( '' === $text ) {
			$text = 'I accept the terms and conditions.';
		}
		if ( $legacy ) {
			$data['label'] = '';
		}

		$optional = empty( $data['required'] ) ? ' optional' : '';
		$markup   = '[acceptance ' . Field_Helpers::name( $data ) . $optional . Field_Helpers::atts( $data, false ) . ' "' . Field_Helpers::q( $text ) . '"]';

		return Field_Helpers::label_wrap( $data, $markup, true );
	}
}
