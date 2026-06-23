<?php

namespace CompactForm\Classes;

use CompactForm\Helpers\Config;

defined( 'ABSPATH' ) || die();

final class CF7_Init {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_vendor_assets' ], 5 );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_vendor_assets' ], 5 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_theme_css' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_theme_css' ] );
		add_action( 'init', [ $this, 'load_extensions' ], 0 );
		// Catch-all for styles enqueued too late for wp_head (a page builder rendering a form
		// at wp_footer priority). WP_Styles::do_items() skips anything already printed, so this
		// is a no-op on every normal page and only flushes what a late renderer queued.
		add_action( 'wp_footer', 'wp_print_styles', 999 );
	}

	public function load_extensions(): void {
		require_once FCF7_INCLUDES . '/extensions/class-form-builder.php';
		new \CompactForm\Extensions\Form_Builder();

		foreach ( Config::get_extensions_flat_map() as $slug => $data ) {
			if ( ! Config::is_extension_active( $slug ) ) {
				continue;
			}

			$class = $this->load_component( $slug, ! empty( $data['is_pro'] ) );

			if ( $class && class_exists( $class ) ) {
				new $class();
			}
		}
	}

	private function load_component( string $slug, bool $is_pro = false ): ?string {
		$file = FCF7_INCLUDES . '/extensions/class-' . $slug . '.php';

		if ( ! file_exists( $file ) ) {
			return null;
		}

		include_once $file;

		return '\\CompactForm\\Extensions\\' . $this->slug_to_class_name( $slug );
	}

	private function slug_to_class_name( string $slug ): string {
		return str_replace( ' ', '_', ucwords( str_replace( [ '-', '_' ], ' ', $slug ) ) );
	}

	public function register_vendor_assets(): void {
		foreach ( Config::get_vendor_assets() as $key => $config ) {
			$handle = $config['handle'] ?? $key;
			$url    = FCF7_ASSETS . 'vendor/' . $config['path'];

			if ( 'style' === $config['type'] ) {
				wp_register_style( $handle, $url, $config['deps'] ?? [], $config['version'] );
			} else {
				wp_register_script( $handle, $url, $config['deps'] ?? [], $config['version'], $config['in_footer'] ?? true );
			}
		}
	}

	public function enqueue_theme_css(): void {
		wp_enqueue_style( 'fcf7-theme', FCF7_ASSETS . 'css/fcf7-theme.min.css', [], FCF7_VERSION );
	}
}
