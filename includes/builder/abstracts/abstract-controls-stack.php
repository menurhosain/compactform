<?php

namespace CompactForm\Builder\Abstracts;

use CompactForm\Builder\Controls_Manager;

defined('ABSPATH') || die();

abstract class Controls_Stack
{

	/** Controls collected during a get_config() build. */
	private array $controls = [];

	/** Sections collected during a get_config() build, keyed by id. */
	private array $sections = [];

	/** Popover groups collected during a get_config() build, keyed by id. */
	private array $popovers = [];

	/** Control-tabs groups (Elementor's start_controls_tabs), by id. */
	private array $tab_groups = [];

	/** Individual tabs (start_controls_tab), by id. */
	private array $tab_items = [];

	private string $current_section   = '';
	private string $current_popover   = '';
	private string $current_tab       = 'settings';
	private string $current_tab_group = '';
	private string $current_tab_item  = '';

	/*  Controls — declare your field-specific controls here. */
	protected function register_controls(): void {}

	public function get_controls(): array
	{
		return $this->controls;
	}

	/** Clear the collected stack before a fresh build (see Base_Field::get_config). */
	protected function reset_stack(): void
	{
		$this->controls   = [];
		$this->sections   = [];
		$this->popovers   = [];
		$this->tab_groups = [];
		$this->tab_items  = [];
	}

	/** The collected controls + their groupings, as get_config() exports them. */
	protected function get_stack(): array
	{
		return [
			'sections'   => array_values($this->sections),
			'popovers'   => array_values($this->popovers),
			'tab_groups' => array_values($this->tab_groups),
			'tab_items'  => array_values($this->tab_items),
			'controls'   => $this->controls,
		];
	}

	protected function start_section(string $id, array $args): void
	{
		$tab   = (string) ( $args['tab'] ?? Controls_Manager::TAB_SETTINGS );
		$label = (string) ( $args['label'] ?? '' );
		unset( $args['tab'], $args['label'] ); // promoted to top-level keys below.

		$this->current_section = $id;
		$this->current_tab     = $tab;
		$this->sections[$id]   = array_merge(
			$args,
			[
				'id'    => $id,
				'tab'   => $tab,
				'label' => $label,
			]
		);
	}

	protected function end_section(): void
	{
		$this->current_section = '';
	}

	protected function start_popover(string $id, array $args): void
	{
		$label = (string) ( $args['label'] ?? '' );
		unset( $args['label'] ); // re-added explicitly below.

		// The toggle itself is NOT inside the group it opens.
		$this->current_popover = '';
		$this->add_control($id, array_merge(
			['type' => Controls_Manager::SWITCHER],
			$args,
			[
				'label'          => $label,
				'popover_toggle' => true,
			]
		));

		$this->current_popover = $id;
		$this->popovers[$id] = array_merge(
			$args,
			[
				'id'      => $id,
				'label'   => $label,
				'tab'     => $this->current_tab,
				'section' => $this->current_section,
			]
		);
	}

	protected function end_popover(): void
	{
		$this->current_popover = '';
	}

	protected function start_controls_tabs( string $id, array $args = [] ): void
	{
		$this->current_tab_group = $id;
		$this->current_tab_item  = '';
		$this->tab_groups[ $id ] = array_merge(
			$args,
			[
				'id'      => $id,
				'tab'     => $this->current_tab,
				'section' => $this->current_section,
			]
		);
	}

	protected function end_controls_tabs(): void
	{
		$this->current_tab_group = '';
		$this->current_tab_item  = '';
	}

	protected function start_controls_tab( string $id, array $args ): void
	{
		$label = (string) ( $args['label'] ?? '' );
		unset( $args['label'] );

		$this->current_tab_item = $id;
		$this->tab_items[ $id ] = array_merge(
			$args,
			[
				'id'    => $id,
				'group' => $this->current_tab_group,
				'label' => $label,
			]
		);
	}

	protected function end_controls_tab(): void
	{
		$this->current_tab_item = '';
	}

	protected function add_control(string $key, array $args): void
	{
		$type = (string) ($args['type'] ?? Controls_Manager::TEXT);
		unset($args['type']); // promoted to a top-level key below.

		// JS reorders an object's integer-like keys ('1','2') ahead of other
		// string keys (like '' for a "Default" option), which would scramble a
		// select's option order. Ship an ordered [ { value, label } ] list the
		// panel renders from; `options` stays the map the server sanitiser needs.
		if (isset($args['options']) && is_array($args['options'])) {
			$list = [];
			foreach ($args['options'] as $ov => $ol) {
				$list[] = ['value' => (string) $ov, 'label' => $ol];
			}
			$args['optionList'] = $list;
		}

		$this->controls[] = array_merge(
			[
				'key'     => $key,
				'type'    => $type,
				'tab'       => $this->current_tab,
				'section'   => $this->current_section,
				'popover'   => $this->current_popover,
				'tab_group' => $this->current_tab_group,
				'tab_item'  => $this->current_tab_item,
			],
			$args
		);
	}
}
