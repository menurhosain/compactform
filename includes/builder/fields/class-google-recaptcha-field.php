<?php

namespace CompactForm\Builder\Fields;

use CompactForm\Builder\Controls_Manager;

use CompactForm\Builder\Abstracts\Extension_Field;
use CompactForm\Builder\Abstracts\Traits\Name_Control;
use CompactForm\Builder\Abstracts\Traits\Required_Control;

defined( 'ABSPATH' ) || die();

class Google_Recaptcha_Field extends Extension_Field {

	use Name_Control;
	use Required_Control;

	public function get_type(): string { return 'fcf7_google_recaptcha'; }
	public function get_title(): string { return __( 'Google reCAPTCHA', 'compactform' ); }
	public function get_icon(): string { return 'ri-shield-check-line'; }
	public function get_category(): string { return 'security'; }
	protected function get_tag(): string { return 'fcf7_google_recaptcha'; }
	public function get_preview(): string { return 'google_recaptcha'; }

	/** v3 score cutoff — Google's own recommended default. */
	private const SCORE_THRESHOLD = 0.5;

	private const SETTINGS_OPTION = 'fcf7_recaptcha_settings';

	private function get_settings(): array {
		$defaults = [ 'site_key'   => '', 'secret_key' => '' ];

		$saved = get_option( self::SETTINGS_OPTION, [] );

		return array_merge( $defaults, is_array( $saved ) ? $saved : [] );
	}

	private function get_site_key(): string {
		return (string) $this->get_settings()['site_key'];
	}

	// Secret key only — never expose this in render_front()'s markup or JS.
	private function get_secret_key(): string {
		return (string) $this->get_settings()['secret_key'];
	}

	private function get_action_name( string $tag_name ): string {
		return preg_replace( '/[^A-Za-z_\/]/', '_', 'fcf7_' . $tag_name );
	}

	protected function tag_values( array $data ): array {
		return [ (string) ( $data['error_message'] ?? '' ) ];
	}

	private function get_error_message( $tag ): string {
		$custom = trim( (string) ( $tag->values[0] ?? '' ) );

		return '' !== $custom ? $custom : __( 'reCAPTCHA verification failed. Please try again.', 'compactform' );
	}

	public function __construct() {
		parent::__construct();

		add_filter( 'wpcf7_validate_' . $this->get_tag(), [ $this, 'validate_recaptcha_response' ], 10, 2 );
		add_filter( 'wpcf7_validate_' . $this->get_tag() . '*', [ $this, 'validate_recaptcha_response' ], 10, 2 );
	}


	protected function register_script(): array {
		$site_key = $this->get_site_key();

		// Google-hosted script, not a file this plugin ships — Google controls its own caching
		// there, so a version query string is meaningless (and not ours to add).
		wp_register_script( 'fcf7-google-recaptcha-v3-api', 'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( $site_key ), [], null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_register_script( 'fcf7-google-recaptcha', FCF7_ASSETS . 'js/google-recaptcha.min.js', [ 'fcf7-google-recaptcha-v3-api' ], FCF7_VERSION, true );

		return [ 'fcf7-google-recaptcha-v3-api', 'fcf7-google-recaptcha' ];
	}

	public function render_front( $tag, $form_id = 0 ) {
		if ( empty( $tag->name ) ) {
			return '';
		}

		$site_key = $this->get_site_key();

		return sprintf(
			'<span class="wpcf7-form-control-wrap %1$s" data-name="%1$s">
				<input type="hidden" name="g-recaptcha-response" class="fcf7-recaptcha-token" data-sitekey="%2$s" data-action="%3$s" value="" />
			</span>',
			esc_attr( $tag->name ),
			esc_attr( $site_key ),
			esc_attr( $this->get_action_name( $tag->name ) )
		);
	}

	public function render_builder( array $data ): string {
		return 'hello';
	}

	public function validate_recaptcha_response( $result, $tag ) {
		$error_message = $this->get_error_message( $tag );

		// This fires on CF7's wpcf7_validate_{tag} filter, dispatched only after CF7 core has
		// already verified the submission's nonce; no separate nonce check belongs here.
		$token = isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( '' === $token ) {
			$result->invalidate( $tag, $error_message );
			return $result;
		}

		$response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
			'body' => [
				'secret'   => $this->get_secret_key(),
				'response' => $token,
				'remoteip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			],
		] );

		if ( is_wp_error( $response ) ) {
			$result->invalidate( $tag, $error_message );
			return $result;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['success'] ) ) {
			$result->invalidate( $tag, $error_message );
			return $result;
		}

		$expected_action = $this->get_action_name( $tag->name );
		if ( isset( $body['action'] ) && $body['action'] !== $expected_action ) {
			$result->invalidate( $tag, $error_message );
			return $result;
		}

		if ( isset( $body['score'] ) && $body['score'] < self::SCORE_THRESHOLD ) {
			$result->invalidate( $tag, $error_message );
		}

		return $result;
	}

	protected function register_controls(): void {
		$this->start_section( 'general', [
			'label' => __( 'General', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
		$this->register_name_control();
		$this->register_required_control();
		$this->end_section();

		$this->start_section( 'recaptcha', [
			'label' => __( 'Google reCAPTCHA', 'compactform' ),
			'tab'   => Controls_Manager::TAB_SETTINGS,
		] );
		$this->add_control( 'error_message', [
			'type'        => Controls_Manager::TEXTAREA,
			'label'       => __( 'Error Message', 'compactform' ),
			'description' => __( 'Shown to the visitor if reCAPTCHA verification fails.', 'compactform' ),
		] );
		$this->end_section();
	}

	protected function option_map(): array {
		return [];
	}
}
