<?php

namespace CompactForm\Builder\Fields;

use CompactForm\Builder\Controls_Manager;

use CompactForm\Builder\Abstracts\Base_Field;
use CompactForm\Builder\Abstracts\Traits\Label_Control;
use CompactForm\Builder\Abstracts\Traits\Name_Control;
use CompactForm\Builder\Abstracts\Traits\Description_Control;
use CompactForm\Builder\Abstracts\Traits\Width_Control;
use CompactForm\Builder\Abstracts\Traits\Flex_Item_Control;
use CompactForm\Builder\Abstracts\Traits\Label_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Field_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Description_Style_Control;
use CompactForm\Builder\Abstracts\Traits\Advanced_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Control;
use CompactForm\Builder\Field_Helpers;

defined( 'ABSPATH' ) || die();

class Quiz_Field extends Base_Field {

	use Label_Control;
	use Name_Control;
	use Description_Control;
	use Width_Control;
	use Flex_Item_Control;
	use Label_Style_Control;
	use Field_Style_Control;
	use Description_Style_Control;
	use Advanced_Control;
	use Conditional_Control;

	public function get_type(): string {
		return 'quiz';
	}

	public function get_title(): string {
		return __( 'Quiz', 'compactform' );
	}

	public function get_icon(): string {
		return 'ri-question-line';
	}

	public function get_category(): string {
		return 'basic';
	}

	public function get_preview(): string {
		return 'quiz';
	}

	protected function register_controls(): void {
		$this->start_section( 'general', [
			'label' => __( 'General', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
		$this->register_label_control();
		$this->register_name_control();
		$this->register_description_control();
		$this->register_width_control();
		$this->register_flex_item_controls();
		$this->end_section();

		$this->start_section( 'quiz', [
			'label' => __( 'Quiz', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );

		$this->add_control( 'options', [
			'type' => Controls_Manager::OPTIONS,
			'label'   => __( 'Question | Answer', 'compactform' ),
			'description'    => __( 'One pair per line, e.g. "1+1=? | 2". CF7 asks one at random.', 'compactform' ),
			'default' => [
				'1 + 1 = ? | 2',
				'The capital of Japan? | Tokyo'
			]
		] );

		$this->end_section();

		$this->register_label_style_controls();
		$this->register_field_style_controls( false, true );
		$this->register_description_style_controls();

		$question = '{{WRAPPER}} .wpcf7-quiz-label';

		$this->start_section( 'quiz_question', [
			'label' => __( 'Question', 'compactform' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'questionTypography', [
			'type' => Controls_Manager::TYPOGRAPHY,
			'label'      => __( 'Typography', 'compactform' ),
			'selector'   => $question,
			'responsive' => true
		] );

		$this->add_control( 'questionColor', [
			'type' => Controls_Manager::COLOR,
			'label'     => __( 'Color', 'compactform' ),
			'selectors' => [ $question => 'color: {{VALUE}};' ]
		] );
		$this->add_control( 'question_margin', [
			'type' => Controls_Manager::DIMENSIONS,
			'label'      => __( 'Margin', 'compactform' ),
			'responsive' => true,
			'selectors'  => [ $question => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ]
		] );

		$this->end_section();

		$this->register_advanced_controls();
		$this->register_conditional_controls();
	}

	public function compile( array $data ): string {
		$name   = Field_Helpers::name( $data );
		$markup = '[quiz ' . $name . Field_Helpers::atts( $data, false );

		foreach ( Field_Helpers::options( $data ) as $pair ) {
			if ( false === strpos( $pair, '|' ) ) {
				continue; // Not a question|answer pair — skip rather than emit a broken quiz.
			}
			list( $question, $answer ) = array_map( 'trim', explode( '|', $pair, 2 ) );
			if ( '' === $question || '' === $answer ) {
				continue;
			}
			$markup .= ' "' . Field_Helpers::q( $question ) . '|' . Field_Helpers::q( $answer ) . '"';
		}

		$markup .= ']';

		return Field_Helpers::label_wrap( $data, $markup );
	}
}
