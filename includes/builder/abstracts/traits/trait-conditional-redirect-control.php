<?php
/** "Conditional Redirect" section (Advanced tab). Self-contained — Submit_Field only. */

namespace CompactForm\Builder\Abstracts\Traits;

use CompactForm\Builder\Controls_Manager;

defined( 'ABSPATH' ) || die();

trait Conditional_Redirect_Control {

	protected function register_conditional_redirect_controls(): void {
		$this->start_section(
			'redirect',
			[
				'label' => __( 'Conditional Redirect', 'compactform' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);
		$this->add_control(
			'is_redirect_enable',
			[
				'type'  => Controls_Manager::SWITCHER,
				'label' => __( 'Enable', 'compactform' ),
			]
		);
		$this->add_control(
			'redirect_page_location',
			[
				'type'      => Controls_Manager::SELECT,
				'label'     => __( 'Redirect page location', 'compactform' ),
				'options'   => [
					'internal' => __( 'Internal Page', 'compactform' ),
					'external' => __( 'External URL', 'compactform' ),
				],
				'default'   => 'internal',
				'condition' => [ 'is_redirect_enable' => true, 'is_custom_redirect_condition_enable' => false ],
			]
		);
		$this->add_control(
			'redirect_page_internal',
			[
				'type'      => Controls_Manager::SELECT,
				'label'     => __( 'Select redirect page', 'compactform' ),
				'options'   => self::get_page_options(),
				'condition' => [ 'is_redirect_enable' => true, 'redirect_page_location' => 'internal', 'is_custom_redirect_condition_enable' => false ],
			]
		);
		$this->add_control(
			'redirect_page_external',
			[
				'type'      => Controls_Manager::TEXT,
				'label'     => __( 'Redirect page URL', 'compactform' ),
				'condition' => [ 'is_redirect_enable' => true, 'redirect_page_location' => 'external', 'is_custom_redirect_condition_enable' => false ],
			]
		);

		do_action( 'fcf7_submit_redirect_custom_condition_controls', $this );

		$this->end_section();
	}

	public function add_redirect_control( string $key, array $args ): void {
		$this->add_control( $key, $args );
	}

	public static function get_page_options(): array {
		$options = [ '' => __( 'Default', 'compactform' ) ];

		$pages = get_pages( [ 'sort_column' => 'post_title', 'sort_order' => 'ASC' ] );

		foreach ( $pages as $page ) {
			$options[ $page->post_name ] = $page->post_title;
		}

		return $options;
	}
}
