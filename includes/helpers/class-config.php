<?php

namespace CompactForm\Helpers;

defined('ABSPATH') || die();

class Config {
	
	public static string $inactive_extensions_key = 'fcf7_inactive_addons';

	public static function get_vendor_assets(): array {
		return [
			'flatpickr-style'      => [
				'type'    => 'style',
				'handle'  => 'flatpickr',
				'path'    => 'flatpickr/flatpickr.min.css',
				'version' => '4.6.13',
				'deps'    => [],
			],
			'flatpickr-script'     => [
				'type'    => 'script',
				'handle'  => 'flatpickr',
				'path'    => 'flatpickr/flatpickr.min.js',
				'version' => '4.6.13',
				'deps'    => [],
			],
			'remixicon-style'      => [
				'type'    => 'style',
				'handle'  => 'fcf7-remixicon',
				'path'    => 'remixicon/remixicon.css',
				'version' => '4.2.0',
				'deps'    => [],
			],
			'country-select-style' => [
				'type'    => 'style',
				'handle'  => 'fcf7-country-select',
				'path'    => 'country-select/css/countrySelect.min.css',
				'version' => FCF7_VERSION,
				'deps'    => [],
			],
			'country-select-script' => [
				'type'    => 'script',
				'handle'  => 'fcf7-country-select-lib',
				'path'    => 'country-select/js/countrySelect.min.js',
				'version' => FCF7_VERSION,
				'deps'    => [ 'jquery' ],
			],
			'signature-pad-script' => [
				'type'    => 'script',
				'handle'  => 'fcf7-signature-pad',
				'path'    => 'signature-pad/signature-pad.min.js',
				'version' => '4.0.0',
				'deps'    => [],
			],
			'qrcode-script'        => [
				'type'    => 'script',
				'handle'  => 'fcf7-qrcode',
				'path'    => 'qrcode-generator/qrcode.js',
				'version' => '1.4.4',
				'deps'    => [],
			],
		];
	}
	
	public static function get_extensions_map(): array {
		$map = [
			'form-fields' => [
				'title'    => __('Form Fields', 'compactform'),
				'tabTitle' => __('Form Fields', 'compactform'),
				'elements' => [
					'star-rating'      => [
						'title'  => __('Star Rating', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => true,
						'hasJs'  => true,
					],
					'country-dropdown' => [
						'title'  => __('Country Dropdown', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => true,
						'hasJs'  => true,
					],
					'date-picker'      => [
						'title'  => __('Date Picker', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => true,
					],
					'time-picker'      => [
						'title'  => __('Time Picker', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => true,
					],
					'datetime-picker'  => [
						'title'  => __('Date & Time Picker', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => true,
					],
					'signature'        => [
						'title'  => __('Digital Signature', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => true,
						'hasJs'  => true,
					],
					'range-slider' => [
						'title'  => __('Range Slider', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => true,
						'hasJs'  => true,
					],
					'spam-protection' => [
						'title'  => __('Spam Protection', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => true,
						'hasJs'  => true,
					],
					'google-recaptcha' => [
						'title'  => __('Google reCAPTCHA', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => true,
						'hasJs'  => true,
					],
				],
			],
			
			'basic-fields' => [
				'title'    => __('Basic Fields', 'compactform'),
				'tabTitle' => __('Basic Fields', 'compactform'),
				'elements' => [
					'text'     => [
						'title'  => __('Text', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'email'    => [
						'title'  => __('Email', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'tel'      => [
						'title'  => __('Phone', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'url'      => [
						'title'  => __('URL', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'number'   => [
						'title'  => __('Number', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'date'     => [
						'title'  => __('Date', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'textarea' => [
						'title'  => __('Textarea', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'quiz'     => [
						'title'  => __('Quiz', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'file'     => [
						'title'  => __('File Upload', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'submit'   => [
						'title'  => __('Submit Button', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
				],
			],
			'choice-fields' => [
				'title'    => __('Choice Fields', 'compactform'),
				'tabTitle' => __('Choice Fields', 'compactform'),
				'elements' => [
					'select'     => [
						'title'  => __('Dropdown', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'radio'      => [
						'title'  => __('Radio', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'checkbox'   => [
						'title'  => __('Checkbox', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'acceptance' => [
						'title'  => __('Acceptance', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
				],
			],
			'layout'      => [
				'title'    => __('Layout', 'compactform'),
				'tabTitle' => __('Layout', 'compactform'),
				'elements' => [
					'heading' => [
						'title'  => __('Heading', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'divider' => [
						'title'  => __('Divider', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'html' => [
						'title'  => __('Raw HTML', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
				],
			],
			'utilities'   => [
				'title'    => __('Utilities', 'compactform'),
				'tabTitle' => __('Utilities', 'compactform'),
				'elements' => [
					'container'    => [
						'title'  => __('Layout Container', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => true,
						'hasJs'  => false,
					],
					'webhook'      => [
						'title'  => __('Webhook (Pabbly/Zapier)', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'default-mail' => [
						'title'  => __('Default Mail', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
					'admin-bar'    => [
						'title'  => __('Admin Bar Edit Link', 'compactform'),
						'docUrl' => '#',
						'isPro'  => false,
						'hasCss' => false,
						'hasJs'  => false,
					],
				],
			],
		];

		return (array) apply_filters('fcf7_extensions_map', $map);
	}

	public static function get_extensions_flat_map(): array {
		$flat = [];

		foreach (self::get_extensions_map() as $group) {
			foreach ($group['elements'] as $slug => $element) {
				$flat[ $slug ] = [
					'title'   => $element['title'],
					'is_pro'  => ! empty($element['isPro']),
					'has_css' => ! empty($element['hasCss']),
					'has_js'  => ! empty($element['hasJs']),
				];
			}
		}

		return $flat;
	}

	public static function get_inactive_extensions(): array {
		$inactive = get_option(self::$inactive_extensions_key, []);

		return is_array($inactive) ? array_values($inactive) : [];
	}

	public static function set_inactive_extensions(array $slugs): void {
		$clean = array_values(array_filter(array_map('sanitize_key', $slugs)));

		update_option(self::$inactive_extensions_key, $clean);
	}

	public static function is_extension_active(string $slug): bool {
		return ! in_array($slug, self::get_inactive_extensions(), true);
	}
}
