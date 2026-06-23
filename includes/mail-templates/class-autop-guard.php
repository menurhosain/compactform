<?php

namespace CompactForm\Mail_Templates;

if (! defined('ABSPATH')) {
	exit;
}

class Autop_Guard {

	private const MARKER = 'class="fcf7-card"';

	private bool $skip = false;

	public function __construct() {
		add_filter('wpcf7_mail_html_body', [ $this, 'detect' ], 9, 1);
		add_filter('wpcf7_autop_or_not', [ $this, 'decide' ], 10, 2);
	}

	public function detect($body) {
		$skip = is_string($body) && false !== strpos($body, self::MARKER);

		$this->skip = (bool) apply_filters('fcf7_mail_body_skip_autop', $skip, $body);

		return $body;
	}

	public function decide($autop, $options = []) {
		if ($this->skip && 'mail' === ($options['for'] ?? '')) {
			return false;
		}

		return $autop;
	}
}
