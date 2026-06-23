<?php

namespace CompactForm\Builder;

defined('ABSPATH') || die();

final class Templates {

	const ENDPOINT      = 'http://192.168.1.222:4567/felxiforms.json';
	const MANIFEST_KEY  = 'fcf7_templates_manifest';
	const TEMPLATE_KEY  = 'fcf7_template_';
	const CACHE_TTL     = 6 * HOUR_IN_SECONDS;

	private static ?self $instance = null;

	public static function instance(): self {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action('wp_ajax_fcf7_templates_list', [ $this, 'ajax_list' ]);
		add_action('wp_ajax_fcf7_templates_get', [ $this, 'ajax_get' ]);
	}

	public static function endpoint(): string {
		return (string) apply_filters('fcf7_templates_endpoint', self::ENDPOINT);
	}

	public function ajax_list(): void {
		$this->guard();

		// $this->guard() above already verified the request's nonce; no separate check belongs here.
		$refresh = isset($_GET['refresh']) && '1' === sanitize_text_field(wp_unslash($_GET['refresh'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$items = $this->manifest($refresh);

		if (null === $items) {
			wp_send_json_error([ 'message' => __('Could not reach the template library.', 'compactform') ], 502);
		}

		$response = [
			'items'      => array_values(array_map([ $this, 'public_row' ], $items)),
			'categories' => $this->vocabulary($items, 'category'),
			'badges'     => $this->vocabulary($items, 'badge'),
		];

		wp_send_json_success(apply_filters('fcf7_templates_list_response', $response, $items));
	}

	public function ajax_get(): void {
		$this->guard();

		// $this->guard() above already verified the request's nonce; no separate check belongs here.
		$id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$items = $this->manifest(false);
		$row   = null;

		foreach ((array) $items as $item) {
			if ((string) $item['id'] === $id) {
				$row = $item;
				break;
			}
		}

		if (! $row) {
			wp_send_json_error([ 'message' => __('That template no longer exists.', 'compactform') ], 404);
		}

		// Seam for the licence check that will gate pro templates server-side.
		if (! apply_filters('fcf7_templates_can_import', true, $row)) {
			wp_send_json_error([ 'message' => __('This template requires CompactForm Pro.', 'compactform') ], 403);
		}

		$schema = $this->template_schema($row);

		if (null === $schema) {
			wp_send_json_error([ 'message' => __('Could not download that template.', 'compactform') ], 502);
		}

		wp_send_json_success(apply_filters('fcf7_templates_get_response', [ 'schema' => $schema ], $row));
	}

	private function guard(): void {
		check_ajax_referer('fcf7_builder', 'nonce');

		if (! current_user_can('wpcf7_edit_contact_forms')) {
			wp_send_json_error([ 'message' => __('Permission denied.', 'compactform') ], 403);
		}
	}

	/** Normalised manifest rows, `data` url included — internal use only. */
	private function manifest(bool $refresh): ?array {
		$items = $refresh ? null : get_transient(self::MANIFEST_KEY);

		if (! is_array($items)) {
			$body = $this->fetch(self::endpoint());

			if (! is_array($body)) {
				return null;
			}

			$items = [];

			foreach ($body as $raw) {
				$row = $this->normalize_row($raw);
				if ($row) {
					$items[] = $row;
				}
			}

			set_transient(self::MANIFEST_KEY, $items, self::CACHE_TTL);
		}

		// Filtered after the transient is written, never before — what a third party adds or
		// removes must not be baked into the cached copy of the remote library. Runs on the
		// list AND the single-template lookup, so an injected row is importable, not just
		// visible. An injected row supplies its own `data` url and skips `normalize_row()`,
		// same-host check included: whoever adds it owns its safety.
		return apply_filters('fcf7_templates_manifest', $items);
	}

	private function normalize_row($raw): ?array {
		if (! is_array($raw) || empty($raw['id']) || empty($raw['data'])) {
			return null;
		}

		$data = $this->same_host_url((string) $raw['data']);

		if (! $data) {
			return null;
		}

		$images = [];
		foreach ((array) ($raw['image'] ?? []) as $image) {
			$url = esc_url_raw((string) $image);
			if ($url) {
				$images[] = $url;
			}
		}

		$categories = $this->term_list($raw['category'] ?? []);
		$badges     = $this->term_list($raw['badge'] ?? []);

		$preview = esc_url_raw((string) ($raw['preview'] ?? ''));

		return [
			'id'           => (string) $raw['id'],
			'name'         => sanitize_text_field((string) ($raw['name'] ?? '')),
			'description'  => sanitize_text_field((string) ($raw['description'] ?? '')),
			'image'        => $images,
			'preview'      => ('#' === ($raw['preview'] ?? '')) ? '' : $preview,
			'subscription' => ('pro' === ($raw['subscription'] ?? 'free')) ? 'pro' : 'free',
			'category'     => $categories,
			'badge'        => $badges,
			'data'         => $data,
		];
	}

	private function term_list($raw): array {
		$out = [];

		foreach ((array) $raw as $term) {
			$term = sanitize_text_field((string) $term);
			if ('' !== $term) {
				$out[] = $term;
			}
		}

		return $out;
	}

	private function public_row(array $row): array {
		unset($row['data']);
		return $row;
	}

	private function vocabulary(array $items, string $key): array {
		$all = [];

		foreach ($items as $item) {
			foreach ((array) ($item[ $key ] ?? []) as $term) {
				$all[ $term ] = true;
			}
		}

		$out = array_keys($all);
		sort($out);

		return $out;
	}

	private function template_schema(array $row): ?array {
		$key    = self::TEMPLATE_KEY . md5($row['data']);
		$cached = get_transient($key);

		if (is_array($cached)) {
			return $cached;
		}

		$body = $this->fetch($row['data']);

		if (! is_array($body) || ! isset($body['fields']) || ! is_array($body['fields'])) {
			return null;
		}

		set_transient($key, $body, self::CACHE_TTL);

		return $body;
	}

	/**
	 * A manifest can only point at its own host — the `data` urls are remote input, and
	 * fetching them server-side would otherwise be an SSRF hop into the local network.
	 */
	private function same_host_url(string $url): string {
		$url = esc_url_raw($url);

		if (! $url) {
			return '';
		}

		$host = wp_parse_url($url, PHP_URL_HOST);
		$home = wp_parse_url(self::endpoint(), PHP_URL_HOST);

		return ($host && $host === $home) ? $url : '';
	}

	private function fetch(string $url): ?array {
		$response = wp_remote_get($url, [
			'timeout' => 15,
			'headers' => [ 'Accept' => 'application/json' ],
		]);

		if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
			return null;
		}

		$decoded = json_decode(wp_remote_retrieve_body($response), true);

		return is_array($decoded) ? $decoded : null;
	}
}
