<?php

namespace CompactForm\Extensions;

if (! defined('ABSPATH')) {
    exit;
}

class Default_Mail
{
    private const META_HASH     = '_fcf7_mail_default_hash';
    private const META_SNAPSHOT = '_fcf7_mail_default_snapshot';

    private $pending = [];

    public function __construct()
    {
        add_action('fcf7_schema_saved', [ $this, 'flag_schema_saved' ], 10, 1);
        add_action('wpcf7_after_save', [ $this, 'maybe_regenerate' ], 20, 1);
        add_action('admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ]);
        add_action('wp_ajax_fcf7_default_mail_set_template', [ $this, 'ajax_set_template' ]);
    }

    public function flag_schema_saved(int $form_id): void
    {
        $this->pending[ $form_id ] = true;
    }

    /**
     * @param \WPCF7_ContactForm $contact_form
     */
    public function maybe_regenerate($contact_form): void
    {
        if (! $contact_form instanceof \WPCF7_ContactForm) {
            return;
        }

        $form_id = (int) $contact_form->id();

        if (empty($this->pending[ $form_id ])) {
            return;
        }
        unset($this->pending[ $form_id ]);

        $tags = $this->scan_raw_tags($form_id);
        if (! $tags) {
            return;
        }

        $title    = (string) $contact_form->title();
        $new_mail = $this->build_mail($form_id, $tags, $title, 'template');
        if (! $new_mail) {
            return;
        }

        $new_mail_2 = $this->build_mail($form_id, $tags, $title, 'template2');

        update_post_meta($form_id, self::META_SNAPSHOT, wp_slash(wp_json_encode([
            'mail'   => $new_mail,
            'mail_2' => $new_mail_2,
        ])));

        $mail = get_post_meta($form_id, '_mail', true);
        $mail = is_array($mail) ? $mail : [];

        $stored_hash  = get_post_meta($form_id, self::META_HASH, true);
        $current_hash = $mail ? md5(serialize($mail)) : '';

        if ('' === $stored_hash) {
            update_post_meta($form_id, self::META_HASH, $current_hash);
            return;
        }

        if ($stored_hash !== $current_hash) {
            return;
        }

        if ($new_mail === $mail) {
            return;
        }

        update_post_meta($form_id, '_mail', wp_slash($new_mail));
        update_post_meta($form_id, self::META_HASH, md5(serialize($new_mail)));
    }

    public function enqueue_admin_assets(string $hook): void
    {
        if ('toplevel_page_wpcf7' !== $hook) {
            return;
        }

        // Read-only page-context id (which post's admin screen this is), not submitted/processed
        // data — nothing is mutated here, so there's no CSRF surface a nonce would protect.
        $form_id = isset($_GET['post']) ? absint($_GET['post']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (! $form_id) {
            return;
        }

        $snapshot = json_decode((string) get_post_meta($form_id, self::META_SNAPSHOT, true), true);
        $snapshot = is_array($snapshot) ? $snapshot : [];

        $mail_snapshot  = is_array($snapshot['mail'] ?? null) ? $snapshot['mail'] : [];
        $mail2_snapshot = is_array($snapshot['mail_2'] ?? null) ? $snapshot['mail_2'] : [];

        $contact_form = wpcf7_contact_form($form_id);
        if ($contact_form instanceof \WPCF7_ContactForm) {
            $tags = $this->scan_raw_tags($form_id);
            if ($tags) {
                $title          = (string) $contact_form->title();
                $mail_snapshot  = $this->build_mail($form_id, $tags, $title, 'template') ?: $mail_snapshot;
                $mail2_snapshot = $this->build_mail($form_id, $tags, $title, 'template2') ?: $mail2_snapshot;
            }
        }

        if (! $mail_snapshot && ! $mail2_snapshot) {
            return;
        }

        wp_enqueue_script(
            'fcf7-default-mail-admin',
            FCF7_ASSETS . 'js/default-mail-admin.min.js',
            [],
            FCF7_VERSION,
            true
        );

        $editor = wp_enqueue_code_editor([
            'type'       => 'text/html',
            'htmlhint'   => [ 'space-tab-mixed-disabled' => 'space' ],
            'codemirror' => [
                'lineNumbers'     => true,
                'lineWrapping'    => true,
                'indentUnit'      => 4,
                'tabSize'         => 4,
                'styleActiveLine' => true,
            ],
        ]);

        wp_enqueue_style(
            'fcf7-default-mail-admin',
            FCF7_ASSETS . 'css/default-mail-admin.min.css',
            $editor ? [ 'code-editor' ] : [],
            FCF7_VERSION
        );

        $schema  = $this->decoded_schema($form_id);
        $options = array_values(array_map(
            static fn($t) => [ 'id' => $t->get_id(), 'title' => $t->get_title() ],
            \CompactForm\Mail_Templates\Mail_Templates_Manager::instance()->all()
        ));

        wp_localize_script('fcf7-default-mail-admin', 'FCF7DefaultMail', [
            'label'   => __('Autofill', 'compactform'),
            'i18n'    => [
                'fullscreen'     => __('Fullscreen', 'compactform'),
                'exitFullscreen' => __('Exit fullscreen', 'compactform'),
                'preview'        => __('Preview', 'compactform'),
                'closePreview'   => __('Close preview', 'compactform'),
            ],
            'formId'  => $form_id,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('fcf7_builder'),
            'editor'  => is_array($editor) ? $editor : false,
            'boxes'   => [
                'mail'   => $this->box_payload($mail_snapshot, $schema, 'template', $options),
                'mail_2' => $this->box_payload($mail2_snapshot, $schema, 'template2', $options),
            ],
        ]);
    }

    private function box_payload(array $snapshot, array $schema, string $template_key, array $options): array
    {
        return [
            'fields'   => [
                'recipient'          => (string) ($snapshot['recipient'] ?? ''),
                'sender'             => (string) ($snapshot['sender'] ?? ''),
                'subject'            => (string) ($snapshot['subject'] ?? ''),
                'additional_headers' => (string) ($snapshot['additional_headers'] ?? ''),
                'body'               => (string) ($snapshot['body'] ?? ''),
                'attachments'        => (string) ($snapshot['attachments'] ?? ''),
            ],
            'template' => [
                'label'    => __('Template', 'compactform'),
                'selected' => (string) ($schema['configuration']['defaultMail'][ $template_key ] ?? self::DEFAULT_TEMPLATE),
                'options'  => $options,
            ],
        ];
    }

    private const BOX_TEMPLATE_KEYS = [
        'mail'   => 'template',
        'mail_2' => 'template2',
    ];

    public function ajax_set_template(): void
    {
        check_ajax_referer('fcf7_builder', 'nonce');

        if (! current_user_can('wpcf7_edit_contact_forms')) {
            wp_send_json_error([ 'message' => __('Permission denied.', 'compactform') ], 403);
        }

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $box     = isset($_POST['box']) ? sanitize_key(wp_unslash($_POST['box'])) : '';

        if (! isset(self::BOX_TEMPLATE_KEYS[ $box ])) {
            wp_send_json_error([ 'message' => __('Invalid mail box.', 'compactform') ], 400);
        }
        $template_key = self::BOX_TEMPLATE_KEYS[ $box ];

        $template_id  = isset($_POST['template']) ? sanitize_key(wp_unslash($_POST['template'])) : '';
        $contact_form = $form_id ? wpcf7_contact_form($form_id) : null;

        if (! $contact_form instanceof \WPCF7_ContactForm
            || ! \CompactForm\Mail_Templates\Mail_Templates_Manager::instance()->get($template_id)
        ) {
            wp_send_json_error([ 'message' => __('Invalid form or template.', 'compactform') ], 400);
        }

        $schema = $this->decoded_schema($form_id);
        $schema['configuration']['defaultMail'][ $template_key ] = $template_id;
        update_post_meta($form_id, '_fcf7_builder_schema', wp_slash(wp_json_encode($schema)));

        $tags     = $this->scan_raw_tags($form_id);
        $new_mail = $tags ? $this->build_mail($form_id, $tags, (string) $contact_form->title(), $template_key) : [];

        if (! $new_mail) {
            wp_send_json_error([ 'message' => __('Nothing to generate a preview from.', 'compactform') ], 400);
        }

        $snapshot = json_decode((string) get_post_meta($form_id, self::META_SNAPSHOT, true), true);
        $snapshot = is_array($snapshot) ? $snapshot : [];
        $snapshot[ $box ] = $new_mail;
        update_post_meta($form_id, self::META_SNAPSHOT, wp_slash(wp_json_encode($snapshot)));

        wp_send_json_success([
            'fields' => [
                'recipient'          => (string) ($new_mail['recipient'] ?? ''),
                'sender'             => (string) ($new_mail['sender'] ?? ''),
                'subject'            => (string) ($new_mail['subject'] ?? ''),
                'additional_headers' => (string) ($new_mail['additional_headers'] ?? ''),
                'body'               => (string) ($new_mail['body'] ?? ''),
                'attachments'        => (string) ($new_mail['attachments'] ?? ''),
            ],
        ]);
    }

    private const SKIP_BASETYPES = [
        'submit',
        'recaptcha',
        'fcf7_container',
        'fcf7_icon_picker',
        'fcf7_google_recaptcha',
        'fcf7_stripe_payment',
        'fcf7_spam_protection',
    ];

    private const FILE_BASETYPES = [ 'file', 'fcf7_signature' ];

    private const DEFAULT_TEMPLATE = 'html-table';

    /**
     * @param \WPCF7_FormTag[] $tags
     * @param string           $template_key Which schema key names the template
     *                         for this box — `'template'` (Mail) or `'template2'`
     *                         (Mail (2)); see template_for().
     */
    private function build_mail(int $form_id, array $tags, string $form_title, string $template_key = 'template'): array
    {
        $schema = $this->decoded_schema($form_id);
        $labels = $this->field_labels($schema);

        $repeater_groups = $this->repeater_groups($schema);
        $child_group_of  = [];
        foreach ($repeater_groups as $id => $group) {
            foreach ($group['children'] as $child_name) {
                $child_group_of[ $child_name ] = $id;
            }
        }

        $rows           = [];
        $file_tags      = [];
        $email_tag      = '';
        $group_children = [];

        foreach ($tags as $tag) {
            if (! $tag instanceof \WPCF7_FormTag || '' === $tag->name) {
                continue;
            }

            if ('fcf7_repeater' === $tag->basetype) {
                continue;
            }
            if (in_array($tag->basetype, self::SKIP_BASETYPES, true)) {
                continue;
            }

            if (isset($child_group_of[ $tag->name ])) {
                $group_children[ $child_group_of[ $tag->name ] ][] = $tag->name;
                continue;
            }

            if (in_array($tag->basetype, (array) apply_filters('fcf7_default_mail_file_basetypes', self::FILE_BASETYPES), true)) {
                $file_tags[] = $tag->name;
                continue;
            }

            if ('' === $email_tag && 'email' === $tag->basetype) {
                $email_tag = $tag->name;
            }

            $rows[] = [
                'label' => $labels[ $tag->name ] ?? $tag->name,
                'tag'   => $tag->name,
            ];
        }

        foreach ($repeater_groups as $id => $group) {
            $children = $group_children[ $id ] ?? [];
            if (! $children) {
                continue;
            }

            $parts = [];
            foreach ($children as $child_name) {
                $parts[] = ($labels[ $child_name ] ?? $child_name) . ': [' . $child_name . ']';
            }

            $rows[] = [
                'label' => $group['label'],
                'tag'   => '[' . $group['name'] . ']' . "\n"
                    . '#[' . $group['name'] . ':index] ' . implode(' | ', $parts) . "\n"
                    . '[/' . $group['name'] . ']',
                'raw'   => true,
            ];
        }

        if (! $rows && ! $file_tags) {
            return [];
        }

        $template = $this->template_for($schema, $template_key);

        return [
            'active'             => true,
            'subject'            => sprintf(
                /* translators: 1: site name, 2: form title */
                __('%1$s - New Submission from %2$s', 'compactform'),
                '[_site_title]',
                $form_title
            ),
            'sender'             => sprintf('%s <%s>', '[_site_title]', \WPCF7_ContactFormTemplate::from_email()),
            'body'               => $template->render($rows, [ 'form_title' => $form_title ]),
            'recipient'          => '[_site_admin_email]',
            'additional_headers' => $email_tag ? 'Reply-To: [' . $email_tag . ']' : '',
            'attachments'        => implode("\n", array_map(static fn($name) => '[' . $name . ']', $file_tags)),
            'use_html'           => $template->get_use_html() ? 1 : 0,
            'exclude_blank'      => 1,
        ];
    }

    private function decoded_schema(int $form_id): array
    {
        $decoded = json_decode((string) get_post_meta($form_id, '_fcf7_builder_schema', true), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function scan_raw_tags(int $form_id): array
    {
        $form = (string) get_post_meta($form_id, '_form', true);

        return '' === $form ? [] : \WPCF7_FormTagsManager::get_instance()->scan($form);
    }

    private function normalize_field_name(string $name): string
    {
        $name = strtolower($name);

        return trim(preg_replace('/[^a-z0-9_\-]/', '-', $name), '-');
    }

    private function repeater_groups(array $schema): array
    {
        $fields = (array) ($schema['fields'] ?? []);
        $groups = [];

        foreach ($fields as $field) {
            if (! is_array($field) || 'repeater' !== ($field['type'] ?? '')) {
                continue;
            }
            $id = (string) ($field['id'] ?? '');
            if ('' === $id) {
                continue;
            }
            $name = $this->normalize_field_name((string) ($field['name'] ?? ''));
            if ('' === $name) {
                continue;
            }
            $row_title = trim((string) ($field['rowTitle'] ?? ''));
            $groups[ $id ] = [
                'name'     => $name,
                'label'    => '' !== $row_title ? $row_title : ucwords(str_replace([ '-', '_' ], ' ', $name)),
                'children' => [],
            ];
        }

        if (! $groups) {
            return [];
        }

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }
            $parent = (string) ($field['parentId'] ?? '');
            if ('' === $parent || ! isset($groups[ $parent ])) {
                continue;
            }
            $child_name = $this->normalize_field_name((string) ($field['name'] ?? ''));
            if ('' !== $child_name) {
                $groups[ $parent ]['children'][] = $child_name;
            }
        }

        return $groups;
    }

    private function field_labels(array $schema): array
    {
        $labels = [];
        foreach ((array) ($schema['fields'] ?? []) as $field) {
            if (! is_array($field)) {
                continue;
            }
            $name = $this->normalize_field_name((string) ($field['name'] ?? ''));
            if ('' === $name) {
                continue;
            }
            $label = trim((string) ($field['label'] ?? ''));
            if ('' !== $label) {
                $labels[ $name ] = $label;
            }
        }

        return $labels;
    }

    private function template_for(array $schema, string $template_key = 'template'): \CompactForm\Mail_Templates\Base_Mail_Template
    {
        $id      = (string) ($schema['configuration']['defaultMail'][ $template_key ] ?? '');
        $manager = \CompactForm\Mail_Templates\Mail_Templates_Manager::instance();
        $all     = $manager->all();

        return $manager->get($id) ?? $manager->get(self::DEFAULT_TEMPLATE) ?? reset($all);
    }
}
