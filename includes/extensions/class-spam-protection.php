<?php

namespace CompactForm\Extensions;

if (! defined('ABSPATH')) {
	exit;
}

class Spam_Protection {

	private const STAMP_PREFIX = 'fcf7_sp_ts_';

	private const STAMP_TTL = 43200; // 12 hours.

	private const HONEYPOT_PREFIX = '_fcf7_hp_';
	private const TIMESTAMP_FIELD = '_fcf7_ts';

	private const MAX_WORDS      = 5000;
	private const MAX_WORD_LEN   = 100;
	private const MAX_LIST_BYTES = 1048576; // 1 MB.

	public function __construct() {
		add_filter('wpcf7_form_elements', [ $this, 'inject_traps' ], 20);
		add_action('wp_enqueue_scripts', [ $this, 'register_assets' ], 5);
		add_filter('wpcf7_feedback_response', [ $this, 'refresh_stamp' ], 10, 2);
		add_action('wpcf7_before_send_mail', [ $this, 'refuse_submission' ], 1, 3);
		add_action('wpcf7_before_send_mail', [ $this, 'refuse_banned_words' ], 3, 3);
		add_action('wp_ajax_fcf7_sp_import_wordlist', [ $this, 'ajax_import_wordlist' ]);
	}

	public function get_form_settings(int $form_id): array {
		$stored  = (string) get_post_meta($form_id, '_fcf7_builder_schema', true);
		$decoded = json_decode($stored, true);

		if ('' === $stored || ! is_array($decoded)) {
			return $this->no_settings();
		}

		$sp = $decoded['configuration']['spamProtection'] ?? [];

		if (! is_array($sp)) {
			$sp = [];
		}

		$fill = isset($sp['minTimeSeconds']) ? (int) $sp['minTimeSeconds'] : 3;

		$bot_message    = isset($sp['botMessage']) ? trim((string) $sp['botMessage']) : '';
		$banned_message = isset($sp['bannedMessage']) ? trim((string) $sp['bannedMessage']) : '';

		return [
			'honeypotEnabled' => ! array_key_exists('honeypotEnabled', $sp) || ! empty($sp['honeypotEnabled']),
			'minTimeEnabled'  => ! empty($sp['minTimeEnabled']),
			'minTimeSeconds'  => $fill > 0 ? $fill : 3,
			'botMessage' => '' !== $bot_message
				? $bot_message
				: __('Your submission looks automated and was not sent. Please try again.', 'compactform'),
			'bannedEnabled' => ! empty($sp['bannedEnabled']),
			'bannedWords' => $this->parse_list($sp['bannedWords'] ?? ''),
			'bannedMessage' => '' !== $banned_message
				? $banned_message
				: __('Your message contains words that are not allowed.', 'compactform'),
		];
	}

	private function no_settings(): array {
		return [
			'honeypotEnabled' => false,
			'minTimeEnabled'  => false,
			'minTimeSeconds'  => 3,
			'botMessage'      => '',
			'bannedEnabled'   => false,
			'bannedWords'     => [],
			'bannedMessage'   => '',
		];
	}

	private function parse_list($raw): array {
		if (is_array($raw)) {
			$parts = $raw;
		} else {
			$parts = preg_split('/[\r\n,]+/', (string) $raw);
		}

		$out = [];
		foreach ((array) $parts as $part) {
			$part = strtolower(trim((string) $part));

			if ('' !== $part) {
				$out[] = $part;
			}
		}

		return array_values(array_unique($out));
	}

	public function ajax_import_wordlist(): void {
		check_ajax_referer('fcf7_builder', 'nonce');

		if (! current_user_can('wpcf7_edit_contact_forms')) {
			wp_send_json_error([ 'message' => __('Permission denied.', 'compactform') ], 403);
		}

		$url  = isset($_POST['url']) ? esc_url_raw((string) wp_unslash($_POST['url'])) : '';
		$text = isset($_POST['text']) ? sanitize_textarea_field(wp_unslash($_POST['text'])) : '';
		$have = isset($_POST['existing']) ? sanitize_text_field(wp_unslash($_POST['existing'])) : '';

		if ('' !== $url) {
			$text = $this->fetch_wordlist($url);

			if (null === $text) {
				wp_send_json_error([ 'message' => __('Could not read that URL.', 'compactform') ]);
			}
		}

		if ('' === trim($text)) {
			wp_send_json_error([ 'message' => __('There was nothing to import.', 'compactform') ]);
		}

		$existing = $this->parse_wordlist($have);
		$incoming = $this->parse_wordlist($text);
		$merged   = array_slice(array_values(array_unique(array_merge($existing, $incoming))), 0, self::MAX_WORDS);

		wp_send_json_success([
			'words' => implode(', ', $merged),
			'added' => count($merged) - count($existing),
			'total' => count($merged),
		]);
	}

	/** @return string|null Raw body, or null when the request failed. */
	private function fetch_wordlist(string $url): ?string {
		$response = wp_safe_remote_get($url, [ 'timeout' => 10 ]);

		if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
			return null;
		}

		return (string) wp_remote_retrieve_body($response);
	}

	/**
	 * Turn a raw list into words.
	 *
	 * @return string[]
	 */
	private function parse_wordlist(string $raw): array {
		$raw = substr($raw, 0, self::MAX_LIST_BYTES);

		$raw = (string) preg_replace('/^\xEF\xBB\xBF/', '', $raw);

		$words = [];

		foreach (preg_split('/\R/', $raw) as $line) {
			$line = trim((string) preg_replace('/(^|\s)(#|\/\/).*$/', '', (string) $line));

			if ('' === $line) {
				continue;
			}

			foreach (explode(',', $line) as $word) {
				$word = strtolower(trim(sanitize_text_field($word)));

				if ('' !== $word && strlen($word) <= self::MAX_WORD_LEN) {
					$words[] = $word;
				}

				if (count($words) >= self::MAX_WORDS) {
					break 2;
				}
			}
		}

		return array_values(array_unique($words));
	}

	/**
	 * The honeypot's field name.
	 */
	private function honeypot_name(int $form_id): string {
		return self::HONEYPOT_PREFIX . substr(hash_hmac('sha256', 'honeypot|' . $form_id, wp_salt('nonce')), 0, 12);
	}

	public function inject_traps($elements) {
		$form = \WPCF7_ContactForm::get_current();

		if (! $form) {
			return $elements;
		}

		$form_id  = (int) $form->id();
		$settings = $this->get_form_settings($form_id);
		$extra    = '';

		if ($settings['honeypotEnabled']) {
			$extra .= sprintf(
				'<span class="fcf7-hp" style="position:absolute!important;left:-9999px!important;top:auto!important;width:1px!important;height:1px!important;overflow:hidden!important;">'
				. '<label for="%1$s">%2$s</label>'
				. '<input type="text" id="%1$s" name="%1$s" value="" size="1" tabindex="-1" autocomplete="off" />'
				. '</span>',
				esc_attr($this->honeypot_name($form_id)),
				esc_html__('Leave this field empty', 'compactform')
			);
		}

		if ($settings['minTimeEnabled']) {
			$issued = time();
			$extra .= sprintf(
				'<input type="hidden" name="%s" value="%s" />',
				esc_attr(self::TIMESTAMP_FIELD),
				esc_attr($issued . '|' . $this->sign_timestamp($issued, $form_id))
			);
			wp_enqueue_script('fcf7-sp-fill-time');
		}

		return '' === $extra ? $elements : $elements . $extra;
	}

	private function sign_timestamp(int $issued, int $form_id): string {
		return hash_hmac('sha256', $issued . '|' . $form_id, wp_salt('nonce'));
	}

	private function filled_too_fast(int $form_id, int $seconds): bool {
		// Called from refuse_submission(), hooked on wpcf7_before_send_mail — fires only after
		// CF7 core has already processed and validated the whole submission's nonce.
		$raw = isset($_POST[ self::TIMESTAMP_FIELD ]) ? sanitize_text_field(wp_unslash($_POST[ self::TIMESTAMP_FIELD ])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$parts = explode('|', $raw);

		if (2 !== count($parts) || ! ctype_digit($parts[0])) {
			return true;
		}

		[ $issued, $signature ] = $parts;

		if (! hash_equals($this->sign_timestamp((int) $issued, $form_id), $signature)) {
			return true;
		}

		$spent = $this->stamp_key($signature);

		if ('' !== $spent && get_transient($spent)) {
			return true;
		}

		if (( time() - (int) $issued ) < $seconds) {
			return true;
		}

		if ('' !== $spent) {
			set_transient($spent, 1, self::STAMP_TTL);
		}

		return false;
	}

	/** Where a spent stamp is remembered. Empty when the IP is unknown. */
	private function stamp_key(string $signature): string {
		$ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

		return '' === $ip ? '' : self::STAMP_PREFIX . md5($signature . '|' . $ip);
	}

	public function refresh_stamp($response, $result = []) {
		$form = \WPCF7_ContactForm::get_current();

		if (! $form || ! is_array($response)) {
			return $response;
		}

		$form_id = (int) $form->id();

		if (! $this->get_form_settings($form_id)['minTimeEnabled']) {
			return $response;
		}

		$issued              = time();
		$response['fcf7_ts'] = $issued . '|' . $this->sign_timestamp($issued, $form_id);

		return $response;
	}

	/** Registered here, enqueued from inject_traps() only when the stamp is in the form. */
	public function register_assets(): void {
		wp_register_script('fcf7-sp-fill-time', FCF7_ASSETS . 'js/spam-fill-time.min.js', [], FCF7_VERSION, true);
	}

	/** True when the honeypot came back with anything in it. Only a bot fills it. */
	private function honeypot_tripped(int $form_id): bool {
		$name = $this->honeypot_name($form_id);

		// Called from refuse_submission(), hooked on wpcf7_before_send_mail — fires only after
		// CF7 core has already processed and validated the whole submission's nonce. Value is
		// only ever used for this boolean presence/emptiness check, never as data.
		return isset($_POST[ $name ]) && '' !== trim((string) wp_unslash($_POST[ $name ])); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * @param \WPCF7_ContactForm $contact_form
	 * @param bool               $abort
	 * @param \WPCF7_Submission  $submission
	 */
	public function refuse_submission($contact_form, &$abort, $submission): void {
		if ($abort) {
			return;
		}

		$form_id  = (int) $contact_form->id();
		$settings = $this->get_form_settings($form_id);

		$message = null;

		if ($settings['honeypotEnabled'] && $this->honeypot_tripped($form_id)) {
			$message = $settings['botMessage'];
		} elseif ($settings['minTimeEnabled'] && $this->filled_too_fast($form_id, $settings['minTimeSeconds'])) {
			$message = $settings['botMessage'];
		}

		if (null === $message) {
			return;
		}

		$abort = true;

		$submission->set_response($message);
	}

	/**
	 * @param \WPCF7_ContactForm $contact_form
	 * @param bool               $abort
	 * @param \WPCF7_Submission  $submission
	 */
	public function refuse_banned_words($contact_form, &$abort, $submission): void {
		if ($abort) {
			return;
		}

		$settings = $this->get_form_settings((int) $contact_form->id());
		$message  = $this->banned_word_refusal($submission, $settings);

		if (null === $message) {
			return;
		}

		$abort = true;
		$submission->set_response($message);
	}

	private function banned_word_refusal(\WPCF7_Submission $submission, array $settings): ?string {
		if (! $settings['bannedEnabled'] || ! $settings['bannedWords']) {
			return null;
		}

		$haystack = strtolower(implode(' ', $this->posted_text($submission)));

		if ('' === trim($haystack)) {
			return null;
		}

		foreach ($settings['bannedWords'] as $word) {
			$quoted = preg_quote($word, '/');
			$found  = preg_match('/^[\w\'-]+$/u', $word)
				? preg_match('/\b' . $quoted . '\b/u', $haystack)
				: false !== strpos($haystack, $word);

			if ($found) {
				return $settings['bannedMessage'];
			}
		}

		return null;
	}

	/** @return string[] Every scalar answer, flattened. */
	private function posted_text(\WPCF7_Submission $submission): array {
		$out = [];

		foreach ((array) $submission->get_posted_data() as $key => $value) {
			if (str_starts_with((string) $key, '_')) {
				continue;
			}

			$items = (array) $value;

			array_walk_recursive($items, static function ($item) use ( &$out ) {
				if (is_scalar($item)) {
					$out[] = (string) $item;
				}
			});
		}

		return $out;
	}

}
