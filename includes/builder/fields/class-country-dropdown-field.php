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
use CompactForm\Builder\Abstracts\Traits\Field_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Advanced_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Control;

defined( 'ABSPATH' ) || die();

class Country_Dropdown_Field extends Extension_Field {

	use Label_Control;
	use Name_Control;
	use Required_Control;
	use Width_Control;
	use Flex_Item_Control;
	use Label_Style_Control;
	use Field_Style_Control;
	use Advanced_Control;
	use Conditional_Control;

	public function get_type(): string { return 'fcf7_country_dropdown'; }
	public function get_title(): string { return __( 'Country', 'compactform' ); }
	public function get_icon(): string { return 'ri-earth-line'; }
	protected function get_tag(): string { return 'fcf7_country_dropdown'; }
	public function get_preview(): string { return 'select'; }

	protected function register_controls(): void {
		$this->start_section( 'general', [
			'label' => __( 'General', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
		$this->register_label_control();
		$this->register_name_control();
		$this->register_required_control();
		$this->register_width_control();
		$this->register_flex_item_controls();
		$this->end_section();

		$this->start_section( 'country', [
			'label' => __( 'Country Options', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
		$this->add_control( 'default_country', [
			'type' => Controls_Manager::TEXT,
			'label' => __( 'Default country (ISO2)', 'compactform' ),
			'description' => __( 'e.g. bd', 'compactform' )
		] );
		$this->add_control( 'only_countries', [
			'type' => Controls_Manager::TEXT,
			'label' => __( 'Only these (pipe list)', 'compactform' ),
			'description' => 'us|ca|gb'
		] );
		$this->add_control( 'auto_complete', [
			'type' => Controls_Manager::SWITCHER,
			'label' => __( 'IP auto-complete', 'compactform' )
		] );
		$this->add_control( 'dynamic', [
			'type' => Controls_Manager::SWITCHER,
			'label' => __( 'Country → State → City', 'compactform' )
		] );
		$this->add_control( 'value_format', [
			'type'        => Controls_Manager::SELECT,
			'label'       => __( 'Submitted Value', 'compactform' ),
			'default'     => 'name',
			'options'     => [
				'name' => __( 'Full country name (e.g. Australia)', 'compactform' ),
				'code' => __( 'ISO2 code (e.g. AU)', 'compactform' ),
			],
			'description' => __( 'What this field posts/saves — the code is what integrations like Stripe expect.', 'compactform' ),
			'condition'   => [ 'dynamic!' => true ],
		] );
		$this->end_section();

		$this->register_label_style_controls();
		$this->register_field_style_controls( false, true );
		$this->register_advanced_controls();
		$this->register_conditional_controls();
	}

	protected function option_map(): array {
		return [
			[ 'prop' => 'default_country', 'opt' => 'default_country' ],
			[ 'prop' => 'only_countries', 'opt' => 'only_countries' ],
			[ 'prop' => 'auto_complete', 'opt' => 'auto_complete', 'type' => 'bool', 'on' => 'on' ],
			[ 'prop' => 'dynamic', 'opt' => 'dynamic', 'type' => 'bool', 'on' => 'on' ],
			[ 'prop' => 'value_format', 'opt' => 'value_format' ],
		];
	}

	protected function register_style(): array {
		wp_register_style( 'fcf7-country-dropdown', FCF7_ASSETS . 'css/country-dropdown.min.css', [ 'fcf7-country-select' ], FCF7_VERSION );
		return [ 'fcf7-country-select', 'fcf7-country-dropdown' ];
	}

	protected function register_script(): array {
		wp_register_script( 'fcf7-country-dropdown', FCF7_ASSETS . 'js/country-dropdown.min.js', [ 'jquery', 'fcf7-country-select-lib' ], FCF7_VERSION, true );

		wp_localize_script(
			'fcf7-country-dropdown',
			'FCF7Country',
			[
				'statesUrl' => FCF7_ASSETS . 'data/states.json',
				'i18n'      => [
					'selectCountry'   => __( 'Select a country', 'compactform' ),
					'selectState'     => __( 'Select a state', 'compactform' ),
					'noStates'        => __( 'No states — type below', 'compactform' ),
					'cityPlaceholder' => __( 'City', 'compactform' ),
				],
			]
		);

		return [ 'fcf7-country-select-lib', 'fcf7-country-dropdown' ];
	}

	public function render_front( $tag, $form_id = 0 ) {
		if ( empty( $tag->name ) ) {
			return '';
		}

		$validation_error = wpcf7_get_validation_error( $tag->name );
		$name             = $tag->name;

		$only_countries  = $tag->get_option( 'only_countries', '', true );
		$default_country = $tag->get_option( 'default_country', '', true );
		$auto_complete   = 'on' === $tag->get_option( 'auto_complete', '', true );
		$dynamic         = 'on' === $tag->get_option( 'dynamic', '', true );
		$value_format    = $tag->get_option( 'value_format', '', true );
		$value_format    = 'code' === $value_format ? 'code' : 'name';

		$only_json = '';
		if ( $only_countries ) {
			$codes     = array_values( array_filter( array_map( 'sanitize_key', preg_split( '/[|,]/', $only_countries ) ) ) );
			$only_json = wp_json_encode( $codes );
		}

		$data_attr  = $only_json ? ' data-only-countries="' . esc_attr( $only_json ) . '"' : '';
		$data_attr .= $default_country ? ' data-default="' . esc_attr( sanitize_key( $default_country ) ) . '"' : '';
		$data_attr .= $auto_complete ? ' data-auto-complete="1"' : '';

		$wrap_class = 'wpcf7-form-control-wrap ' . sanitize_html_class( $name );

		ob_start();

		if ( $dynamic ) {
			?>
			<span class="<?php echo esc_attr( $wrap_class ); ?>" data-name="<?php echo esc_attr( $name ); ?>">
				<span class="fcf7-country-dynamic" data-name="<?php echo esc_attr( $name ); ?>"<?php echo $data_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from already-escaped fragments above; re-escaping breaks the attribute markup. ?>>
					<input type="text" class="fcf7-cd-country-flag" />
					<select class="fcf7-cd-state" disabled></select>
					<input type="text" class="fcf7-cd-city wpcf7-form-control wpcf7-text" placeholder="<?php esc_attr_e( 'City', 'compactform' ); ?>" disabled />
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>" class="fcf7-cd-value" value="" />
				</span>
				<span class="wpcf7-not-valid-tip-wrap"><?php echo $validation_error; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpcf7_get_validation_error() already returns escaped HTML markup; re-escaping breaks the output. ?></span>
			</span>
			<?php
		} else {
			$class = wpcf7_form_controls_class( $tag->type ) . ' fcf7-country-dropdown';
			if ( $validation_error ) {
				$class .= ' wpcf7-not-valid';
			}

			$atts = [
				'type'         => 'text',
				'name'         => $name,
				'id'           => 'fcf7-country-' . sanitize_html_class( $name ),
				'class'        => $tag->get_class_option( $class ),
				'aria-invalid' => $validation_error ? 'true' : 'false',
			];
			if ( $tag->is_required() ) {
				$atts['aria-required'] = 'true';
			}
			if ( $default_country ) {
				$atts['data-default'] = sanitize_key( $default_country );
			}
			if ( $only_json ) {
				$atts['data-only-countries'] = $only_json;
			}
			if ( $auto_complete ) {
				$atts['data-auto-complete'] = '1';
			}
			if ( 'code' === $value_format ) {
				$atts['data-value-format'] = 'code';
			}
			?>
			<span class="<?php echo esc_attr( $wrap_class ); ?>" data-name="<?php echo esc_attr( $name ); ?>">
				<input <?php echo wpcf7_format_atts( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpcf7_format_atts() already escapes attribute values; re-escaping breaks the output. ?> />
				<span class="wpcf7-not-valid-tip-wrap"><?php echo $validation_error; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpcf7_get_validation_error() already returns escaped HTML markup; re-escaping breaks the output. ?></span>
			</span>
			<?php
		}

		return ob_get_clean();
	}


	public function validate_req_data( $schema, $tag, array $form_schema = array(), $contact_form = null ) {
		if ( ! $tag->is_required() ) {
			return;
		}

		$schema->add_rule(
			wpcf7_swv_create_rule( 'required', [
				'field' => $tag->name,
				'error' => wpcf7_get_message( 'invalid_required' ),
			] )
		);
	}
}
