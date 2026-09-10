<?php

namespace CompactForm\Mail_Templates\Templates;

use CompactForm\Mail_Templates\Base_Mail_Template;

if (! defined('ABSPATH')) {
	exit;
}

class Html_Table_Mail_Template extends Base_Mail_Template {
	public function get_id(): string {
		return 'html-table';
	}

	public function get_title(): string {
		return __('HTML — Default Template', 'compactform');
	}

	public function get_use_html(): bool {
		return true;
	}

	private const ACCENT      = '#0091fa';
	private const TEXT        = '#111827';
	private const MUTED       = '#6b7280';
	private const BORDER      = '#eef0f3';
	private const CANVAS      = '#f1f5f9';
	private const FOOTER_FILL = '#f9fafb';

	public function render(array $rows, array $context): string {
		$ind   = static fn (int $level): string => str_repeat('    ', $level);
		$last  = count($rows) - 1;
		$pad   = $ind(7); // Row depth: table > tr > td.wrap > table > tr > td.body > table.
		$lines = [];

		foreach ($rows as $i => $row) {
			$value_border = $i === $last ? '' : 'border-bottom:1px solid ' . self::BORDER . ';';

			$lines[] = $pad . sprintf(
				'<tr><td class="fcf7-label" style="padding:16px 0 4px;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:%1$s;">%2$s</td></tr>',
				self::MUTED,
				esc_html($row['label'])
			);

			$value = empty($row['raw']) ? '[' . $row['tag'] . ']' : nl2br($row['tag']);

			$lines[] = $pad . sprintf(
				'<tr><td class="fcf7-value" style="padding:0 0 14px;font-size:15px;line-height:1.5;color:%1$s;%2$s">%3$s</td></tr>',
				self::TEXT,
				$value_border,
				$value
			);
		}

		$footer = esc_html(sprintf(
			/* translators: 1: site name, 2: site URL */
			__('Sent from %1$s (%2$s)', 'compactform'),
			'[_site_title]',
			'[_site_url]'
		));

		$title   = esc_html($context['form_title'] ?? '');
		$eyebrow = esc_html__('New Submission', 'compactform');
		$font    = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";

		// This markup never loads as a page — it's an HTML email body handed to wp_mail().
		// Email clients don't support <link rel="stylesheet">, so a <style> block (or inline
		// style="" attributes, both used here) is the only way to style an email at all.
		$out = [
			'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . self::CANVAS . ';">',
			$ind(1) . '<tr>',
			$ind(2) . '<td class="fcf7-wrap" align="center" style="padding:32px 16px;">',
			$ind(3) . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="fcf7-card" style="width:100%;max-width:600px;margin:0 auto;font-family:' . $font . ';background:#ffffff;border:1px solid ' . self::BORDER . ';border-radius:12px;overflow:hidden;">',
			$ind(4) . '<tr>',
			$ind(5) . '<td class="fcf7-header" style="background:' . self::ACCENT . ';padding:28px 32px;border-radius:12px 12px 0 0;">',
			$ind(6) . '<div style="font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.72);margin:0 0 8px;">' . $eyebrow . '</div>',
			$ind(6) . '<div class="fcf7-title" style="font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">' . $title . '</div>',
			$ind(5) . '</td>',
			$ind(4) . '</tr>',
			$ind(4) . '<tr>',
			$ind(5) . '<td class="fcf7-body" style="padding:8px 32px 24px;">',
			$ind(6) . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;">',
		];

		$out = array_merge($out, $lines, [
			$ind(6) . '</table>',
			$ind(5) . '</td>',
			$ind(4) . '</tr>',
			$ind(4) . '<tr>',
			$ind(5) . '<td class="fcf7-footer" style="padding:16px 32px;background:' . self::FOOTER_FILL . ';border-top:1px solid ' . self::BORDER . ';border-radius:0 0 12px 12px;font-size:12px;line-height:1.5;color:' . self::MUTED . ';text-align:center;">',
			$ind(6) . $footer,
			$ind(5) . '</td>',
			$ind(4) . '</tr>',
			$ind(3) . '</table>',
			$ind(2) . '</td>',
			$ind(1) . '</tr>',
			'</table>',
		]);

		return implode("\n", $out);
	}
}
