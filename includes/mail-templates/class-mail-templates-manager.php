<?php

namespace CompactForm\Mail_Templates;

if (! defined('ABSPATH')) {
	exit;
}

class Mail_Templates_Manager {

	private static ?self $instance = null;

	/** @var Base_Mail_Template[] keyed by template id. */
	private array $templates = [];

	public static function instance(): self {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		require_once __DIR__ . '/abstract-mail-template.php';
		$this->register_defaults();

		/**
		 * add_action( 'fcf7_register_mail_templates', fn( $m ) => $m->register( new My_Template() ) );
		 */
		do_action('fcf7_register_mail_templates', $this);
	}

	private function register_defaults(): void {
		// One class per file — drop a new class-*-mail-template.php in and it is loaded.
		foreach (glob(__DIR__ . '/templates/class-*.php') as $file) {
			require_once $file;
		}

		$this->register(new Templates\Plain_Text_Mail_Template());
		$this->register(new Templates\Html_Table_Mail_Template());
	}

	public function register(Base_Mail_Template $template): void {
		$this->templates[ $template->get_id() ] = $template;
	}

	public function get(string $id): ?Base_Mail_Template {
		return $this->templates[ $id ] ?? null;
	}

	/** @return Base_Mail_Template[] */
	public function all(): array {
		return $this->templates;
	}
}
