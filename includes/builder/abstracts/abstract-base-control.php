<?php


namespace CompactForm\Builder\Abstracts;


defined('ABSPATH') || die();

abstract class Base_Control {
	/**
	 * The control type slug, e.g. 'text', 'color', 'select'. Must match the JS
	 * renderer registered for it.
	 */
	abstract public function get_type(): string;

	abstract public function get_option_value_stringify($value): string;

	/**
	 * Sanitise a submitted value for this control type. Authoritative — the
	 * builder is untrusted, so every saved value passes through here.
	 *
	 * @param mixed $value The raw value from the schema.
	 * @param array $args  The control instance args (options, min, max, default …).
	 * @return mixed
	 */
	public function sanitize($value, array $args = []) {
		return is_scalar($value) ? sanitize_text_field((string) $value) : '';
	}

	/**
	 * The value a freshly-dropped field gets for this control when no explicit
	 * default is set in the control instance args.
	 *
	 * @return mixed
	 */
	public function get_default() {
		return '';
	}

	/**
	 * Render a control's value into CSS declarations for one `selectors` entry.
	 *
	 * The base implementation handles scalar values and the `{{VALUE}}`
	 * placeholder. Composite controls (slider, dimensions, typography) override
	 * this to support their own placeholders / emit a whole declaration block.
	 *
	 * @param mixed  $value    Sanitised value from the schema.
	 * @param string $template e.g. `color: {{VALUE}};` ('' for composite controls).
	 * @return string Declarations, or '' when the value is unset (emit nothing).
	 */
	public function get_css($value, string $template): string {
		if ('' === $template) {
			return '';
		}
		$v = $this->css_value($value);
		if ('' === $v) {
			return '';
		}
		return str_replace('{{VALUE}}', $v, $template);
	}

	/**
	 * Render a value straight to device => declarations, for a composite control
	 * whose SUB-values are responsive independently (see Border_Control, where
	 * width is responsive but style and colour are not).
	 *
	 * Return null — the default — to use the generic path instead: the whole
	 * value is per-device when the control instance is flagged `responsive`, and
	 * desktop-only otherwise.
	 *
	 * @param mixed    $value    The control's whole (sanitised) value.
	 * @param string   $template The `selectors` template ('' for composites).
	 * @param string[] $devices  Devices in play: 'desktop' plus the form's breakpoints.
	 * @return array<string,string>|null device => declarations, or null.
	 */
	public function get_css_devices($value, string $template, array $devices): ?array {
		return null;
	}

	/** The scalar CSS representation of a value ('' when unset / not scalar). */
	protected function css_value($value): string {
		if (null === $value || is_array($value) || is_object($value)) {
			return '';
		}
		if (is_bool($value)) {
			return $value ? '1' : '';
		}
		return self::css_safe((string) $value);
	}

	/**
	 * Strip anything that could break out of a CSS declaration. Selectors and
	 * templates come from (trusted) PHP, but values come from the user.
	 */
	public static function css_safe($value): string {
		return trim(preg_replace('/[{};]|[\r\n]/', '', (string) $value));
	}

	/** A finite, bounded number as a CSS-safe string ('' when not numeric). */
	protected static function css_number($value, float $max = 9999): string {
		if (! is_numeric($value)) {
			return '';
		}
		$n = 0 + $value;
		return (abs($n) <= $max) ? (string) $n : '';
	}
}
