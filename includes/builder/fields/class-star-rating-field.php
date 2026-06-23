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

class Star_Rating_Field extends Extension_Field {

	use Label_Control;
	use Name_Control;
	use Required_Control;
	use Width_Control;
	use Flex_Item_Control;
	use Label_Style_Control;
	use Field_Style_Control;
	use Advanced_Control;
	use Conditional_Control;

	public function get_type(): string { return 'fcf7_star_rating'; }
	public function get_title(): string { return __( 'Star Rating', 'compactform' ); }
	public function get_icon(): string { return 'ri-star-line'; }
	protected function get_tag(): string { return 'fcf7_star_rating'; }
	public function get_preview(): string { return 'stars'; }

	protected function register_style(): array {
		wp_register_style( 'fcf7-star-rating', FCF7_ASSETS . 'css/star-rating.min.css', [], FCF7_VERSION );
		return [ 'fcf7-star-rating' ];
	}

	protected function register_script(): array {
		wp_register_script( 'fcf7-star-rating', FCF7_ASSETS . 'js/star-rating.min.js', [], FCF7_VERSION, true );
		return [ 'fcf7-star-rating' ];
	}

	public function render_front( $tag, $form_id = 0 ) {
		ob_start();
		$validation_error = wpcf7_get_validation_error( $tag->name );

		$class = wpcf7_form_controls_class( $tag->type );
		$class .= ' fcf7-rating';
		if ( $validation_error ) {
			$class .= ' wpcf7-not-valid';
		}

		$atts = [];
		$atts['class'] = $class;

		if ( $tag->is_required() ) {
			$atts['aria-required'] = 'true';
		}
		if ( $validation_error ) {
			$atts['aria-describedby'] = wpcf7_get_validation_error_reference( $tag->name );
		}

		$atts['aria-invalid'] = $validation_error ? 'true' : 'false';
		$atts = wpcf7_format_atts( $atts );

		$star_values    = $this->get_star_values( $tag );
		$raw_selected   = $tag->get_option( 'selected', '', true );
		$selected_index = ( '' === $raw_selected ) ? 5 : (int) $raw_selected;
		$selected_index = min( max( $selected_index, 0 ), 5 );
		$selected       = $selected_index > 0 ? $star_values[ $selected_index ] : '';

		?>

		<span data-name="<?php echo esc_attr( $tag->name ); ?>" class="wpcf7-form-control-wrap <?php echo esc_attr( $tag->name ); ?>">
			<span <?php echo $atts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpcf7_format_atts() already escapes attribute values; re-escaping breaks the output. ?>>
				<label>
					<input type="text" class="fcf7-rating-input" name="<?php echo esc_attr( $tag->name ); ?>" value="<?php echo esc_attr( $selected ); ?>"/>
					<span class="icon">
					   <?php for ( $i = 1; $i <= 5; $i++ ) { ?>
                           <span class="fcf7-star" data-value="<?php echo esc_attr( $star_values[ $i ] ); ?>" role="button" tabindex="0" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: star rating value. */ __( 'Rate: %s', 'compactform' ), $star_values[ $i ] ) ); ?>">
                               <svg viewBox="0 0 640 640" fill="currentColor"><path d="M341.5 45.1C337.4 37.1 329.1 32 320.1 32C311.1 32 302.8 37.1 298.7 45.1L225.1 189.3L65.2 214.7C56.3 216.1 48.9 222.4 46.1 231C43.3 239.6 45.6 249 51.9 255.4L166.3 369.9L141.1 529.8C139.7 538.7 143.4 547.7 150.7 553C158 558.3 167.6 559.1 175.7 555L320.1 481.6L464.4 555C472.4 559.1 482.1 558.3 489.4 553C496.7 547.7 500.4 538.8 499 529.8L473.7 369.9L588.1 255.4C594.5 249 596.7 239.6 593.9 231C591.1 222.4 583.8 216.1 574.8 214.7L415 189.3L341.5 45.1z"/></svg>
                           </span>
					   <?php } ?>
                    </span>
				</label>
			</span>
		</span>
		<span><?php echo $validation_error; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpcf7_get_validation_error() already returns escaped HTML markup; re-escaping breaks the output. ?></span>
		<?php

		$output = ob_get_clean();
		return apply_filters( 'fcf7_star_rating_output', $output, $tag );
	}

	public function render_builder( array $data ): string {
		$selected = (int) ( $data['selected'] ?? 5 );
		ob_start();
		?>
		<div class="fcf7-rating">
			<span class="icon">
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
					<span class="fcf7-star<?php echo $i <= $selected ? ' is-active' : ''; ?>">
						<svg viewBox="0 0 640 640" fill="currentColor"><path d="M341.5 45.1C337.4 37.1 329.1 32 320.1 32C311.1 32 302.8 37.1 298.7 45.1L225.1 189.3L65.2 214.7C56.3 216.1 48.9 222.4 46.1 231C43.3 239.6 45.6 249 51.9 255.4L166.3 369.9L141.1 529.8C139.7 538.7 143.4 547.7 150.7 553C158 558.3 167.6 559.1 175.7 555L320.1 481.6L464.4 555C472.4 559.1 482.1 558.3 489.4 553C496.7 547.7 500.4 538.8 499 529.8L473.7 369.9L588.1 255.4C594.5 249 596.7 239.6 593.9 231C591.1 222.4 583.8 216.1 574.8 214.7L415 189.3L341.5 45.1z"/></svg>
					</span>
				<?php endfor; ?>
			</span>
		</div>
		<?php
		return trim( (string) ob_get_clean() );
	}

	public function validate_req_data( $schema, $tag, array $form_schema = array(), $contact_form = null ) {
		if ( $tag->is_required() ) {
			$schema->add_rule(
				wpcf7_swv_create_rule( 'required', [
					'field' => $tag->name,
					'error' => wpcf7_get_message( 'invalid_required' ),
				] )
			);
		}

		$schema->add_rule(
			wpcf7_swv_create_rule( 'enum', [
				'field'  => $tag->name,
				'accept' => array_values( $this->get_star_values( $tag ) ),
				'error'  => __( 'Undefined value was submitted through this field.', 'compactform' ),
			] )
		);
	}

	private function get_star_values( $tag ) {
		$star_values = [];
		for ( $i = 1; $i <= 5; $i++ ) {
			$star_values[ $i ] = ( $tag->values[ $i - 1 ] ?? '' ) !== '' ? $tag->values[ $i - 1 ] : "{$i}";
		}
		return $star_values;
	}

	protected function tag_values( array $data ): array {
		$values = [];
		for ( $i = 1; $i <= 5; $i++ ) {
			$values[] = ( $data["star{$i}"] ?? '' ) !== '' ? $data["star{$i}"] : (string) $i;
		}
		return $values;
	}

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

		$this->start_section( 'rating', [
			'label' => __( 'Rating', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
		$this->add_control( 'selected', [
			'type' => Controls_Manager::NUMBER,
			'label' => __( 'Default rating', 'compactform' ),
			'hint' => __( '1–5', 'compactform' ),
			'default' => '5'
		] );
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->add_control( "star{$i}", [
				'type' => Controls_Manager::TEXT,
				/* translators: %d: star position number. */
				'label' => sprintf( __( 'Star %d value', 'compactform' ), $i )
			] );
		}

		$this->add_control( 'fillColor', [
			'type' => Controls_Manager::COLOR,
			'label'     => __( 'Star Fill Color', 'compactform' ),
			'selectors' => [ '{{WRAPPER}} .fcf7-star.is-active' => 'color: {{VALUE}};' ]
		] );
		$this->add_control( 'size', [
			'type' => Controls_Manager::SLIDER,
			'label'      => __( 'Star Size', 'compactform' ),
			'min'        => 0,
			'max'        => 100,
			'step'       => 1,
			'units'      => [ 'px', 'em', 'rem', 'custom' ],
			'responsive' => true,
			'selectors'  => [ '{{WRAPPER}} .fcf7-star svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ]
		] );
		$this->end_section();

		$this->register_label_style_controls();
		$this->register_field_style_controls( false, true );
		$this->register_advanced_controls();
		$this->register_conditional_controls();
	}

	protected function option_map(): array {
		return [
			[ 'prop' => 'selected', 'opt' => 'selected' ],
		];
	}
}
