<?php
/**
 * Core "Raw HTML" field: an escape hatch for markup the builder has no control
 * for — a notice box, a legal blurb, an <a>, a table, an embed.
 *
 * Nothing about it is a form control: no name, no value, no CF7 tag, nothing in
 * the mail. It is a layout field in the same sense Heading and Divider are.
 *
 * It is also what `Builder_Parser` imports an unrecognised CF7 tag into — that
 * block always claimed type `html`, and until this class existed there was no
 * field registered for it, so Form_Compiler dropped it. Anything stored here is
 * emitted verbatim into the form template, so a `[tag]` inside it is still a
 * live CF7 tag; shortcodes are not, because CF7 does not run do_shortcode() on
 * a form.
 *
 * The markup is trusted-by-capability, not trusted-by-storage: Html_Control
 * decides at SAVE time whether the author may keep script/iframe/style or gets
 * wp_kses_post(), exactly as WordPress decides for post content. compile() must
 * therefore emit the stored string untouched — re-filtering here would silently
 * strip what a super admin deliberately saved.
 */

namespace CompactForm\Builder\Fields;

use CompactForm\Builder\Controls_Manager;

use CompactForm\Builder\Abstracts\Base_Field;
use CompactForm\Builder\Abstracts\Traits\Width_Control;
use CompactForm\Builder\Abstracts\Traits\Flex_Item_Control;
use CompactForm\Builder\Abstracts\Traits\Advanced_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Control;

defined( 'ABSPATH' ) || die();

class Html_Field extends Base_Field {

	use Width_Control;
	use Flex_Item_Control;
	use Advanced_Control;
	use Conditional_Control;

	public function get_type(): string {
		return 'html';
	}

	public function get_title(): string {
		return __( 'Raw HTML', 'compactform' );
	}

	public function get_icon(): string {
		return 'ri-code-s-slash-line';
	}

	public function get_category(): string {
		return 'layout';
	}

	public function get_preview(): string {
		return 'html';
	}

	protected function register_controls(): void {
		$this->start_section( 'general', [
			'label' => __( 'Raw HTML', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
			$this->add_control( 'html', [
				'type'        => Controls_Manager::HTML,
				'label'       => __( 'HTML', 'compactform' ),
				'label_block' => true,
				'placeholder' => '<p class="note">…</p>',
			] );
			$this->add_control( 'width', self::width_control_args() );
			$this->register_flex_item_controls();
		$this->end_section();

		$this->start_section( 'style', [
			'label' => __( 'Style', 'compactform' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
			$this->add_control( 'typography', [
				'type'       => Controls_Manager::TYPOGRAPHY,
				'label'      => __( 'Typography', 'compactform' ),
				'selector'   => '{{WRAPPER}} .fcf7b-html',
				'responsive' => true,
			] );
			$this->add_control( 'text_color', [
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Text Color', 'compactform' ),
				'selectors' => [ '{{WRAPPER}} .fcf7b-html' => 'color: {{VALUE}};' ],
			] );
			$this->add_control( 'link_color', [
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Link Color', 'compactform' ),
				'selectors' => [ '{{WRAPPER}} .fcf7b-html a' => 'color: {{VALUE}};' ],
			] );
			$this->add_control( 'link_color_hover', [
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Link Color (Hover)', 'compactform' ),
				'selectors' => [ '{{WRAPPER}} .fcf7b-html a:hover' => 'color: {{VALUE}};' ],
			] );
			$this->add_control( 'background', [
				'type'      => Controls_Manager::COLOR,
				'label'     => __( 'Background', 'compactform' ),
				'selectors' => [ '{{WRAPPER}} .fcf7b-html' => 'background-color: {{VALUE}};' ],
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
				'selectors'  => [ '{{WRAPPER}} .fcf7b-html' => 'text-align: {{VALUE}};' ],
			] );
			$this->add_control( 'border', [
				'type'     => Controls_Manager::BORDER,
				'label'    => __( 'Border', 'compactform' ),
				'selector' => '{{WRAPPER}} .fcf7b-html',
			] );
			$this->add_control( 'padding', [
				'type'       => Controls_Manager::DIMENSIONS,
				'label'      => __( 'Padding', 'compactform' ),
				'selectors'  => [
					'{{WRAPPER}} .fcf7b-html' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'responsive' => true,
			] );
		$this->end_section();

		$this->register_advanced_controls();
		$this->register_conditional_controls();
	}

	public function compile( array $data ): string {
		$html = (string) ( $data['html'] ?? '' );
		if ( '' === trim( $html ) ) {
			return '';
		}

		$cls = 'fcf7b-html';
		if ( ! empty( $data['cssClass'] ) ) {
			$cls .= ' ' . preg_replace( '/[^A-Za-z0-9_\- ]/', '', trim( (string) $data['cssClass'] ) );
		}

		return '<div class="' . esc_attr( trim( $cls ) ) . '">' . $html . '</div>';
	}
}
