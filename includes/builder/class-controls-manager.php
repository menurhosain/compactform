<?php

namespace CompactForm\Builder;
use CompactForm\Builder\Abstracts\Base_Control;

defined( 'ABSPATH' ) || die();

class Controls_Manager {

	const TEXT       = 'text';
	const TEXTAREA   = 'textarea';
	const HTML       = 'html';
	const NUMBER     = 'number';
	const SELECT     = 'select';
	const CHOOSE     = 'choose';
	const SWITCHER   = 'switcher';
	const COLOR      = 'color';
	const TIME       = 'time';
	const DATE       = 'date';
	const DATE_LIST  = 'date_list';
	const TIME_RANGES = 'time_ranges';
	const ICON       = 'icon';
	const OPTIONS    = 'options';
	const DIMENSIONS = 'dimensions';
	const SLIDER     = 'slider';
	const CONDITIONS = 'conditions';
	const TYPOGRAPHY = 'typography';
	const BORDER     = 'border';
	const HEADING    = 'heading';
	const BOX_SHADOW = 'box_shadow';
	const STEPS      = 'steps';
	const TIME_RANGE_PRICES = 'time_range_prices';
	const DATE_PRICES       = 'date_prices';
	const FORMULA           = 'formula';
	const PRICE_MAP         = 'price_map';

	/**
	 * Panel tab slugs, for start_section()'s `tab` — same idea as the control
	 * constants: autocomplete + typo safety. TAB_SETTINGS is our equivalent of
	 * Elementor's content tab.
	 */
	const TAB_SETTINGS = 'settings';
	const TAB_STYLE    = 'style';
	const TAB_ADVANCED = 'advanced';

	private static ?self $instance = null;

	private array $controls = [];

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->register_defaults();
		do_action( 'fcf7_register_controls', $this );
	}

	private function register_defaults(): void {
		foreach ( glob( __DIR__ . '/controls/class-*.php' ) as $file ) {
			$slug  = preg_replace( '/^class-/', '', basename( $file, '.php' ) );
			$class = __NAMESPACE__ . '\\Controls\\' . str_replace( '-', '_', ucwords( $slug, '-' ) );

			if ( ! class_exists( $class ) ) {
				continue;
			}
			$reflection = new \ReflectionClass( $class );
			if ( $reflection->isAbstract() || ! $reflection->isSubclassOf( Base_Control::class ) ) {
				continue;
			}

			$this->register( new $class() );
		}
	}

	public function register( Base_Control $control ): void {
		$this->controls[ $control->get_type() ] = $control;
	}

	public function get( string $type ): ?Base_Control {
		return $this->controls[ $type ] ?? null;
	}

	public function has( string $type ): bool {
		return isset( $this->controls[ $type ] );
	}

	public function sanitize( string $type, $value, array $args = [] ) {
		$control = $this->get( $type );
		if ( $control ) {
			return $control->sanitize( $value, $args );
		}
		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}

	public function get_default( string $type, array $args = [] ) {
		if ( array_key_exists( 'default', $args ) ) {
			return $args['default'];
		}
		$control = $this->get( $type );
		return $control ? $control->get_default() : '';
	}
}
