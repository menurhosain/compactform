<?php

namespace CompactForm\Mail_Templates\Templates;

use CompactForm\Mail_Templates\Base_Mail_Template;

if (! defined('ABSPATH')) {
	exit;
}

class Plain_Text_Mail_Template extends Base_Mail_Template {

	public function get_id(): string {
		return 'plain-text';
	}

	public function get_title(): string {
		return __('Plain Text', 'compactform');
	}

	public function get_use_html(): bool {
		return false;
	}

	public function render(array $rows, array $context): string {
		$lines = [];
		foreach ($rows as $row) {
			$lines[] = ! empty($row['raw'])
				? $row['label'] . ':' . "\n" . $row['tag']
				: $row['label'] . ': [' . $row['tag'] . ']';
		}

		return implode("\n", $lines) . "\n\n"
			. sprintf(
				/* translators: 1: site name, 2: site URL */
				__('Sent from %1$s (%2$s)', 'compactform'),
				'[_site_title]',
				'[_site_url]'
			);
	}
}
