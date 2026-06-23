<?php

namespace CompactForm\Builder\Fields;

use CompactForm\Builder\Controls_Manager;
use CompactForm\Builder\Abstracts\Extension_Field;
use CompactForm\Builder\Abstracts\Traits\Label_Control;
use CompactForm\Builder\Abstracts\Traits\Name_Control;
use CompactForm\Builder\Abstracts\Traits\Required_Control;
use CompactForm\Builder\Abstracts\Traits\Width_Control;
use CompactForm\Builder\Abstracts\Traits\Flex_Item_Control;
use CompactForm\Builder\Abstracts\Traits\Label_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Description_Control;
use CompactForm\Builder\Abstracts\Traits\Description_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Advanced_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Control;

defined( 'ABSPATH' ) || die();

class Signature_Field extends Extension_Field {

	use Label_Control;
	use Name_Control;
	use Description_Control;
	use Required_Control;
	use Width_Control;
	use Flex_Item_Control;
	use Label_Style_Control;
	use Description_Style_Control;
	use Advanced_Control;
	use Conditional_Control;

	public function __construct() {
		parent::__construct();

		add_filter( 'wpcf7_validate_' . $this->get_tag(), [ $this, 'validate_signature' ], 10, 2 );
		add_filter( 'wpcf7_validate_' . $this->get_tag() . '*', [ $this, 'validate_signature' ], 10, 2 );
	}

	public function get_type(): string { return 'fcf7_signature'; }
	public function get_title(): string { return __( 'Digital Signature', 'compactform' ); }
	public function get_icon(): string { return 'ri-quill-pen-line'; }
	protected function get_tag(): string { return 'fcf7_signature'; }

	protected function tag_features(): array {
		return [ 'name-attr' => true, 'file-uploading' => true ];
	}

	protected function label_group(): bool {
		return true;
	}

	protected function register_controls(): void {
		$this->start_section(
			'general',
			[
				'label' => __( 'General', 'compactform' ),
				'tab'   => Controls_Manager::TAB_SETTINGS,
			]
		);
			$this->register_label_control();
			$this->register_name_control();
			$this->register_description_control();
			$this->register_required_control();
			$this->register_width_control();
			$this->register_flex_item_controls();
		$this->end_section();

		$this->start_section(
			'signature',
			[
				'label' => __( 'Signature Options', 'compactform' ),
				'tab'   => Controls_Manager::TAB_SETTINGS,
			]
		);
			$this->add_control(
				'clear_text',
				[
					'type'        => Controls_Manager::TEXT,
					'label'       => __( 'Clear Button Text', 'compactform' ),
					'placeholder' => __( 'Clear', 'compactform' ),
				]
			);
		$this->end_section();

		$this->register_label_style_controls();
		$this->register_description_style_controls();
		$this->register_signature_wrapper_style_controls();
		$this->register_pad_style_controls();
		$this->register_clear_button_style_controls();
		$this->register_advanced_controls();
		$this->register_conditional_controls();
	}

	private function register_signature_wrapper_style_controls(): void {
		$frame = '{{WRAPPER}} .fcf7-signature-frame';

		$this->start_section(
			'section_sign_wrapper_style',
			[
				'label' => __( 'Wrapper', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
			$this->add_control(
				'sign_wrapper_padding',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Padding', 'compactform' ),
					'selectors'  => [
						$frame => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'sign_wrapper_radius',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Border Radius', 'compactform' ),
					'selectors'  => [
						$frame => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'sign_wrapper_background_color',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Background Color', 'compactform' ),
					'selectors' => [
						$frame => 'background-color: {{VALUE}};',
					],
				]
			);
			$this->add_control(
				'sign_wrapper_border',
				[
					'type'     => Controls_Manager::BORDER,
					'label'    => __( 'Border', 'compactform' ),
					'selector' => $frame,
				]
			);
			$this->add_control(
				'sign_wrapper_box_shadow',
				[
					'type'     => Controls_Manager::BOX_SHADOW,
					'label'    => __( 'Box Shadow', 'compactform' ),
					'selector' => $frame,
				]
			);
		$this->end_section();
	}
	
	private function register_pad_style_controls(): void {
		$frame = '{{WRAPPER}} .fcf7-signature-frame';
		$canvas = '{{WRAPPER}} .fcf7-signature-pad canvas';

		$this->start_section(
			'section_pad_style',
			[
				'label' => __( 'Signature Pad', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
			$this->add_control(
				'pad_width',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Width', 'compactform' ),
					'units'      => [ 'px', '%', 'em', 'rem', 'custom' ],
					'ranges'     => [
						'px' => [ 'min' => 0, 'max' => 800 ],
						'%'  => [ 'min' => 0, 'max' => 100 ],
					],
					'min'        => 0,
					'max'        => 800,
					'selectors'  => [ $frame => 'width: {{SIZE}}{{UNIT}};' ],
					'responsive' => true,
				]
			);
			$this->add_control(
				'pad_height',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Height', 'compactform' ),
					'units'      => [ 'px', 'em', 'rem', 'custom' ],
					'min'        => 0,
					'max'        => 400,
					'selectors'  => [ $canvas => 'height: {{SIZE}}{{UNIT}};' ],
					'responsive' => true,
				]
			);
			$this->add_control(
				'pad_border',
				[
					'type'     => Controls_Manager::BORDER,
					'label'    => __( 'Border', 'compactform' ),
					'selector' => $canvas,
				]
			);
			$this->add_control(
				'pad_radius',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Border Radius', 'compactform' ),
					'selectors'  => [ $canvas => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
					'responsive' => true,
				]
			);
			$this->add_control(
				'pen_color',
				[
					'type'    => Controls_Manager::COLOR,
					'label'   => __( 'Pen Color', 'compactform' ),
				]
			);
			$this->add_control(
				'bg_color',
				[
					'type'    => Controls_Manager::COLOR,
					'label'   => __( 'Pad Background Color', 'compactform' ),
				]
			);
		$this->end_section();
	}

	private function register_clear_button_style_controls(): void {
		$actions = '{{WRAPPER}} .fcf7-signature-actions';
		$button  = '{{WRAPPER}} .fcf7-signature-clear';

		$this->start_section(
			'section_clear_button_style',
			[
				'label' => __( 'Clear Button', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
			$this->add_control(
				'clear_btn_typography',
				[
					'type'       => Controls_Manager::TYPOGRAPHY,
					'label'      => __( 'Typography', 'compactform' ),
					'selector'   => $button,
					'responsive' => true,
				]
			);
			$this->add_control(
				'clear_btn_align',
				[
					'type'        => Controls_Manager::CHOOSE,
					'label'       => __( 'Alignment', 'compactform' ),
					'label_block' => false,
					'toggle'      => true,
					'options'     => [
						'flex-start' => [
							'title' => __( 'Left', 'compactform' ),
							'icon'  => 'ri-align-left',
						],
						'center'     => [
							'title' => __( 'Center', 'compactform' ),
							'icon'  => 'ri-align-center',
						],
						'flex-end'   => [
							'title' => __( 'Right', 'compactform' ),
							'icon'  => 'ri-align-right',
						],
					],
					'responsive'  => true,
					'selectors'   => [ $actions => 'width: 100%; justify-content: {{VALUE}};' ],
				]
			);
			$this->add_control(
				'clear_btn_width',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Width', 'compactform' ),
					'units'      => [ 'px', '%', 'em', 'rem', 'custom' ],
					'ranges'     => [
						'px' => [ 'min' => 0, 'max' => 400 ],
						'%'  => [ 'min' => 0, 'max' => 100 ],
					],
					'min'        => 0,
					'max'        => 400,
					'selectors'  => [ $button => 'width: {{SIZE}}{{UNIT}};' ],
					'responsive' => true,
				]
			);
			$this->add_control(
				'clear_btn_padding',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Padding', 'compactform' ),
					'selectors'  => [
						$button => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'clear_btn_margin',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Margin', 'compactform' ),
					'selectors'  => [
						$button => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'clear_btn_radius',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Border Radius', 'compactform' ),
					'selectors'  => [
						$button => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'clear_btn_transition',
				[
					'type'      => Controls_Manager::SLIDER,
					'label'     => __( 'Transition Duration', 'compactform' ),
					'units'     => [ 's' ],
					'min'       => 0,
					'max'       => 3,
					'step'      => 0.1,
					'selectors' => [ $button => 'transition: all {{SIZE}}{{UNIT}} ease;' ],
				]
			);

			$this->start_controls_tabs( 'clear_btn_style_tabs', [ 'separator' => 'before' ] );

				$this->start_controls_tab(
					'clear_btn_tab_normal',
					[
						'label' => __( 'Normal', 'compactform' ),
					]
				);
					$this->add_control(
						'clear_btn_color',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Text Color', 'compactform' ),
							'selectors' => [ $button => 'color: {{VALUE}};' ],
						]
					);
					$this->add_control(
						'clear_btn_bg_color',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Background Color', 'compactform' ),
							'selectors' => [ $button => 'background-color: {{VALUE}};' ],
						]
					);
					$this->add_control(
						'clear_btn_border',
						[
							'type'     => Controls_Manager::BORDER,
							'label'    => __( 'Border', 'compactform' ),
							'selector' => $button,
						]
					);
					$this->add_control(
						'clear_btn_box_shadow',
						[
							'type'     => Controls_Manager::BOX_SHADOW,
							'label'    => __( 'Box Shadow', 'compactform' ),
							'selector' => $button,
						]
					);
				$this->end_controls_tab();

				$this->start_controls_tab(
					'clear_btn_tab_hover',
					[
						'label' => __( 'Hover', 'compactform' ),
					]
				);
					$this->add_control(
						'clear_btn_color_hover',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Text Color', 'compactform' ),
							'selectors' => [ $button . ':hover' => 'color: {{VALUE}};' ],
						]
					);
					$this->add_control(
						'clear_btn_bg_color_hover',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Background Color', 'compactform' ),
							'selectors' => [ $button . ':hover' => 'background-color: {{VALUE}};' ],
						]
					);
					$this->add_control(
						'clear_btn_border_color_hover',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Border Color', 'compactform' ),
							'selectors' => [ $button . ':hover' => 'border-color: {{VALUE}} !important;' ],
							'condition' => [ 'clear_btn_border[style]!' => '' ],
						]
					);
					$this->add_control(
						'clear_btn_box_shadow_hover',
						[
							'type'     => Controls_Manager::BOX_SHADOW,
							'label'    => __( 'Box Shadow', 'compactform' ),
							'selector' => $button . ':hover',
						]
					);
				$this->end_controls_tab();

			$this->end_controls_tabs();
		$this->end_section();
	}

	protected function option_map(): array {
		return [
			[ 'prop' => 'bg_color', 'opt' => 'bg_color' ],
			[ 'prop' => 'pen_color', 'opt' => 'pen_color' ],
		];
	}

	protected function tag_values( array $data ): array {
		return [ trim( (string) ( $data['clear_text'] ?? '' ) ) ];
	}

	protected function register_style(): array {
		wp_register_style( 'fcf7-signature', FCF7_ASSETS . 'css/signature.min.css', [], FCF7_VERSION );
		return [ 'fcf7-signature' ];
	}
	
	protected function register_preview_style(): array {
		wp_register_style( 'fcf7-signature', FCF7_ASSETS . 'css/signature.min.css', [], FCF7_VERSION );
		return [ 'fcf7-signature' ];
	}
	
	protected function register_script(): array {
		wp_register_script( 'fcf7-signature', FCF7_ASSETS . 'js/signature.min.js', [ 'fcf7-signature-pad' ], FCF7_VERSION, true );
		return [ 'fcf7-signature-pad', 'fcf7-signature' ];
	}

	public function render_front( $tag, $form_id = 0 ) {
		if ( empty( $tag->name ) ) {
			return '';
		}

		$validation_error = wpcf7_get_validation_error( $tag->name );

		$class = wpcf7_form_controls_class( $tag->type ) . ' fcf7-signature-frame';
		if ( $validation_error ) {
			$class .= ' wpcf7-not-valid';
		}

		$bg_color  = $tag->get_option( 'bg_color', '', true ) ?: '#bceeff';
		$pen_color = $tag->get_option( 'pen_color', '', true ) ?: '#000000';

		$values     = (array) $tag->values;
		$clear_text = '' !== trim( (string) ( $values[0] ?? '' ) ) ? $values[0] : __( 'Clear', 'compactform' );

		$wrap_atts = [
			'class' => $tag->get_class_option( $class ),
			'id'    => $tag->get_id_option(),
		];
		if ( $tag->is_required() ) {
			$wrap_atts['aria-required'] = 'true';
		}
		if ( $validation_error ) {
			$wrap_atts['aria-describedby'] = wpcf7_get_validation_error_reference( $tag->name );
		}
		$wrap_atts['aria-invalid'] = $validation_error ? 'true' : 'false';

		$file_atts = [
			'type'   => 'file',
			'name'   => $tag->name,
			'class'  => 'fcf7-signature-file',
			'accept' => 'image/png',
		];

		ob_start();
		?>
		<span class="wpcf7-form-control-wrap <?php echo esc_attr( $tag->name ); ?>" data-name="<?php echo esc_attr( $tag->name ); ?>">
			<span <?php echo wpcf7_format_atts( $wrap_atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpcf7_format_atts() already escapes attribute values; re-escaping breaks the output. ?>>
				<span class="fcf7-signature-pad" data-field="<?php echo esc_attr( $tag->name ); ?>" data-bg-color="<?php echo esc_attr( $bg_color ); ?>" data-pen-color="<?php echo esc_attr( $pen_color ); ?>">
					<canvas></canvas>
				</span>
				<span class="fcf7-signature-actions">
					<button type="button" class="fcf7-signature-clear"><?php echo esc_html( $clear_text ); ?></button>
				</span>
				<input hidden <?php echo wpcf7_format_atts( $file_atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpcf7_format_atts() already escapes attribute values; re-escaping breaks the output. ?> />
			</span>
			<?php echo $validation_error; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpcf7_get_validation_error() already returns escaped HTML markup; re-escaping breaks the output. ?>
		</span>
		<?php

		return apply_filters( 'fcf7_signature_output', ob_get_clean(), $tag );
	}

	public function validate_req_data( $schema, $tag, array $form_schema = array(), $contact_form = null ) {
		if ( ! $tag->is_required() ) {
			return;
		}

		$schema->add_rule(
			wpcf7_swv_create_rule( 'requiredfile', [
				'field' => $tag->name,
				'error' => wpcf7_get_message( 'invalid_required' ),
			] )
		);
	}

	public function validate_signature( $result, $tag ) {
		if ( ! $tag->is_required() ) {
			return $result;
		}

		// This fires on CF7's wpcf7_validate_{tag} filter, dispatched only after CF7 core has
		// already verified the submission's nonce; no separate nonce check belongs here.
		// $file is only used for a presence/emptiness check below, never output or stored.
		$file     = $_FILES[ $tag->name ] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$has_file = ! empty( $file['name'] ) && ( ! isset( $file['error'] ) || UPLOAD_ERR_NO_FILE !== $file['error'] );

		if ( ! $has_file ) {
			$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
		}

		return $result;
	}
}
