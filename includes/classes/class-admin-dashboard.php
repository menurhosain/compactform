<?php

namespace CompactForm\Classes;

use CompactForm\Helpers\Config;
use CompactForm\Helpers\Utils;

defined( 'ABSPATH' ) || die();

class Admin_Dashboard {

	const MENU_SLUG = 'compactform';

	const RECAPTCHA_SETTINGS_OPTION = 'fcf7_recaptcha_settings';

	private string $hook_suffix = '';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_fcf7_save_elements_config', [ $this, 'ajax_save_elements_config' ] );
		add_action( 'wp_ajax_fcf7_save_recaptcha_settings', [ $this, 'ajax_save_recaptcha_settings' ] );
	}

	public function register_menu(): void {
		$this->hook_suffix = add_menu_page(
			__( 'CompactForm', 'compactform' ),
			__( 'CompactForm', 'compactform' ),
			'manage_options',
			self::MENU_SLUG,
			[ $this, 'render_page' ],
			FCF7_URL . 'assets/branding/menu-icon.png',
			58
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( $hook !== $this->hook_suffix ) {
			return;
		}

		$asset_file = FCF7_PATH . 'assets/dashboard/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		wp_enqueue_style(
			'fcf7-dashboard',
			FCF7_URL . 'assets/dashboard/index.css',
			[ 'wp-components' ],
			$asset['version']
		);

		wp_enqueue_script(
			'fcf7-dashboard',
			FCF7_URL . 'assets/dashboard/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations( 'fcf7-dashboard', 'compactform', FCF7_PATH . 'languages' );

		wp_localize_script( 'fcf7-dashboard', 'FCF7Local', $this->get_local_var() );
	}

	private function get_local_var(): array {
		$flat     = Config::get_extensions_flat_map();
		$inactive = Config::get_inactive_extensions();

		$active_count = 0;
		foreach ( array_keys( $flat ) as $slug ) {
			if ( ! in_array( $slug, $inactive, true ) ) {
				$active_count++;
			}
		}

		return [
			'nonce'              => wp_create_nonce( 'fcf7_admin_nonce' ),
			'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
			'version'            => FCF7_VERSION,
			'logoUrl'            => FCF7_URL . 'assets/branding/logo.png',
			'isProActive'        => Utils::is_pro_active(),
			'extensionsMap'      => $this->get_extensions_map(),
			'inactiveExtensions' => $inactive,
			'activeCount'        => $active_count,
			'totalCount'         => count( $flat ),
			'recaptchaSettings'  => $this->public_recaptcha_settings( get_option( self::RECAPTCHA_SETTINGS_OPTION, [] ) ),
			'links'              => [
				'forms'   => admin_url( 'admin.php?page=wpcf7' ),
				'support' => 'https://rstheme.com/support',
				'docs'    => 'https://rstheme.com/docs/compactform',
				'review'  => 'https://wordpress.org/support/plugin/compactform/reviews/#new-post',
				'upgrade' => 'https://rstheme.com/compactform',
			],
		];
	}

	private function get_extensions_map(): array {
		$map = Config::get_extensions_map();

		foreach ( $map as $group_key => &$group ) {
			foreach ( $group['elements'] as $slug => &$element ) {
				$element['docUrl'] = $element['docUrl'] ?? '#';
			}
			unset( $element );
		}
		unset( $group );

		return $map;
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! file_exists( FCF7_PATH . 'assets/dashboard/index.asset.php' ) ) {
			echo '<div class="wrap"><div class="notice notice-warning"><p>';
			echo esc_html__( 'The CompactForm dashboard has not been built yet. Run "npm install && npm run build:dashboard" in the plugin folder.', 'compactform' );
			echo '</p></div></div>';

			return;
		}

		echo '<div id="fcf7-dashboard" class="fcf7-dashboard"></div>';
	}

	public function ajax_save_elements_config(): void {
		check_ajax_referer( 'fcf7_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'compactform' ) ], 403 );
		}

		$raw     = isset( $_POST['config'] ) ? sanitize_text_field( wp_unslash( $_POST['config'] ) ) : '[]';
		$decoded = json_decode( $raw, true );

		if ( ! is_array( $decoded ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid data.', 'compactform' ) ], 400 );
		}

		Config::set_inactive_extensions( $decoded );

		wp_send_json_success( [ 'message' => __( 'Settings saved.', 'compactform' ) ] );
	}

	public function ajax_save_recaptcha_settings(): void {
		check_ajax_referer( 'fcf7_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'compactform' ) ], 403 );
		}

		$existing = get_option( self::RECAPTCHA_SETTINGS_OPTION, [] );
		$existing = is_array( $existing ) ? $existing : [];

		$secret_key = isset( $_POST['secret_key'] ) ? sanitize_text_field( wp_unslash( $_POST['secret_key'] ) ) : '';

		$settings = [
			'site_key'   => isset( $_POST['site_key'] ) ? sanitize_text_field( wp_unslash( $_POST['site_key'] ) ) : '',
			'secret_key' => '' !== $secret_key ? $secret_key : ( $existing['secret_key'] ?? '' ),
		];

		update_option( self::RECAPTCHA_SETTINGS_OPTION, $settings );

		wp_send_json_success( [
			'message'  => __( 'Google reCAPTCHA settings saved.', 'compactform' ),
			'settings' => $this->public_recaptcha_settings( $settings ),
		] );
	}

	private function public_recaptcha_settings( array $settings ): array {
		return [
			'siteKey'      => $settings['site_key'] ?? '',
			'hasSecretKey' => ! empty( $settings['secret_key'] ),
		];
	}

}
