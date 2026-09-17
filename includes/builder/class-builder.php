<?php


namespace CompactForm\Builder;

defined('ABSPATH') || die();

spl_autoload_register(static function (string $class): void {
	$prefix = __NAMESPACE__ . '\\';
	if (0 !== strpos($class, $prefix)) {
		return;
	}

	$parts = explode('\\', substr($class, strlen($prefix)));
	$name  = array_pop($parts);
	$sub   = implode('\\', $parts);

	$roots = [
		''                  => [ '', 'class-' ],
		'Controls'          => [ '/controls', 'class-' ],
		'Fields'            => [ '/fields', 'class-' ],
		'Abstracts'         => [ '/abstracts', 'abstract-' ],
		'Abstracts\\Traits' => [ '/abstracts/traits', 'trait-' ],
	];

	if (! isset($roots[ $sub ])) {
        // Unknown sub-namespace — nothing this loader knows how to place.
		return; 
	}

	[ $dir, $file_prefix ] = $roots[ $sub ];
	$path = __DIR__ . $dir . '/' . $file_prefix . strtolower(str_replace('_', '-', $name)) . '.php';

	if (file_exists($path)) {
		require $path;
	}
});

class Builder {
	private static ?self $instance = null;

	public static function instance(): self {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Boot the field/control registry now (loads controls, all fields, the
		// helpers and Form_Compiler, all lazily via the autoloader above).
		Fields_Manager::instance();

		add_action('wp_ajax_fcf7_builder_render', [ $this, 'ajax_render' ]);
	}

	public function fields(): Fields_Manager {
		return Fields_Manager::instance();
	}

	/** Everything the JS builder needs, localized on the editor screen. */
	public function localized_data(int $form_id): array {
		$fields_config = Fields_Manager::instance()->get_config();

		$data = [
			'ajaxUrl'    => admin_url('admin-ajax.php'),
			'nonce'      => wp_create_nonce('fcf7_builder'),
			'saveNonce'  => wp_create_nonce('fcf7_builder_save'),
			'formId'     => $form_id,
			'logoUrl'      => FCF7_URL . 'assets/branding/logo.png',
			'logoLightUrl' => FCF7_URL . 'assets/branding/logo-light.png',
			'menuIconUrl'  => FCF7_URL . 'assets/branding/menu-icon.png',
			'fields'     => $fields_config,
			'instanceMarkup' => $this->instance_markup_for($form_id, $fields_config),
			'fontFamilies' => Controls\Typography_Control::families_for_js(),
			'borderStyles' => Controls\Border_Control::styles_for_js(),
			'categories' => [
				'layout'     => __('Layout', 'compactform'),
				'basic'      => __('Basic Fields', 'compactform'),
				'choice'     => __('Choice Fields', 'compactform'),
				'extensions' => __('Extensions', 'compactform'),
				'security'   => __('Security', 'compactform'),
			],
			'tabs'       => [
				'settings' => [ 'label' => __('Settings', 'compactform'), 'icon' => 'ri-pencil-line' ],
				'style'    => [ 'label' => __('Style', 'compactform'), 'icon' => 'ri-contrast-2-line' ],
				'advanced' => [ 'label' => __('Advanced', 'compactform'), 'icon' => 'ri-settings-3-line' ],
			],
			'integrations' => $this->integrations_for(),
			'configuration' => $this->configuration_for(),
		];

		return apply_filters('fcf7_builder_localized_data', $data, $form_id);
	}

	protected function configuration_for(): array {
		$configuration = [
			'spamProtection' => [
				'active' => \CompactForm\Helpers\Config::is_extension_active('spam-protection'),
			],
		];

		return apply_filters('fcf7_builder_configuration', $configuration);
	}

	protected function integrations_for(): array {
		$integrations = [];

		$webhook_active = \CompactForm\Helpers\Config::is_extension_active('webhook');

		$integrations['webhook'] = [
			'active'      => $webhook_active,
			'metaChoices' => ($webhook_active && class_exists('\CompactForm\Extensions\Webhook'))
				? \CompactForm\Extensions\Webhook::meta_choices()
				: [],
		];

		return apply_filters('fcf7_builder_integrations', $integrations);
	}

	protected function instance_markup_for(int $form_id, array $fields_config): array {
		if (! $form_id) {
			return [];
		}

		$eligible_types = [];
		foreach ($fields_config as $type_def) {
			if (! empty($type_def['builderMarkup'])) {
				$eligible_types[ $type_def['type'] ] = true;
			}
		}
		if (! $eligible_types) {
			return [];
		}

		$decoded = json_decode((string) get_post_meta($form_id, '_fcf7_builder_schema', true), true);
		if (! is_array($decoded) || empty($decoded['fields'])) {
			return [];
		}

		$fields_manager = Fields_Manager::instance();
		$out = [];

		foreach ((array) $decoded['fields'] as $field) {
			if (! is_array($field) || empty($field['type']) || empty($field['id'])) {
				continue;
			}
			if (empty($eligible_types[ $field['type'] ])) {
				continue; 
			}
			$def = $fields_manager->get((string) $field['type']);
			if (! $def) {
				continue;
			}
			$markup = $def->render_builder($field);
			if ('' !== $markup) {
				$out[ (string) $field['id'] ] = $markup;
			}
		}

		return $out;
	}

	public function ajax_render(): void {
		check_ajax_referer('fcf7_builder', 'nonce');

		if (! current_user_can('wpcf7_edit_contact_forms')) {
			wp_send_json_error([ 'message' => __('Permission denied.', 'compactform') ], 403);
		}

		$schema = json_decode(\CompactForm\Helpers\Utils::raw_post('schema'), true);
		if (! is_array($schema)) {
			$schema = [ 'fields' => [] ];
		}

		$schema = Fields_Manager::instance()->sanitize_schema($schema);

		wp_send_json_success([
			'markup' => Form_Compiler::compile($schema),
		]);
	}
}
