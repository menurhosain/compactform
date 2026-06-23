<?php

namespace CompactForm\Mail_Templates;

if (! defined('ABSPATH')) {
	exit;
}

abstract class Base_Mail_Template {

	abstract public function get_id(): string;

	abstract public function get_title(): string;

	abstract public function get_use_html(): bool;

	abstract public function render(array $rows, array $context): string;
}
