<?php

namespace CompactForm\Extensions;

if (! defined('ABSPATH')) {
	exit;
}

class Webhook {

	private const SPECIAL_MAIL_TAGS = [
		'remote_ip', 'user_agent', 'url', 'date', 'time',
		'serial_number', 'post_id', 'post_name', 'post_title', 'post_url',
		'post_author', 'post_author_email', 'site_title', 'site_description',
		'site_url', 'site_admin_email', 'user_login', 'user_email', 'user_url',
		'user_first_name', 'user_last_name', 'user_nickname', 'user_display_name',
	];

	public const META_PREFIX = '_meta:';

	/** Methods offered in the panel; anything else is refused by the sanitiser. */
	public const METHODS = [ 'POST', 'GET', 'PUT', 'PATCH', 'DELETE' ];

	/** Methods that carry the payload in the query string instead of a body. */
	private const QUERY_METHODS = [ 'GET', 'DELETE' ];

	public const FORMATS = [ 'json', 'form' ];

	/** How long to wait for the endpoint, in seconds (sanitiser clamps to this). */
	public const TIMEOUT_MIN = 1;
	public const TIMEOUT_MAX = 30;
	public const TIMEOUT_DEFAULT = 10;

	/** Upper bound on header / body rows, so one form's meta cannot grow forever. */
	public const MAX_ROWS = 50;

	private const VALUE_SEPARATOR = ', ';

	public function __construct() {
		add_action('wpcf7_before_send_mail', [ $this, 'maybe_send' ], 10, 3);
	}

	public function get_form_settings( int $form_id ): array {
		$decoded = json_decode( (string) get_post_meta( $form_id, '_fcf7_builder_schema', true ), true );
		$wh      = is_array( $decoded ) ? ( $decoded['integrations']['webhook'] ?? [] ) : [];

		if ( ! is_array( $wh ) ) {
			$wh = [];
		}

		$method  = strtoupper( (string) ( $wh['method'] ?? 'POST' ) );
		$format  = (string) ( $wh['format'] ?? 'json' );
		$timeout = isset( $wh['timeout'] ) ? (int) $wh['timeout'] : self::TIMEOUT_DEFAULT;

		return [
			'enabled'  => ! empty( $wh['enabled'] ),
			'url'      => isset( $wh['url'] ) ? trim( (string) $wh['url'] ) : '',
			'method'   => in_array( $method, self::METHODS, true ) ? $method : 'POST',
			'format'   => in_array( $format, self::FORMATS, true ) ? $format : 'json',
			'bodyMode' => 'mapped' === ( $wh['bodyMode'] ?? 'all' ) ? 'mapped' : 'all',
			'body'     => $this->body_rows( $wh['body'] ?? [] ),
			'headers'  => $this->rows( $wh['headers'] ?? [], [ 'name', 'mode', 'value' ] ),
			'meta'     => ! empty( $wh['meta'] ),
			'timeout'  => max( self::TIMEOUT_MIN, min( self::TIMEOUT_MAX, $timeout ) ),
			'blocking' => ! empty( $wh['blocking'] ),
		];
	}

	private function body_rows( $raw ): array {
		$out = [];

		foreach ( (array) $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$sources = [];
			foreach ( (array) ( $row['source'] ?? [] ) as $source ) {
				if ( is_scalar( $source ) && '' !== trim( (string) $source ) ) {
					$sources[] = trim( (string) $source );
				}
			}

			$out[] = [
				'key'     => isset( $row['key'] ) && is_scalar( $row['key'] ) ? trim( (string) $row['key'] ) : '',
				'sources' => $sources,
			];
		}

		return array_slice( $out, 0, self::MAX_ROWS );
	}

	/** Normalise a stored repeater into a list of string-keyed rows. */
	private function rows( $raw, array $keys ): array {
		$out = [];

		foreach ( (array) $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean = [];
			foreach ( $keys as $key ) {
				$clean[ $key ] = isset( $row[ $key ] ) && is_scalar( $row[ $key ] ) ? trim( (string) $row[ $key ] ) : '';
			}
			$out[] = $clean;
		}

		return array_slice( $out, 0, self::MAX_ROWS );
	}

	/**
	 * @param \WPCF7_ContactForm $contact_form
	 * @param bool                $abort
	 * @param \WPCF7_Submission   $submission
	 */
	public function maybe_send( $contact_form, &$abort, $submission ): void {
		if ( $abort ) {
			return;
		}

		$form_id  = (int) $contact_form->id();
		$settings = $this->get_form_settings( $form_id );

		if ( ! $settings['enabled'] || '' === $settings['url'] ) {
			return;
		}

		$posted = (array) $submission->get_posted_data();
		$meta   = $this->special_mail_tags();

		$payload = $this->build_payload(
			$settings,
			$posted,
			$meta,
			$this->skip_fields( $contact_form ),
			$form_id
		);

		/**
		 * The flat key => string payload, just before it is encoded.
		 *
		 * @param array               $payload
		 * @param \WPCF7_ContactForm  $contact_form
		 * @param \WPCF7_Submission   $submission
		 */
		$payload = (array) apply_filters( 'fcf7_webhook_payload', $payload, $contact_form, $submission );

		$url  = $settings['url'];
		$args = [
			'method'   => $settings['method'],
			'timeout'  => $settings['timeout'],
			'blocking' => $settings['blocking'],
			'headers'  => $this->build_headers( $settings, $posted, $meta ),
		];

		if ( in_array( $settings['method'], self::QUERY_METHODS, true ) ) {
			$query = [];
			foreach ( $payload as $key => $value ) {
				$query[ rawurlencode( (string) $key ) ] = rawurlencode( (string) $value );
			}

			$url = add_query_arg( $query, $url );
			unset( $args['headers']['Content-Type'] );
		} else {
			$args['body'] = 'json' === $settings['format']
				? (string) wp_json_encode( $payload )
				: http_build_query( $payload );
		}

		/**
		 * The full `wp_remote_request()` argument array.
		 *
		 * @param array               $args
		 * @param string              $url
		 * @param \WPCF7_ContactForm  $contact_form
		 */
		$args = (array) apply_filters( 'fcf7_webhook_request_args', $args, $url, $contact_form );
		
		$allow_unsafe = (bool) apply_filters( 'fcf7_webhook_allow_unsafe_url', false, $url, $contact_form );

		$response = $allow_unsafe
			? wp_remote_request( $url, $args )
			: wp_safe_remote_request( $url, $args );

		if ( ! $settings['blocking'] ) {
			return;
		}

		if ( is_wp_error( $response ) ) {
			// Intentional failure log, not leftover debug code — the only record of a webhook that never reached its target.
			error_log( sprintf( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				'CompactForm – Webhook: request failed for form %d – %s',
				$form_id,
				$response->get_error_message()
			) );
			return;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code > 299 ) {
			// Intentional failure log, not leftover debug code — the only record of a webhook the receiving endpoint rejected.
			error_log( sprintf( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				'CompactForm – Webhook: form %d got HTTP %d from %s – %s',
				$form_id,
				$code,
				$url,
				substr( (string) wp_remote_retrieve_body( $response ), 0, 500 )
			) );
		}
	}

	/**
	 * The flat key => string payload.
	 */
	private function build_payload( array $settings, array $posted, array $meta, array $skip, int $form_id ): array {
		if ( 'mapped' === $settings['bodyMode'] ) {
			$payload = [];

			foreach ( $settings['body'] as $row ) {
				if ( '' === $row['key'] || ! $row['sources'] ) {
					continue;
				}

				$values = [];
				foreach ( $row['sources'] as $source ) {
					$value = $this->resolve( $source, $posted, $meta );

					if ( '' !== $value ) {
						$values[] = $value;
					}
				}

				if ( isset( $payload[ $row['key'] ] ) && '' !== $payload[ $row['key'] ] ) {
					array_unshift( $values, $payload[ $row['key'] ] );
				}

				$payload[ $row['key'] ] = implode( self::VALUE_SEPARATOR, $values );
			}

			return $payload;
		}

		$payload = [];

		$duplicate = \CompactForm\Helpers\Utils::repeater_duplicate_keys( $form_id );

		foreach ( $posted as $key => $value ) {
			if ( '' === $key || 0 === strpos( $key, '_wpcf7' ) || 0 === strpos( $key, '_wpnonce' )
				|| in_array( $key, $skip, true ) || isset( $duplicate[ $key ] ) ) {
				continue;
			}
			$payload[ $key ] = $this->flatten( $value );
		}

		if ( $settings['meta'] ) {
			$payload += $meta;
		}

		return $payload;
	}

	/** One mapped row's value: a special mail tag, or a posted field. */
	private function resolve( string $source, array $posted, array $meta ): string {
		if ( 0 === strpos( $source, self::META_PREFIX ) ) {
			$key = substr( $source, strlen( self::META_PREFIX ) );

			return isset( $meta[ $key ] ) ? (string) $meta[ $key ] : '';
		}

		// A checkbox/multi-select posts under `name[]`; the panel offers the name
		// exactly as the compiled tag carries it, so accept either spelling.
		$key = array_key_exists( $source, $posted ) ? $source : rtrim( $source, '[]' );

		if ( ! array_key_exists( $key, $posted ) ) {
			return '';
		}

		return $this->flatten( $posted[ $key ] );
	}

	/**
	 * A posted value as a single string. An uploaded file's posted value is
	 * its original filename(s) — the file itself is never persisted or sent.
	 */
	private function flatten( $value ): string {
		if ( \CompactForm\Helpers\Utils::is_row_list( $value ) ) {
			return \CompactForm\Helpers\Utils::rows_to_string( $value, self::VALUE_SEPARATOR );
		}

		if ( is_array( $value ) ) {
			return implode( self::VALUE_SEPARATOR, array_map( 'strval', array_filter( $value, 'is_scalar' ) ) );
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	private function build_headers( array $settings, array $posted, array $meta ): array {
		$headers = [
			'Content-Type' => 'json' === $settings['format']
				? 'application/json'
				: 'application/x-www-form-urlencoded',
		];

		foreach ( $settings['headers'] as $row ) {
			$name = preg_replace( '/[^A-Za-z0-9\-_]/', '', $row['name'] );

			if ( '' === $name ) {
				continue;
			}

			$value = 'field' === $row['mode']
				? $this->resolve( $row['value'], $posted, $meta )
				: $row['value'];

			$headers[ $name ] = trim( str_replace( [ "\r", "\n" ], '', $value ) );
		}

		return $headers;
	}

	private function skip_fields( $contact_form ): array {
		$skip       = [ 'g-recaptcha-response', '_wpcf7cf_hidden_group_fields' ];
		$skip_types = [ 'fcf7_container', 'fcf7_icon_picker', 'fcf7_google_recaptcha', 'fcf7_google_recaptcha*' ];

		foreach ( $contact_form->scan_form_tags() as $tag ) {
			if ( in_array( $tag->type, $skip_types, true ) && ! empty( $tag->name ) ) {
				$skip[] = $tag->name;
			}
		}

		return (array) apply_filters( 'fcf7_webhook_skip_fields', $skip, $contact_form );
	}

	private function special_mail_tags(): array {
		$meta = [];

		foreach ( self::SPECIAL_MAIL_TAGS as $tag ) {
			$tagname  = '_' . $tag;
			$mail_tag = new \WPCF7_MailTag( sprintf( '[%s]', $tagname ), $tagname, '' );

			// Contact Form 7 core's own hook, invoked here to reuse its special mail-tag
			// resolution — not a hook this plugin defines, so it can't carry our prefix.
			$meta[ str_replace( '_', '-', $tag ) ] = apply_filters( 'wpcf7_special_mail_tags', '', $tagname, false, $mail_tag ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}

		$meta['datetime'] = $meta['date'] . ' ' . $meta['time'];

		return $meta;
	}

	public static function meta_choices(): array {
		$keys = [];

		foreach ( self::SPECIAL_MAIL_TAGS as $tag ) {
			$keys[] = str_replace( '_', '-', $tag );
		}
		$keys[] = 'datetime';

		sort( $keys );

		$choices = [];
		foreach ( $keys as $key ) {
			$choices[] = [
				'value' => self::META_PREFIX . $key,
				'label' => $key,
			];
		}

		return $choices;
	}
}
