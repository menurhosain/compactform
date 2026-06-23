<?php
namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Choices_Control {

	protected function register_choices_control( bool $with_first_option = false ): void {
		$this->start_section(
			'choices',
			[
				'label' => __( 'Options', 'compactform' ),
				'tab'   => Controls_Manager::TAB_SETTINGS,
			]
		);

		$this->add_control(
			'options',
			[
				'label'    => esc_html__( 'Options', 'compactform' ),
				'type'     => Controls_Manager::SELECT,
				'multiple' => true,
				'create'   => true,
			]
		);

		if ( $with_first_option ) {
			// Both modes describe the row a single-select shows before a choice is
			// made, so neither means anything once `multiple` is on — every option
			// is visible at once. Only Select_Field passes $with_first_option, and
			// it is the only field with a `multiple` control, so the condition is
			// inert everywhere else.
			$this->add_control(
				'first_option',
				[
					'type'      => Controls_Manager::SELECT,
					'label'     => __( 'Default option', 'compactform' ),
					'options'   => [
						''      => __( 'None', 'compactform' ),
						'blank' => __( 'Empty', 'compactform' ),
						'label' => __( 'Placeholder text', 'compactform' ),
					],
					'default'   => '',
					'condition' => [ 'multiple' => '' ],
				]
			);
			$this->add_control(
				'first_option_text',
				[
					'type'        => Controls_Manager::TEXT,
					'label'       => __( 'Placeholder', 'compactform' ),
					'placeholder' => __( 'e.g. Choose…', 'compactform' ),
					'condition'   => [ 'first_option' => 'label', 'multiple' => '' ],
				]
			);
		} else {
			$this->add_control(
				'default_option',
				[
					'type'        => Controls_Manager::SELECT,
					'label'       => __( 'Default Option', 'compactform' ),
					'label_block' => true,
					'get_option'  => 'options',
					'multiple'    => 'checkbox' === $this->get_tag(),
					'default'     => 'checkbox' === $this->get_tag() ? [] : '',
				]
			);
		}

		$this->end_section();
	}
}
