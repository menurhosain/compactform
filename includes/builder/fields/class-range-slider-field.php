<?php

namespace CompactForm\Builder\Fields;

use CompactForm\Builder\Controls_Manager;

use CompactForm\Builder\Abstracts\Extension_Field;
use CompactForm\Builder\Abstracts\Traits\Label_Control;
use CompactForm\Builder\Abstracts\Traits\Name_Control;
use CompactForm\Builder\Abstracts\Traits\Description_Control;
use CompactForm\Builder\Abstracts\Traits\Required_Control;
use CompactForm\Builder\Abstracts\Traits\Width_Control;
use CompactForm\Builder\Abstracts\Traits\Flex_Item_Control;
use CompactForm\Builder\Abstracts\Traits\Label_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Description_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Advanced_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Control;

defined( 'ABSPATH' ) || die();

class Range_Slider_Field extends Extension_Field {

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

	private const MAX_TICKS = 50;

	public function __construct() {
		parent::__construct();

		add_filter( 'wpcf7_validate_' . $this->get_tag(), [ $this, 'validate_range' ], 10, 2 );
		add_filter( 'wpcf7_validate_' . $this->get_tag() . '*', [ $this, 'validate_range' ], 10, 2 );
	}

	public function get_type(): string { return 'fcf7_range_slider'; }
	public function get_title(): string { return __( 'Range Slider', 'compactform' ); }
	public function get_icon(): string { return 'ri-equalizer-line'; }
	protected function get_tag(): string { return 'fcf7_range_slider'; }

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
			'range',
			[
				'label' => __( 'Range', 'compactform' ),
				'tab'   => Controls_Manager::TAB_SETTINGS,
			]
		);
			$this->add_control(
				'handles',
				[
					'type'    => Controls_Manager::SELECT,
					'label'   => __( 'Handles', 'compactform' ),
					'default' => '1',
					'options' => [
						'1' => __( 'Single — one value', 'compactform' ),
						'2' => __( 'Double — a from/to range', 'compactform' ),
					],
				]
			);
			$this->add_control(
				'min',
				[
					'type'    => Controls_Manager::NUMBER,
					'label'   => __( 'Minimum', 'compactform' ),
					'default' => 0,
					'label_block' => false,
				]
			);
			$this->add_control(
				'max',
				[
					'type'    => Controls_Manager::NUMBER,
					'label'   => __( 'Maximum', 'compactform' ),
					'default' => 100,
					'label_block' => false,
				]
			);
			$this->add_control(
				'step',
				[
					'type'    => Controls_Manager::NUMBER,
					'label'   => __( 'Step', 'compactform' ),
					'default' => 1,
					'label_block' => false,
				]
			);
			$this->add_control(
				'default_value',
				[
					'type'      => Controls_Manager::NUMBER,
					'label'     => __( 'Default Value', 'compactform' ),
					'description'      => __( 'Blank = maximum', 'compactform' ),
					'condition' => [ 'handles' => '1' ],
					'label_block' => false,
				]
			);
			$this->add_control(
				'default_min',
				[
					'type'      => Controls_Manager::NUMBER,
					'label'     => __( 'Default From', 'compactform' ),
					'description'      => __( 'Blank = minimum', 'compactform' ),
					'condition' => [ 'handles' => '2' ],
					'label_block' => false,
				]
			);
			$this->add_control(
				'default_max',
				[
					'type'      => Controls_Manager::NUMBER,
					'label'     => __( 'Default To', 'compactform' ),
					'description'      => __( 'Blank = maximum', 'compactform' ),
					'condition' => [ 'handles' => '2' ],
					'label_block' => false,
				]
			);
			$this->add_control(
				'suffix',
				[
					'type'        => Controls_Manager::TEXT,
					'label'       => __( 'Unit', 'compactform' ),
					'placeholder' => __( 'kg', 'compactform' ),
					'description' => __( 'Shown after every number. Display only — the submitted value stays numeric.', 'compactform' ),
					'label_block' => false,
				]
			);
			$this->add_control(
				'separator',
				[
					'type'        => Controls_Manager::TEXT,
					'label'       => __( 'Separator', 'compactform' ),
					'default'     => '-',
					'description' => __( 'Sits between the two values, in the readout and in the submitted value.', 'compactform' ),
					'condition'   => [ 'handles' => '2' ],
					'label_block' => false,
				]
			);
		$this->end_section();

		$this->start_section(
			'range_readout',
			[
				'label' => __( 'Readout', 'compactform' ),
				'tab'   => Controls_Manager::TAB_SETTINGS,
			]
		);
			$this->add_control(
				'hide_value',
				[
					'type'  => Controls_Manager::SWITCHER,
					'label' => __( 'Hide Current Value', 'compactform' ),
				]
			);
			$this->add_control(
				'value_pos',
				[
					'type'      => Controls_Manager::SELECT,
					'label'     => __( 'Value Position', 'compactform' ),
					'default'   => 'bubble',
					'options'   => [
						'bubble' => __( 'Bubble — follows the handle', 'compactform' ),
						'top'    => __( 'Above the track', 'compactform' ),
						'bottom' => __( 'Below the track', 'compactform' ),
						'end'    => __( 'Beside the track', 'compactform' ),
						'split'  => __( 'Split to both ends', 'compactform' ),
					],
					'condition' => [
						'hide_value' => ''
					],
				]
			);
			$this->add_control(
				'ticks',
				[
					'type'        => Controls_Manager::NUMBER,
					'label'       => __( 'Step Marks', 'compactform' ),
					'default'     => 0,
					'min'         => 0,
					'max'         => self::MAX_TICKS,
					'placeholder' => '0',
					'description' => __( 'How many segments to divide the track into. A dot and a number are drawn at each division. 0 turns them off.', 'compactform' ),
					'separator'   => 'before',
				]
			);
			$this->add_control(
				'hide_minmax',
				[
					'type'      => Controls_Manager::SWITCHER,
					'label'     => __( 'Hide Min / Max Labels', 'compactform' ),
					'separator' => 'before',
				]
			);
			$this->add_control(
				'min_label',
				[
					'type'        => Controls_Manager::TEXT,
					'label'       => __( 'Minimum Label', 'compactform' ),
					'placeholder' => __( 'Min', 'compactform' ),
					'description'        => __( 'No spaces', 'compactform' ),
					'condition'   => [ 'hide_minmax' => '' ],
				]
			);
			$this->add_control(
				'max_label',
				[
					'type'        => Controls_Manager::TEXT,
					'label'       => __( 'Maximum Label', 'compactform' ),
					'placeholder' => __( 'Max', 'compactform' ),
					'description'        => __( 'No spaces', 'compactform' ),
					'condition'   => [ 'hide_minmax' => '' ],
				]
			);
		$this->end_section();

		$this->register_label_style_controls();
		$this->register_description_style_controls();
		$this->register_track_style_controls();
		$this->register_handle_style_controls();
		$this->register_value_style_controls();
		$this->register_tick_style_controls();
		$this->register_minmax_style_controls();
		$this->register_advanced_controls();
		$this->register_conditional_controls();
	}

	private function register_track_style_controls(): void {
		$root = '{{WRAPPER}} .fcf7-range';

		$this->start_section(
			'section_range_track_style',
			[
				'label' => __( 'Track', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
			$this->add_control(
				'track_height',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Height', 'compactform' ),
					'units'      => [ 'px', 'em', 'rem', 'custom' ],
					'min'        => 0,
					'max'        => 40,
					'selectors'  => [ $root => '--fcf7-range-track-height: {{SIZE}}{{UNIT}};' ],
					'responsive' => true,
				]
			);
			$this->add_control(
				'track_radius',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Border Radius', 'compactform' ),
					'units'      => [ 'px', '%', 'em', 'rem', 'custom' ],
					'min'        => 0,
					'max'        => 50,
					'selectors'  => [ $root => '--fcf7-range-track-radius: {{SIZE}}{{UNIT}};' ],
					'responsive' => true,
				]
			);
			$this->add_control(
				'track_color',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Track Color', 'compactform' ),
					'selectors' => [ $root => '--fcf7-range-track-bg: {{VALUE}};' ],
				]
			);
			$this->add_control(
				'fill_color',
				[
					'type'        => Controls_Manager::COLOR,
					'label'       => __( 'Selection Color', 'compactform' ),
					'description' => __( 'The filled part of the track.', 'compactform' ),
					'selectors'   => [ $root => '--fcf7-range-fill-bg: {{VALUE}};' ],
				]
			);
			$this->add_control(
				'track_margin',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Margin', 'compactform' ),
					'selectors'  => [
						$root . ' ' . '.fcf7-range-track' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'area_margin',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Area Margin', 'compactform' ),
					'selectors'  => [
						$root => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
		$this->end_section();
	}

	private function register_handle_style_controls(): void {
		$root = '{{WRAPPER}} .fcf7-range';

		$this->start_section(
			'section_range_handle_style',
			[
				'label' => __( 'Handle', 'compactform' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
			$this->add_control(
				'handle_size',
				[
					'type'        => Controls_Manager::SLIDER,
					'label'       => __( 'Size', 'compactform' ),
					'units'       => [ 'px', 'em', 'rem', 'custom' ],
					'min'         => 0,
					'max'         => 80,
					'description' => __( 'Also sets the row height the track is centred in.', 'compactform' ),
					'selectors'   => [ $root => '--fcf7-range-handle-size: {{SIZE}}{{UNIT}};' ],
					'responsive'  => true,
				]
			);
			$this->add_control(
				'handle_radius',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Border Radius', 'compactform' ),
					'units'      => [ 'px', '%', 'em', 'rem', 'custom' ],
					'min'        => 0,
					'max'        => 50,
					'selectors'  => [ $root => '--fcf7-range-handle-radius: {{SIZE}}{{UNIT}};' ],
					'responsive' => true,
				]
			);

			$this->start_controls_tabs( 'handle_style_tabs', [ 'separator' => 'before' ] );

				$this->start_controls_tab(
					'handle_tab_normal',
					[
						'label' => __( 'Normal', 'compactform' ),
					]
				);
					$this->add_control(
						'handle_color',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Background Color', 'compactform' ),
							'selectors' => [ $root => '--fcf7-range-handle-bg: {{VALUE}};' ],
						]
					);
					$this->add_control(
						'handle_border_color',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Border Color', 'compactform' ),
							'selectors' => [ $root => '--fcf7-range-handle-border-color: {{VALUE}};' ],
						]
					);
					$this->add_control(
						'handle_border_width',
						[
							'type'      => Controls_Manager::SLIDER,
							'label'     => __( 'Border Width', 'compactform' ),
							'units'     => [ 'px' ],
							'min'       => 0,
							'max'       => 12,
							'selectors' => [ $root => '--fcf7-range-handle-border-width: {{SIZE}}{{UNIT}};' ],
						]
					);
					$this->add_control(
						'handle_shadow',
						[
							'type'        => Controls_Manager::COLOR,
							'label'       => __( 'Glow Color', 'compactform' ),
							'description' => __( 'A soft ring around the handle.', 'compactform' ),
							'selectors'   => [ $root => '--fcf7-range-handle-shadow-color: {{VALUE}};' ],
						]
					);
				$this->end_controls_tab();

				$this->start_controls_tab(
					'handle_tab_hover',
					[
						'label' => __( 'Hover', 'compactform' ),
					]
				);
					$this->add_control(
						'handle_color_hover',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Background Color', 'compactform' ),
							'selectors' => [ $root . ':hover' => '--fcf7-range-handle-bg: {{VALUE}};' ],
						]
					);
					$this->add_control(
						'handle_border_color_hover',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Border Color', 'compactform' ),
							'selectors' => [ $root . ':hover' => '--fcf7-range-handle-border-color: {{VALUE}};' ],
						]
					);
					$this->add_control(
						'handle_shadow_hover',
						[
							'type'      => Controls_Manager::COLOR,
							'label'     => __( 'Glow Color', 'compactform' ),
							'selectors' => [ $root . ':hover' => '--fcf7-range-handle-shadow-color: {{VALUE}};' ],
						]
					);
				$this->end_controls_tab();

			$this->end_controls_tabs();
		$this->end_section();
	}

	private function register_value_style_controls(): void {
		$item = '{{WRAPPER}} .fcf7-range-readout-item';

		$this->start_section(
			'section_range_value_style',
			[
				'label'     => __( 'Value Readout', 'compactform' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'hide_value' => '' ],
			]
		);
			$this->add_control(
				'value_typography',
				[
					'type'       => Controls_Manager::TYPOGRAPHY,
					'label'      => __( 'Typography', 'compactform' ),
					'selector'   => $item,
					'responsive' => true,
				]
			);
			$this->add_control(
				'value_color',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Text Color', 'compactform' ),
					'selectors' => [ $item => 'color: {{VALUE}};' ],
				]
			);
			$this->add_control(
				'value_bg_color',
				[
					'type'  => Controls_Manager::COLOR,
					'label' => __( 'Background Color', 'compactform' ),
					'selectors' => [ '{{WRAPPER}} .fcf7-range' => '--fcf7-range-value-bg: {{VALUE}};' ],
				]
			);
			$this->add_control(
				'value_padding',
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
				'value_radius',
				[
					'type'       => Controls_Manager::DIMENSIONS,
					'label'      => __( 'Border Radius', 'compactform' ),
					'selectors'  => [
						$item => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
					'responsive' => true,
				]
			);
			$this->add_control(
				'value_border',
				[
					'type'     => Controls_Manager::BORDER,
					'label'    => __( 'Border', 'compactform' ),
					'selector' => $item,
				]
			);
			$this->add_control(
				'value_align',
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
					'selectors'   => [ '{{WRAPPER}} .fcf7-range-readout' => 'justify-content: {{VALUE}};' ],
					'condition'   => [ 'value_pos' => [ 'top', 'bottom' ] ],
				]
			);
		$this->end_section();
	}

	private function register_tick_style_controls(): void {
		$root   = '{{WRAPPER}} .fcf7-range';
		$labels = '{{WRAPPER}} .fcf7-range-tick-labels';

		$this->start_section(
			'section_range_tick_style',
			[
				'label'     => __( 'Step Marks', 'compactform' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'ticks!' => [ '', '0' ] ],
			]
		);
			$this->add_control(
				'tick_size',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Dot Size', 'compactform' ),
					'units'      => [ 'px', 'em', 'rem', 'custom' ],
					'min'        => 0,
					'max'        => 30,
					'selectors'  => [ $root => '--fcf7-range-tick-size: {{SIZE}}{{UNIT}};' ],
					'responsive' => true,
				]
			);
			$this->add_control(
				'tick_color',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Dot Color', 'compactform' ),
					'selectors' => [ $root => '--fcf7-range-tick-bg: {{VALUE}};' ],
				]
			);
			$this->add_control(
				'tick_label_typography',
				[
					'type'       => Controls_Manager::TYPOGRAPHY,
					'label'      => __( 'Number Typography', 'compactform' ),
					'selector'   => $labels,
					'responsive' => true,
				]
			);
			$this->add_control(
				'tick_label_color',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Number Color', 'compactform' ),
					'selectors' => [ $labels => 'color: {{VALUE}};' ],
				]
			);
		$this->end_section();
	}

	private function register_minmax_style_controls(): void {
		$scale = '{{WRAPPER}} .fcf7-range-scale';

		$this->start_section(
			'section_range_minmax_style',
			[
				'label'     => __( 'Min / Max Labels', 'compactform' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [ 'hide_minmax' => '' ],
			]
		);
			$this->add_control(
				'minmax_typography',
				[
					'type'       => Controls_Manager::TYPOGRAPHY,
					'label'      => __( 'Typography', 'compactform' ),
					'selector'   => $scale,
					'responsive' => true,
				]
			);
			$this->add_control(
				'minmax_color',
				[
					'type'      => Controls_Manager::COLOR,
					'label'     => __( 'Text Color', 'compactform' ),
					'selectors' => [ $scale => 'color: {{VALUE}};' ],
				]
			);
			$this->add_control(
				'minmax_spacing',
				[
					'type'       => Controls_Manager::SLIDER,
					'label'      => __( 'Spacing', 'compactform' ),
					'units'      => [ 'px', 'em', 'rem', 'custom' ],
					'min'        => 0,
					'max'        => 60,
					'selectors'  => [ $scale => 'margin-top: {{SIZE}}{{UNIT}};' ],
					'responsive' => true,
				]
			);
		$this->end_section();
	}

	protected function option_map(): array {
		return [
			[ 'prop' => 'handles',       'opt' => 'handles' ],
			[ 'prop' => 'min',           'opt' => 'min' ],
			[ 'prop' => 'max',           'opt' => 'max' ],
			[ 'prop' => 'step',          'opt' => 'step' ],
			[ 'prop' => 'default_value', 'opt' => 'default' ],
			[ 'prop' => 'default_min',   'opt' => 'default_min' ],
			[ 'prop' => 'default_max',   'opt' => 'default_max' ],
			[ 'prop' => 'suffix',        'opt' => 'suffix' ],
			[ 'prop' => 'separator',     'opt' => 'separator' ],
			[ 'prop' => 'hide_value',    'opt' => 'hide_value', 'type' => 'bool', 'on' => 'on' ],
			[ 'prop' => 'value_pos',     'opt' => 'value_pos' ],
			[ 'prop' => 'ticks',         'opt' => 'ticks' ],
			[ 'prop' => 'hide_minmax',   'opt' => 'hide_minmax', 'type' => 'bool', 'on' => 'on' ],
			[ 'prop' => 'min_label',     'opt' => 'min_label' ],
			[ 'prop' => 'max_label',     'opt' => 'max_label' ],
		];
	}

	protected function register_style(): array {
		wp_register_style( 'fcf7-range-slider', FCF7_ASSETS . 'css/range-slider.min.css', [], FCF7_VERSION );
		return [ 'fcf7-range-slider' ];
	}
	
	protected function register_preview_style(): array {
		wp_register_style( 'fcf7-range-slider', FCF7_ASSETS . 'css/range-slider.min.css', [], FCF7_VERSION );
		return [ 'fcf7-range-slider' ];
	}

	protected function register_script(): array {
		wp_register_script( 'fcf7-range-slider', FCF7_ASSETS . 'js/range-slider.min.js', [], FCF7_VERSION, true );
		return [ 'fcf7-range-slider' ];
	}

	public function render_front( $tag, $form_id = 0 ) {
		if ( empty( $tag->name ) ) {
			return '';
		}

		$s = $this->settings( $tag );

		$validation_error = wpcf7_get_validation_error( $tag->name );

		$class = wpcf7_form_controls_class( $tag->type );
		$class .= ' fcf7-range fcf7-range--' . ( 2 === $s['handles'] ? 'double' : 'single' );
		$class .= ' fcf7-range--value-' . $s['value_pos'];
		if ( $validation_error ) {
			$class .= ' wpcf7-not-valid';
		}

		$atts = [
			'class'          => $tag->get_class_option( $class ),
			'id'             => $tag->get_id_option(),
			'data-min'       => $s['min'],
			'data-max'       => $s['max'],
			'data-step'      => $s['step'],
			'data-handles'   => $s['handles'],
			'data-separator' => $s['separator'],
			'data-suffix'    => $s['suffix'],
		];
		if ( $tag->is_required() ) {
			$atts['aria-required'] = 'true';
		}
		if ( $validation_error ) {
			$atts['aria-describedby'] = wpcf7_get_validation_error_reference( $tag->name );
		}
		$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

		$from = $s['from'];
		$to   = $s['to'];

		$atts['style'] = sprintf(
			'--fcf7-range-from:%s;--fcf7-range-to:%s;',
			2 === $s['handles'] ? $this->position_of( $this->ratio( $from, $s ) ) : '0%',
			$this->position_of( $this->ratio( $to, $s ) )
		);

		$readout_before = 'bottom' !== $s['value_pos'] && 'split' !== $s['value_pos'];

		ob_start();
		?>
		<span class="wpcf7-form-control-wrap <?php echo esc_attr( $tag->name ); ?>" data-name="<?php echo esc_attr( $tag->name ); ?>">
			<span <?php echo wp_kses( wpcf7_format_atts( $atts ), array() ); ?>>
				<?php if ( 'on' === $s['show_value'] && $readout_before ) : ?>
					<?php $this->readout( $s, $from, $to ); ?>
				<?php endif; ?>

				<span class="fcf7-range-track">
					<span class="fcf7-range-rail" aria-hidden="true"></span>
					<span class="fcf7-range-fill" aria-hidden="true"></span>
					<?php $this->ticks( $s ); ?>
					<?php if ( 2 === $s['handles'] ) : ?>
						<input type="range" class="fcf7-range-input fcf7-range-input--from"
							min="<?php echo esc_attr( $s['min'] ); ?>" max="<?php echo esc_attr( $s['max'] ); ?>"
							step="<?php echo esc_attr( $s['step'] ); ?>" value="<?php echo esc_attr( $from ); ?>"
							aria-label="<?php echo esc_attr( $s['min_label'] ); ?>" />
						<input type="range" class="fcf7-range-input fcf7-range-input--to"
							min="<?php echo esc_attr( $s['min'] ); ?>" max="<?php echo esc_attr( $s['max'] ); ?>"
							step="<?php echo esc_attr( $s['step'] ); ?>" value="<?php echo esc_attr( $to ); ?>"
							aria-label="<?php echo esc_attr( $s['max_label'] ); ?>" />
						<input type="hidden" name="<?php echo esc_attr( $tag->name ); ?>" class="fcf7-range-value-field"
							value="<?php echo esc_attr( $this->pair_value( $from, $to, $s['separator'] ) ); ?>" />
					<?php else : ?>
						<input type="range" class="fcf7-range-input fcf7-range-input--to"
							name="<?php echo esc_attr( $tag->name ); ?>"
							min="<?php echo esc_attr( $s['min'] ); ?>" max="<?php echo esc_attr( $s['max'] ); ?>"
							step="<?php echo esc_attr( $s['step'] ); ?>" value="<?php echo esc_attr( $to ); ?>" />
					<?php endif; ?>
				</span>

				<?php $this->tick_labels( $s ); ?>

				<?php if ( 'on' === $s['show_minmax'] ) : ?>
					<span class="fcf7-range-scale">
						<span class="fcf7-range-scale-min">
							<?php if ( '' !== $s['min_label'] ) : ?>
								<span class="fcf7-range-scale-label"><?php echo esc_html( $s['min_label'] ); ?></span>
							<?php endif; ?>
							<span class="fcf7-range-scale-number"><?php echo esc_html( $s['min'] ); ?><?php echo esc_html( $s['suffix'] ); ?></span>
						</span>
						<span class="fcf7-range-scale-max">
							<?php if ( '' !== $s['max_label'] ) : ?>
								<span class="fcf7-range-scale-label"><?php echo esc_html( $s['max_label'] ); ?></span>
							<?php endif; ?>
							<span class="fcf7-range-scale-number"><?php echo esc_html( $s['max'] ); ?><?php echo esc_html( $s['suffix'] ); ?></span>
						</span>
					</span>
				<?php endif; ?>

				<?php if ( 'on' === $s['show_value'] && ! $readout_before ) : ?>
					<?php $this->readout( $s, $from, $to ); ?>
				<?php endif; ?>
			</span>
			<?php echo wp_kses( $validation_error, array( 'span' => array( 'class' => true, 'aria-hidden' => true ) ) ); ?>
		</span>
		<?php

		return apply_filters( 'fcf7_range_slider_output', ob_get_clean(), $tag );
	}

	private function readout( array $s, $from, $to ): void {
		?>
		<span class="fcf7-range-readout">
			<?php if ( 2 === $s['handles'] ) : ?>
				<span class="fcf7-range-readout-item" data-handle="from" style="--pos:<?php echo esc_attr( $this->position_of( $this->ratio( $from, $s ) ) ); ?>">
					<?php if ( 'split' === $s['value_pos'] ) : ?>
						<span class="fcf7-range-readout-label"><?php echo esc_html( $s['min_label'] ); ?></span>
					<?php endif; ?>
					<span class="fcf7-range-number"><?php echo esc_html( $from ); ?></span><span class="fcf7-range-suffix"><?php echo esc_html( $s['suffix'] ); ?></span>
				</span>
				<span class="fcf7-range-readout-sep"><?php echo esc_html( $s['separator'] ); ?></span>
			<?php endif; ?>
			<span class="fcf7-range-readout-item" data-handle="to" style="--pos:<?php echo esc_attr( $this->position_of( $this->ratio( $to, $s ) ) ); ?>">
				<?php if ( 'split' === $s['value_pos'] && 2 === $s['handles'] ) : ?>
					<span class="fcf7-range-readout-label"><?php echo esc_html( $s['max_label'] ); ?></span>
				<?php endif; ?>
				<span class="fcf7-range-number"><?php echo esc_html( $to ); ?></span><span class="fcf7-range-suffix"><?php echo esc_html( $s['suffix'] ); ?></span>
			</span>
		</span>
		<?php
	}

	private function ticks( array $s ): void {
		if ( $s['ticks'] < 1 ) {
			return;
		}
		?>
		<span class="fcf7-range-ticks" aria-hidden="true">
			<?php for ( $i = 0; $i <= $s['ticks']; $i++ ) : ?>
				<span class="fcf7-range-tick" style="--pos:<?php echo esc_attr( $this->position_of( $i / $s['ticks'] ) ); ?>"></span>
			<?php endfor; ?>
		</span>
		<?php
	}

	private function tick_labels( array $s ): void {
		if ( $s['ticks'] < 1 ) {
			return;
		}
		?>
		<span class="fcf7-range-tick-labels" aria-hidden="true">
			<?php
			for ( $i = 0; $i <= $s['ticks']; $i++ ) :
				$value = $s['min'] + ( $s['max'] - $s['min'] ) * $i / $s['ticks'];
				?>
				<span class="fcf7-range-tick-label" style="--pos:<?php echo esc_attr( $this->position_of( $i / $s['ticks'] ) ); ?>">
					<?php echo esc_html( $this->format( $value, $s['step'] ) ); ?>
				</span>
			<?php endfor; ?>
		</span>
		<?php
	}

	private function ratio( $value, array $s ): float {
		$span  = $s['max'] - $s['min'];
		$ratio = $span > 0 ? ( $value - $s['min'] ) / $span : 0;

		return (float) max( 0, min( 1, $ratio ) );
	}

	private function position_of( float $ratio ): string {
		$factor = rtrim( rtrim( number_format( $ratio, 5, '.', '' ), '0' ), '.' );

		return sprintf(
			'calc(var(--fcf7-range-handle-size) / 2 + %s * (100%% - var(--fcf7-range-handle-size)))',
			'' === $factor ? '0' : $factor
		);
	}

	private function format( $value, $step ): string {
		$decimals = 0;
		$text     = (string) $step;
		$dot      = strpos( $text, '.' );
		if ( false !== $dot ) {
			$decimals = strlen( $text ) - $dot - 1;
		}

		return number_format( (float) $value, $decimals, '.', '' );
	}

	private function pair_value( $from, $to, string $separator ): string {
		return $from . ' ' . $separator . ' ' . $to;
	}

	private function settings( $tag ): array {
		$num = static function ( $value, $fallback ) {
			return is_numeric( $value ) ? 0 + $value : $fallback;
		};

		$min  = $num( $tag->get_option( 'min', '', true ), 0 );
		$max  = $num( $tag->get_option( 'max', '', true ), 100 );
		$step = $num( $tag->get_option( 'step', '', true ), 1 );

		// A max below the min, or a non-positive step, would make the control
		// unusable (and divide by zero when positioning the fill).
		if ( $max <= $min ) {
			$max = $min + 100;
		}
		if ( $step <= 0 ) {
			$step = 1;
		}

		$handles = '2' === (string) $tag->get_option( 'handles', '', true ) ? 2 : 1;

		if ( 2 === $handles ) {
			$from = $this->clamp( $num( $tag->get_option( 'default_min', '', true ), $min ), $min, $max );
			$to   = $this->clamp( $num( $tag->get_option( 'default_max', '', true ), $max ), $min, $max );
			if ( $from > $to ) {
				[ $from, $to ] = [ $to, $from ];
			}
		} else {
			$from = $min;
			$to   = $this->clamp( $num( $tag->get_option( 'default', '', true ), $max ), $min, $max );
		}

		$value_pos = (string) $tag->get_option( 'value_pos', '', true );

		return [
			'handles'     => $handles,
			'min'         => $min,
			'max'         => $max,
			'step'        => $step,
			'from'        => $from,
			'to'          => $to,
			'suffix'      => (string) $tag->get_option( 'suffix', '', true ),
			'separator'   => ( '' !== (string) $tag->get_option( 'separator', '', true ) ) ? (string) $tag->get_option( 'separator', '', true ) : '-',
			'show_value'  => 'on' === (string) $tag->get_option( 'hide_value', '', true ) ? 'off' : 'on',
			'value_pos'   => in_array( $value_pos, [ 'bubble', 'top', 'bottom', 'end', 'split' ], true ) ? $value_pos : 'bubble',
			'ticks'       => (int) max( 0, min( self::MAX_TICKS, $num( $tag->get_option( 'ticks', '', true ), 0 ) ) ),
			'show_minmax' => 'on' === (string) $tag->get_option( 'hide_minmax', '', true ) ? 'off' : 'on',
			'min_label'   => ( '' !== (string) $tag->get_option( 'min_label', '', true ) ) ? (string) $tag->get_option( 'min_label', '', true ) : __( 'Min', 'compactform' ),
			'max_label'   => ( '' !== (string) $tag->get_option( 'max_label', '', true ) ) ? (string) $tag->get_option( 'max_label', '', true ) : __( 'Max', 'compactform' ),
		];
	}

	private function clamp( $value, $min, $max ) {
		return max( $min, min( $max, $value ) );
	}

	public function validate_req_data( $schema, $tag, array $form_schema = array(), $contact_form = null ) {
		$s = $this->settings( $tag );

		if ( $tag->is_required() ) {
			$schema->add_rule(
				wpcf7_swv_create_rule( 'required', [
					'field' => $tag->name,
					'error' => wpcf7_get_message( 'invalid_required' ),
				] )
			);
		}

		if ( 2 === $s['handles'] ) {
			return;
		}

		$schema->add_rule(
			wpcf7_swv_create_rule( 'number', [
				'field' => $tag->name,
				'error' => wpcf7_get_message( 'invalid_number' ),
			] )
		);
		$schema->add_rule(
			wpcf7_swv_create_rule( 'minnumber', [
				'field'     => $tag->name,
				'threshold' => $s['min'],
				'error'     => wpcf7_get_message( 'number_too_small' ),
			] )
		);
		$schema->add_rule(
			wpcf7_swv_create_rule( 'maxnumber', [
				'field'     => $tag->name,
				'threshold' => $s['max'],
				'error'     => wpcf7_get_message( 'number_too_large' ),
			] )
		);
	}

	public function validate_range( $result, $tag ) {
		$s = $this->settings( $tag );
		$value = trim( sanitize_text_field( wpcf7_superglobal_post( $tag->name ) ) );

		if ( '' === $value ) {
			if ( $tag->is_required() ) {
				$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
			}
			return $result;
		}

		$expected = 2 === $s['handles'] ? 2 : 1;

		preg_match_all( '/-?\d+(?:\.\d+)?/', $value, $matches );
		$numbers = array_map( 'floatval', $matches[0] );

		if ( count( $numbers ) !== $expected ) {
			$result->invalidate( $tag, __( 'Undefined value was submitted through this field.', 'compactform' ) );
			return $result;
		}

		foreach ( $numbers as $number ) {
			if ( $number < $s['min'] || $number > $s['max'] ) {
				$result->invalidate( $tag, __( 'The submitted value is outside the allowed range.', 'compactform' ) );
				return $result;
			}
		}

		if ( 2 === $expected && $numbers[0] > $numbers[1] ) {
			$result->invalidate( $tag, __( 'The submitted range is reversed.', 'compactform' ) );
		}

		return $result;
	}
}
