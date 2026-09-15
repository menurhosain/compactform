<?php

namespace CompactForm\Extensions;

use CompactForm\Builder\Fields_Manager;
use CompactForm\Builder\Abstracts\Extension_Field;

if (! defined('ABSPATH')) {
    exit;
}

require_once FCF7_INCLUDES . '/extensions/partials/form-builder/parser.php';
require_once FCF7_INCLUDES . '/builder/class-builder.php';
require_once FCF7_INCLUDES . '/builder/class-conditions.php';

class Form_Builder
{
    public const META_SCHEMA  = '_fcf7_builder_schema';
    public const META_ENABLED = '_fcf7_builder_enabled';

    public const CSS_SUBDIR = 'compactform/forms';

    protected static $instance = null;
    private static bool $fanning = false;

    private array $enqueued_forms = [];
    private array $scanned_forms = [];
    private array $extension_style_handles = [];
    private bool $multistep_localized = false;
    private bool $multi_select_localized = false;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        self::$instance = $this;

        \CompactForm\Builder\Builder::instance();

        add_action('fcf7_fan_out_start', [ $this, 'fan_out_started' ]);
        add_action('fcf7_fan_out_end', [ $this, 'fan_out_ended' ]);

        add_filter('wpcf7_editor_panels', [ $this, 'add_panel' ]);

        add_action('admin_enqueue_scripts', [ $this, 'enqueue' ]);

        add_action('wpcf7_save_contact_form', [ $this, 'compile_on_save' ], 10, 1);
        add_action('save_post_wpcf7_contact_form', [ $this, 'store_schema_meta' ], 10, 1);

        add_action('wp_ajax_fcf7_builder_save', [ $this, 'ajax_save' ]);

        add_action('wp_ajax_fcf7_builder_create_form', [ $this, 'ajax_create_form' ]);

        add_action('wp_ajax_fcf7_builder_enable', [ $this, 'ajax_enable' ]);

        add_action('wp_ajax_fcf7_builder_icons', [ $this, 'ajax_icons' ]);

        add_action('wp_enqueue_scripts', [ $this, 'register_frontend_assets' ]);
        add_action('wp_enqueue_scripts', [ $this, 'enqueue_content_form_styles' ], 20);
        add_filter('wpcf7_form_elements', [ $this, 'on_form_render' ]);

        add_filter('wpcf7_autop_or_not', [ $this, 'disable_autop_for_builder_form' ], 10, 2);

        add_filter('wpcf7_posted_data', [ $this, 'strip_hidden_conditional_data' ]);
        add_filter('wpcf7_validate', [ $this, 'skip_hidden_conditional_validation' ], 20, 2);

        add_action('wp_ajax_fcf7_validate_step', [ $this, 'ajax_validate_step' ]);
        add_action('wp_ajax_nopriv_fcf7_validate_step', [ $this, 'ajax_validate_step' ]);

        add_filter('wpcf7_ajax_json_echo', [ $this, 'add_redirect_url' ], 10, 2);
    }

    public function add_panel($panels)
    {
        $ours = [
            'fcf7-form-builder' => [
                'title'    => __('Visual Editor', 'compactform'),
                'callback' => [ $this, 'render_panel' ],
            ],
        ];
        $panels = (array) $panels;

        if ($this->is_enabled($this->current_form_id())) {
            unset($panels['form-panel']);
        }

        return $ours + $panels;
    }

    protected function current_form_id(): int
    {
        // Read-only page-context id (which post's admin screen this is), not submitted/processed
        // data — nothing is mutated here, so there's no CSRF surface a nonce would protect.
        return isset($_GET['post']) ? absint($_GET['post']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    public function is_enabled(int $form_id): bool
    {
        if (! $form_id) {
            // Read-only opt-in query flag, not submitted/processed data — nothing is mutated
            // here, so there's no CSRF surface a nonce would protect.
            return isset($_GET['fcf7-builder']) && '1' === sanitize_text_field(wp_unslash($_GET['fcf7-builder'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        return '1' === (string) get_post_meta($form_id, self::META_ENABLED, true);
    }

    public function render_panel($post)
    {
        $form_id = $post ? (int) $post->id() : 0;

        if (! $this->is_enabled($form_id)) {
            $this->render_optin($form_id);
            return;
        }

        $schema = $this->get_schema_for($post);
        printf(
            '<div id="fcf7-builder-root" class="fcf7-builder-root"><div class="fcf7b-boot">%s</div></div>',
            esc_html__('Loading builder…', 'compactform')
        );
        printf(
            '<input type="hidden" name="fcf7-builder-schema" id="fcf7-builder-schema-input" value="%s" />',
            esc_attr(wp_json_encode($schema))
        );

        printf(
            '<textarea name="wpcf7-form" id="fcf7-builder-cf7-form" hidden>%s</textarea>',
            esc_textarea((string) ($post ? $post->prop('form') : ''))
        );
    }

    protected function render_optin(int $form_id): void
    {
        $new_form_url = add_query_arg('fcf7-builder', '1');
        $nonce        = wp_create_nonce('fcf7_builder_enable');
        ?>
        <div class="fcf7b-optin-stage">
            <div class="fcf7b-glimpse" aria-hidden="true">
                <div class="fcf7b-glimpse-toolbar"><span></span><span></span><span></span><span></span></div>
                <div class="fcf7b-glimpse-body">
                    <div class="fcf7b-glimpse-fields"><?php echo wp_kses( str_repeat('<i></i>', 8), [ 'i' => [] ] ); ?></div>
                    <div class="fcf7b-glimpse-canvas">
                        <div><b></b><u></u></div>
                        <div class="fcf7b-glimpse-row">
                            <div><b></b><u></u></div>
                            <div><b></b><u></u></div>
                        </div>
                        <div><b></b><u></u></div>
                        <div class="fcf7b-glimpse-cta"></div>
                    </div>
                    <div class="fcf7b-glimpse-panel">
                        <div></div><div class="fcf7b-glimpse-input"></div><div></div><div class="fcf7b-glimpse-input"></div><div></div>
                    </div>
                </div>
            </div>
        <div class="fcf7b-optin">
            <h2><?php esc_html_e('Build this form visually', 'compactform'); ?></h2>
            <p class="fcf7b-optin-lead"><?php esc_html_e('Design fields, layout and styling by dragging — no tags to write.', 'compactform'); ?></p>
            <?php if ($form_id) : ?>
                <button type="button" class="button button-primary" id="fcf7b-enable-builder"
                        data-form-id="<?php echo esc_attr((string) $form_id); ?>"
                        data-nonce="<?php echo esc_attr($nonce); ?>">
                    <?php esc_html_e('Edit with Builder', 'compactform'); ?>
                </button>
            <?php else : ?>
                <a class="button button-primary" href="<?php echo esc_url( $new_form_url ); ?>">
                    <?php esc_html_e('Edit with Builder', 'compactform'); ?>
                </a>
            <?php endif; ?>
            <p class="fcf7b-optin-note">
                <?php esc_html_e('Existing tags are imported as closely as possible; custom HTML, layout markup and other plugins’ tags may not carry over. Copy anything you need from the Form tab first — the builder regenerates this form’s markup on every save.', 'compactform'); ?>
            </p>
            <p class="fcf7b-optin-note"><?php esc_html_e('Asked once per form.', 'compactform'); ?></p>
        </div>
        </div>
        <?php
    }

    protected function enqueue_optin_assets(): void
    {
        wp_enqueue_style('fcf7b-optin', FCF7_ASSETS . 'css/builder-optin.min.css', [], FCF7_VERSION);

        wp_register_script('fcf7b-optin', false, [], FCF7_VERSION, true);
        wp_enqueue_script('fcf7b-optin');
        wp_add_inline_script(
            'fcf7b-optin',
            '(function(){var btn=document.getElementById("fcf7b-enable-builder");if(!btn){return;}'
            . 'btn.addEventListener("click",function(){btn.disabled=true;'
            . 'var body=new FormData();body.append("action","fcf7_builder_enable");'
            . 'body.append("form_id",btn.dataset.formId);body.append("nonce",btn.dataset.nonce);'
            . 'fetch(ajaxurl,{method:"POST",credentials:"same-origin",body:body})'
            . '.then(function(r){return r.json();}).then(function(res){'

            . 'if(res&&res.success){window.location.reload();return;}'
            . 'btn.disabled=false;window.alert((res&&res.data&&res.data.message)||'
            . wp_json_encode(__('Could not switch this form to the builder.', 'compactform'))
            . ');}).catch(function(){btn.disabled=false;});});})();'
        );
    }

    public function ajax_enable()
    {
        check_ajax_referer('fcf7_builder_enable', 'nonce');

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

        if (! $form_id || ! current_user_can('wpcf7_edit_contact_form', $form_id)) {
            wp_send_json_error([ 'message' => __('Permission denied.', 'compactform') ], 403);
        }

        update_post_meta($form_id, self::META_ENABLED, '1');

        wp_send_json_success();
    }

    public function enqueue($hook)
    {
        if (false === strpos((string) $hook, 'wpcf7')) {
            return;
        }

        if (! $this->is_enabled($this->current_form_id())) {
            if ($this->current_form_id() || false !== strpos((string) $hook, 'wpcf7-new')) {
                $this->enqueue_optin_assets();
            }
            return;
        }

        $code_editor = false;
        if (function_exists('wp_enqueue_code_editor')) {
            $code_editor = wp_enqueue_code_editor([
                'type'       => 'text/html',

                'codemirror' => [
                    'mode'          => 'htmlmixed',
                    'lineNumbers'   => true,
                    'lineWrapping'  => true,
                    'autoCloseTags' => true,
                    'matchTags'     => [ 'bothTags' => true ],
                ],
            ]);
        }

        wp_enqueue_style('fcf7b-editor', FCF7_ASSETS . 'editor/index.css', $code_editor ? [ 'code-editor' ] : [], FCF7_VERSION);

        wp_enqueue_style('fcf7-remixicon');

        if (defined('WPCF7_PLUGIN_URL') && function_exists('wpcf7_plugin_url')) {
            wp_enqueue_style('contact-form-7', wpcf7_plugin_url('includes/css/styles.css'), [], defined('WPCF7_VERSION') ? WPCF7_VERSION : FCF7_VERSION);
        }
        wp_enqueue_style('fcf7-container', FCF7_ASSETS . 'css/container.min.css', [], FCF7_VERSION);

        $this->enqueue_preview_styles();

        $editor_asset = FCF7_PATH . 'assets/editor/index.asset.php';
        $editor_asset_data = file_exists($editor_asset) ? include $editor_asset : [ 'dependencies' => [ 'wp-element' ], 'version' => FCF7_VERSION ];
        wp_enqueue_script('fcf7b-builder', FCF7_ASSETS . 'editor/index.js', $editor_asset_data['dependencies'], $editor_asset_data['version'], true);
        wp_set_script_translations('fcf7b-builder', 'compactform', FCF7_PATH . 'languages');

        // Read-only page-context id (which post's admin screen this is), not submitted/processed
        // data — nothing is mutated here, so there's no CSRF surface a nonce would protect.
        $form_id = isset($_GET['post']) ? absint($_GET['post']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        wp_localize_script('fcf7b-builder', 'FCF7Builder', \CompactForm\Builder\Builder::instance()->localized_data($form_id));

        wp_localize_script('fcf7b-builder', 'FCF7CodeEditor', [
            'settings' => $code_editor ? $code_editor : null,
        ]);
    }

    private function enqueue_preview_styles(): void
    {
        $manager = Fields_Manager::instance();

        foreach ($manager->get_types() as $type) {
            $field = $manager->get($type);

            if ($field instanceof Extension_Field) {
                $field->enqueue_preview_styles();

                $field->enqueue_preview_scripts();
            }
        }
    }

    public function ajax_icons()
    {
        check_ajax_referer('fcf7_builder', 'nonce');

        if (! current_user_can('wpcf7_edit_contact_forms')) {
            wp_send_json_error([ 'message' => __('Permission denied.', 'compactform') ], 403);
        }

        $all    = $this->icon_classes();
        $search = isset($_GET['search']) ? strtolower(sanitize_text_field(wp_unslash($_GET['search']))) : '';
        $offset = isset($_GET['offset']) ? absint($_GET['offset']) : 0;
        $limit  = isset($_GET['limit']) ? absint($_GET['limit']) : 120;
        $limit  = max(1, min(200, $limit));

        if ('' !== $search) {
            $all = array_values(array_filter(
                $all,
                function ($c) use ($search) {
                    return false !== strpos($c, $search);
                }
            ));
        }

        $total = count($all);
        $page  = array_slice($all, $offset, $limit);
        $next  = $offset + count($page);

        wp_send_json_success([
            'icons'      => $page,
            'total'      => $total,
            'nextOffset' => $next,
            'hasMore'    => $next < $total,
        ]);
    }

    protected function icon_classes()
    {
        static $classes = null;
        if (null !== $classes) {
            return $classes;
        }

        $classes = [];
        $file    = FCF7_PATH . 'assets/vendor/remixicon/remixicon-icons.json';

        if (is_readable($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (is_array($decoded)) {
                $classes = array_values(array_filter(array_map('strval', $decoded)));
            }
        }

        return $classes;
    }

    public function register_frontend_assets()
    {
        wp_register_style('fcf7b-common', FCF7_ASSETS . 'css/form-builder.min.css', [], FCF7_VERSION);

        wp_register_script('fcf7b-conditional', FCF7_ASSETS . 'js/conditional.min.js', [], FCF7_VERSION, true);

        wp_register_script('fcf7b-multistep', FCF7_ASSETS . 'js/multistep.min.js', [], FCF7_VERSION, true);

        wp_register_script('fcf7-submit-btn-handler', FCF7_ASSETS . 'js/submit.min.js', [], FCF7_VERSION, true);
        wp_register_style('fcf7-multi-select', FCF7_ASSETS . 'css/multi-select.min.css', [], FCF7_VERSION);
        wp_register_script('fcf7-multi-select', FCF7_ASSETS . 'js/multi-select.min.js', [], FCF7_VERSION, true);
    }

    public function enqueue_content_form_styles(): void
    {
        $post_id = get_queried_object_id();
        $content = $post_id ? (string) get_post_field('post_content', $post_id) : '';
        if ('' === $content) {
            return;
        }

        $ids = [];

        if (preg_match_all('/' . get_shortcode_regex([ 'contact-form-7' ]) . '/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $atts = shortcode_parse_atts($m[3]);
                $ids[] = $this->resolve_form_id($atts['id'] ?? '', $atts['title'] ?? '');
            }
        }

        if (preg_match_all('#contact-form-7/contact-form-selector[^}]*?"id"\s*:\s*"?(\d+)#i', $content, $m)) {
            $ids = array_merge($ids, array_map('absint', $m[1]));
        }

        foreach (array_unique(array_filter(array_map('absint', $ids))) as $form_id) {
            $extra_deps = $this->enqueue_extension_assets($form_id);
            $this->enqueue_form_style($form_id, $extra_deps);
        }
    }

    protected function enqueue_extension_assets(int $form_id): array
    {
        if (! $form_id) {
            return [];
        }
        if (isset($this->scanned_forms[ $form_id ])) {
            return $this->extension_style_handles[ $form_id ] ?? [];
        }
        $this->scanned_forms[ $form_id ] = true;

        if (! function_exists('wpcf7_contact_form')) {
            return [];
        }
        $contact_form = wpcf7_contact_form($form_id);
        if (! $contact_form) {
            return [];
        }

        $handles = [];

        $manager = Fields_Manager::instance();
        foreach ($contact_form->scan_form_tags() as $tag) {
            $field = $manager->get($tag->basetype);
            if ($field instanceof Extension_Field) {
                $handles = array_merge($handles, $field->enqueue_frontend_assets());
            }
        }

        if (false !== strpos((string) $contact_form->prop('form'), 'fcf7-field-icon')) {
            wp_enqueue_style('fcf7-remixicon');
            $handles[] = 'fcf7-remixicon';
        }

        $handles = array_values(array_unique($handles));
        $this->extension_style_handles[ $form_id ] = $handles;

        return $handles;
    }

    protected function resolve_form_id(string $id, string $title = ''): int
    {
        $id = trim($id);

        if ('' !== $id && function_exists('wpcf7_get_contact_form_by_hash')) {
            $form = wpcf7_get_contact_form_by_hash($id);
            if ($form) {
                return (int) $form->id();
            }
        }
        if (is_numeric($id) && function_exists('wpcf7_contact_form')) {
            $form = wpcf7_contact_form((int) $id);
            if ($form) {
                return (int) $form->id();
            }
        }
        if ('' !== $title && function_exists('wpcf7_get_contact_form_by_title')) {
            $form = wpcf7_get_contact_form_by_title($title);
            if ($form) {
                return (int) $form->id();
            }
        }
        return 0;
    }

    public function disable_autop_for_builder_form($autop, $options = [])
    {
        $for = (is_array($options) && isset($options['for'])) ? (string) $options['for'] : 'form';
        if ('form' !== $for) {
            return $autop;
        }

        $contact_form = \WPCF7_ContactForm::get_current();
        if (! $contact_form || ! $this->is_enabled((int) $contact_form->id())) {
            return $autop;
        }

        return false;
    }

    public function on_form_render($html)
    {
        $form    = function_exists('wpcf7_get_current_contact_form') ? wpcf7_get_current_contact_form() : null;
        $form_id = $form ? (int) $form->id() : 0;

        if ($form_id && ! isset($this->enqueued_forms[ $form_id ]) && get_post_meta($form_id, self::META_SCHEMA, true)) {
            $this->enqueued_forms[ $form_id ] = true;
            wp_enqueue_style('fcf7b-common');
            $css = $this->get_form_css($form_id);
            if ('' !== $css) {
                $handle = 'fcf7b-form-' . $form_id;
                wp_register_style($handle, false, [ 'fcf7b-common' ], FCF7_VERSION);
                wp_enqueue_style($handle);
                wp_add_inline_style($handle, wp_strip_all_tags($css));
            }
        }

        if (false !== strpos($html, 'fcf7-field-icon')) {
            wp_enqueue_style('fcf7-remixicon');
        }

        if (false !== strpos($html, 'data-fcf7-cond')) {
            wp_enqueue_script('fcf7b-conditional');
        }

        if (false !== strpos($html, 'data-fcf7-multistep')) {
            wp_enqueue_script('fcf7b-multistep');
            if (! $this->multistep_localized) {
                $this->multistep_localized = true;
                wp_localize_script('fcf7b-multistep', 'FCF7Multistep', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce'   => wp_create_nonce('fcf7_builder'),
                ]);
            }
        }

        if (false !== strpos($html, 'wpcf7-submit')) {
            wp_enqueue_script('fcf7-submit-btn-handler');
        }

        if (preg_match('/<select[^>]*\smultiple[\s=>]/i', $html)) {
            wp_enqueue_style('fcf7-multi-select');
            wp_enqueue_script('fcf7-multi-select');

            if (! $this->multi_select_localized) {
                $this->multi_select_localized = true;
                wp_localize_script('fcf7-multi-select', 'FCF7MultiSelect', [
                    'placeholder' => __('Select options…', 'compactform'),
                    'remove'      => __('Remove', 'compactform'),
                ]);
            }
        }

        return $html;
    }

    protected function hidden_conditional_fields(array $posted): array
    {
        $form = function_exists('wpcf7_get_current_contact_form') ? wpcf7_get_current_contact_form() : null;
        if (! $form) {
            return [];
        }
        $schema = json_decode((string) get_post_meta((int) $form->id(), self::META_SCHEMA, true), true);
        if (! is_array($schema)) {
            return [];
        }
        return \CompactForm\Builder\Conditions::hidden_field_names($schema, $posted);
    }

    public function strip_hidden_conditional_data($posted_data)
    {
        foreach ($this->hidden_conditional_fields((array) $posted_data) as $name) {
            $posted_data[ $name ] = is_array($posted_data[ $name ] ?? '') ? [] : '';
        }
        return $posted_data;
    }

    public function get_redirect_url_for_current_form()
    {
        $form = function_exists('wpcf7_get_current_contact_form') ? wpcf7_get_current_contact_form() : null;

        if (! $form) {
            return '';
        }

        $schema = json_decode(
            (string) get_post_meta((int) $form->id(), self::META_SCHEMA, true),
            true
        );

        if (! is_array($schema)) {
            return '';
        }

        $posted = [];
        if (class_exists('\WPCF7_Submission')) {
            $submission = \WPCF7_Submission::get_instance();
            if ($submission) {
                $posted = (array) $submission->get_posted_data();
            }
        }

        foreach ((array) ($schema['fields'] ?? []) as $field) {
            if (! is_array($field) || empty($field['is_redirect_enable'])) {
                continue;
            }

            $url      = '';
            $location = $field['redirect_page_location'] ?? '';

            if ('internal' === $location && ! empty($field['redirect_page_internal'])) {
                $page = get_page_by_path(sanitize_title($field['redirect_page_internal']));

                if ($page instanceof \WP_Post) {
                    $url = get_permalink($page);
                }
            }

            if ('external' === $location && ! empty($field['redirect_page_external'])) {
                $url = esc_url_raw($field['redirect_page_external']);
            }

            $url = (string) apply_filters('fcf7_submit_redirect_url', $url, $field, $posted, $form);

            if ('' !== $url) {
                return $url;
            }
        }

        return '';
    }

    public function add_redirect_url($data, $result)
    {
        if (empty($result['status']) || 'mail_sent' !== $result['status']) {
            return $data;
        }
        $url = $this->get_redirect_url_for_current_form();
        if ('' !== $url) {
            $data['redirectUrl'] = $url;
        }
        return $data;
    }

    public function ajax_validate_step(): void
    {
        check_ajax_referer('fcf7_builder', 'nonce');

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $step_id = isset($_POST['step_id']) ? sanitize_text_field(wp_unslash($_POST['step_id'])) : '';

        $contact_form = $form_id ? wpcf7_contact_form($form_id) : null;
        if (! $contact_form) {
            wp_send_json_error([ 'message' => __('Invalid form.', 'compactform') ], 400);
        }

        $future_names = $this->future_step_field_names_for($contact_form, $step_id);

        $skip_future = function ($result, $tags) use ($future_names) {
            return $this->skip_future_step_validation($result, $future_names);
        };
        add_filter('wpcf7_validate', $skip_future, 20, 2);

        // File-uploading tags (Signature, native File) never reach `wpcf7_validate` — CF7
        // core validates them in its own unship_uploaded_files() pass instead, which has no
        // aggregate filter to rebuild against. It does run `wpcf7_validate_{$tag->type}` per
        // tag though (after already invalidating), so hook that directly for every
        // file-uploading type this form actually uses — discovered from CF7 itself, not a
        // hand-written list, so a Pro file-uploading field is covered for free.
        $skip_future_file = function ($result, $tag, $args = []) use ($future_names) {
            return $this->skip_future_step_file_validation($result, $tag, $future_names);
        };
        $file_tag_types = $future_names ? $this->file_uploading_tag_types($contact_form) : [];
        foreach ($file_tag_types as $type) {
            add_filter("wpcf7_validate_{$type}", $skip_future_file, 20, 3);
        }

        $result = \CompactForm\Helpers\Utils::dry_run_validate($contact_form);

        remove_filter('wpcf7_validate', $skip_future, 20);
        foreach ($file_tag_types as $type) {
            remove_filter("wpcf7_validate_{$type}", $skip_future_file, 20);
        }

        if (! empty($result['invalid_fields'])) {
            wp_send_json_error([

                'invalid_fields' => \CompactForm\Helpers\Utils::format_invalide_fields_msg(
                    $result['invalid_fields'],
                    isset($_POST['_wpcf7_unit_tag']) ? sanitize_text_field(wp_unslash($_POST['_wpcf7_unit_tag'])) : ''
                ),
            ]);
        }

        wp_send_json_success();
    }

    protected function future_step_field_names_for($contact_form, string $step_id): array
    {
        if ('' === $step_id) {
            return [];
        }

        $schema = json_decode((string) get_post_meta((int) $contact_form->id(), self::META_SCHEMA, true), true);
        if (! is_array($schema)) {
            return [];
        }

        return $this->future_step_field_names($schema, $step_id);
    }

    protected function skip_future_step_validation($result, array $future_names)
    {
        if (! $future_names || ! class_exists('\WPCF7_Validation')) {
            return $result;
        }

        $invalid = $result->get_invalid_fields();
        if (! is_array($invalid) || ! $invalid) {
            return $result;
        }

        $future_norm = array_map(static function ($n) {
            return rtrim($n, '[]');
        }, $future_names);

        $clean = new \WPCF7_Validation();
        foreach ($invalid as $key => $data) {
            $norm = rtrim($key, '[]');

            $base = preg_replace('/__\d+$/', '', $norm);

            if (in_array($norm, $future_norm, true) || in_array($base, $future_norm, true)) {
                continue;
            }
            $clean->invalidate($key, $data['reason']);
        }
        return $clean;
    }

    /** Every tag type in this form whose tag carries CF7's 'file-uploading' feature. */
    protected function file_uploading_tag_types($contact_form): array
    {
        if (! method_exists($contact_form, 'scan_form_tags')) {
            return [];
        }

        $types = [];
        foreach ($contact_form->scan_form_tags(['feature' => 'file-uploading']) as $tag) {
            $types[(string) $tag->type] = true;
        }
        return array_keys($types);
    }

    /**
     * Runs on `wpcf7_validate_{$tag->type}` for a file-uploading tag, AFTER CF7 core (or the
     * field's own filter, e.g. Signature) has already invalidated it — there is no earlier,
     * pre-invalidate hook for the native `file`/`file*` tag, whose required-check runs inside
     * WPCF7_Submission::unship_uploaded_files() before any filter fires. Same rebuild-minus
     * trick as skip_future_step_validation(), scoped to just this one tag.
     */
    protected function skip_future_step_file_validation($result, $tag, array $future_names)
    {
        if (! $future_names || ! class_exists('\WPCF7_Validation') || ! is_object($tag)) {
            return $result;
        }

        $name = (string) ($tag->name ?? '');
        if ('' === $name || ! in_array($name, $future_names, true) || $result->is_valid($name)) {
            return $result;
        }

        $clean = new \WPCF7_Validation();
        foreach ($result->get_invalid_fields() as $key => $data) {
            if ($key === $name) {
                continue;
            }
            $clean->invalidate($key, $data['reason']);
        }
        return $clean;
    }

    protected function future_step_field_names(array $schema, string $step_id): array
    {
        $names = [];

        foreach ((array) ($schema['fields'] ?? []) as $field) {
            if (! is_array($field) || 'multistep' !== ($field['type'] ?? '')) {
                continue;
            }

            $steps = is_array($field['steps'] ?? null) ? $field['steps'] : [];
            $index = null;
            foreach ($steps as $i => $step) {
                if ((string) ($step['id'] ?? '') === $step_id) {
                    $index = $i;
                    break;
                }
            }
            if (null === $index) {
                continue;
            }

            $future_step_ids = [];
            foreach ($steps as $i => $step) {
                if ($i > $index) {
                    $future_step_ids[] = (string) ($step['id'] ?? '');
                }
            }
            if (! $future_step_ids) {
                continue;
            }

            $my_id = (string) ($field['id'] ?? '');
            foreach ((array) ($schema['fields'] ?? []) as $sibling) {
                if (! is_array($sibling)) {
                    continue;
                }
                if ((string) ($sibling['parentId'] ?? '') !== $my_id) {
                    continue;
                }
                if (! in_array((string) ($sibling['stepId'] ?? ''), $future_step_ids, true)) {
                    continue;
                }
                $name = (string) ($sibling['name'] ?? '');
                if ('' !== $name) {
                    $names[] = $name;
                }

                $names = array_merge($names, $this->container_child_names($schema, (string) ($sibling['id'] ?? '')));
            }
        }

        return array_values(array_unique($names));
    }

    protected function container_child_names(array $schema, string $container_id): array
    {
        $names = [];

        if ('' === $container_id) {
            return $names;
        }

        foreach ((array) ($schema['fields'] ?? []) as $field) {
            if (! is_array($field) || (string) ($field['parentId'] ?? '') !== $container_id) {
                continue;
            }
            $name = (string) ($field['name'] ?? '');
            if ('' !== $name) {
                $names[] = $name;
            }
            $names = array_merge($names, $this->container_child_names($schema, (string) ($field['id'] ?? '')));
        }

        return $names;
    }

    public function skip_hidden_conditional_validation($result, $tags)
    {
        if (! class_exists('\WPCF7_Submission') || ! class_exists('\WPCF7_Validation')) {
            return $result;
        }
        $submission = \WPCF7_Submission::get_instance();
        $posted     = $submission ? (array) $submission->get_posted_data() : [];
        $hidden     = $this->hidden_conditional_fields($posted);
        if (empty($hidden)) {
            return $result;
        }

        $invalid = $result->get_invalid_fields();
        if (! is_array($invalid) || ! $invalid) {
            return $result;
        }

        $hidden_norm = array_map(static function ($n) {
            return rtrim($n, '[]');
        }, $hidden);

        $clean = new \WPCF7_Validation();
        foreach ($invalid as $key => $data) {
            if (! in_array(rtrim($key, '[]'), $hidden_norm, true)) {
                $clean->invalidate($key, $data['reason']);
            }
        }
        return $clean;
    }

    protected static function minify_css(string $css): string
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $css);
        $css = preg_replace('/\s+/', ' ', (string) $css);
        $css = preg_replace('/\s*([{}:;,>])\s*/', '$1', (string) $css);
        $css = str_replace(';}', '}', (string) $css);
        return trim((string) $css);
    }

    protected function get_form_css(int $form_id): string
    {
        $css = '';

        if (wp_style_is('fcf7b-form-' . $form_id, 'enqueued') || wp_style_is('fcf7b-form-' . $form_id, 'done')) {
            return $css;
        }

        $file = basename((string) get_post_meta($form_id, '_fcf7_builder_css_file', true));
        if ('' !== $file) {
            $uploads = wp_upload_dir();
            $path    = trailingslashit($uploads['basedir']) . self::CSS_SUBDIR . '/' . $file;
            if (file_exists($path)) {
                return self::minify_css((string) file_get_contents($path));
            }
        }

        $inline = (string) get_post_meta($form_id, '_fcf7_builder_css_inline', true);
        if ('' !== $inline) {
            $css = trim($inline);
        }

        return $css;
    }

    protected function enqueue_form_style(int $form_id, array $extra_deps = []): void
    {
        if (! $form_id || isset($this->enqueued_forms[ $form_id ])) {
            return;
        }

        if (! get_post_meta($form_id, self::META_SCHEMA, true)) {
            return;
        }
        $this->enqueued_forms[ $form_id ] = true;

        wp_enqueue_style('fcf7b-common');

        $handle = 'fcf7b-form-' . $form_id;
        $deps   = array_values(array_unique(array_merge([ 'fcf7b-common' ], $extra_deps)));
        $file   = basename((string) get_post_meta($form_id, '_fcf7_builder_css_file', true));

        if ('' !== $file) {
            $uploads = wp_upload_dir();
            $path    = trailingslashit($uploads['basedir']) . self::CSS_SUBDIR . '/' . $file;
            if (file_exists($path)) {
                wp_enqueue_style(
                    $handle,
                    trailingslashit($uploads['baseurl']) . self::CSS_SUBDIR . '/' . rawurlencode($file),
                    $deps,
                    (string) filemtime($path)
                );
                return;
            }
        }

        $inline = (string) get_post_meta($form_id, '_fcf7_builder_css_inline', true);
        if ('' !== $inline) {
            wp_register_style($handle, false, $deps, FCF7_VERSION);
            wp_enqueue_style($handle);
            wp_add_inline_style($handle, wp_strip_all_tags($inline));
        }
    }

    protected function generate_form_css(int $form_id, array $schema): void
    {
        $fields   = \CompactForm\Builder\Fields_Manager::instance();
        $controls = \CompactForm\Builder\Controls_Manager::instance();
        $configs  = [];
        $buckets  = [];
        $breaks   = $this->breakpoints($schema);

        foreach ((array) ($schema['fields'] ?? []) as $field) {
            if (! is_array($field)) {
                continue;
            }
            $field_id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($field['id'] ?? ''));
            $type     = (string) ($field['type'] ?? '');
            if ('' === $field_id || '' === $type) {
                continue;
            }
            if (! isset($configs[ $type ])) {
                $def = $fields->get($type);
                $cfg = $def ? $def->get_config() : [ 'controls' => [], 'sections' => [], 'popovers' => [] ];

                $by_id = static function (array $rows): array {
                    $out = [];
                    foreach ($rows as $row) {
                        if (isset($row['id'])) {
                            $out[ $row['id'] ] = $row;
                        }
                    }
                    return $out;
                };
                $configs[ $type ] = [
                    'controls'   => $cfg['controls'] ?? [],
                    'sections'   => $by_id($cfg['sections'] ?? []),
                    'popovers'   => $by_id($cfg['popovers'] ?? []),
                    'tab_groups' => $by_id($cfg['tab_groups'] ?? []),
                    'tab_items'  => $by_id($cfg['tab_items'] ?? []),
                ];
            }
            $type_controls = $configs[ $type ]['controls'];

            $scope   = '.wpcf7[data-wpcf7-id="' . $form_id . '"] ';
            $wrapper = $scope . '.fcf7b-field-' . $field_id;

            foreach ($type_controls as $ctrl) {
                $key = $ctrl['key'] ?? '';
                if ('' === $key || ! array_key_exists($key, $field)) {
                    continue;
                }

                $popover = (string) ($ctrl['popover'] ?? '');
                if ('' !== $popover && empty($field[ $popover ])) {
                    continue;
                }

                $section_cfg = $configs[ $type ]['sections'][ $ctrl['section'] ?? '' ] ?? null;
                if ($section_cfg && ! empty($section_cfg['condition'])
                    && ! self::condition_passes($section_cfg['condition'], $field, $type_controls, 'desktop')
                ) {
                    continue;
                }
                $popover_cfg = '' !== $popover ? ($configs[ $type ]['popovers'][ $popover ] ?? null) : null;
                if ($popover_cfg && ! empty($popover_cfg['condition'])
                    && ! self::condition_passes($popover_cfg['condition'], $field, $type_controls, 'desktop')
                ) {
                    continue;
                }

                $tab_group_cfg = $configs[ $type ]['tab_groups'][ $ctrl['tab_group'] ?? '' ] ?? null;
                if ($tab_group_cfg && ! empty($tab_group_cfg['condition'])
                    && ! self::condition_passes($tab_group_cfg['condition'], $field, $type_controls, 'desktop')
                ) {
                    continue;
                }
                $tab_item_cfg = $configs[ $type ]['tab_items'][ $ctrl['tab_item'] ?? '' ] ?? null;
                if ($tab_item_cfg && ! empty($tab_item_cfg['condition'])
                    && ! self::condition_passes($tab_item_cfg['condition'], $field, $type_controls, 'desktop')
                ) {
                    continue;
                }

                if (! empty($ctrl['selectors']) && is_array($ctrl['selectors'])) {
                    $targets = $ctrl['selectors'];
                } elseif (! empty($ctrl['selector'])) {
                    $targets = [ (string) $ctrl['selector'] => '' ];
                } else {
                    continue;
                }

                $control = $controls->get((string) ($ctrl['type'] ?? ''));
                if (! $control) {
                    continue;
                }

                $devices = array_merge([ 'desktop' ], array_keys($breaks));

                foreach ($targets as $sel => $template) {
                    $selector = str_replace('{{WRAPPER}}', $wrapper, (string) $sel);

                    $by_device = $control->get_css_devices($field[ $key ], (string) $template, $devices);

                    if (null === $by_device) {
                        $by_device = [];
                        foreach ($this->value_per_device($field[ $key ], $ctrl, $breaks) as $device => $value) {
                            $by_device[ $device ] = $control->get_css($value, (string) $template);
                        }
                    }

                    foreach ($by_device as $device => $decl) {
                        if ('' === trim((string) $decl)) {
                            continue;
                        }

                        if (! empty($ctrl['condition'])
                            && ! self::condition_passes($ctrl['condition'], $field, $type_controls, $device)
                        ) {
                            continue;
                        }
                        $buckets[ $device ][ $selector ] = array_merge(
                            $buckets[ $device ][ $selector ] ?? [],
                            $this->parse_declarations((string) $decl)
                        );
                    }
                }
            }
        }

        $globals = [
            'fieldGap'           => [ '[data-wpcf7-id="' . $form_id . '"]', '--fcf7b-field-gap', 200 ],
            'containerColumnGap' => [ '[data-wpcf7-id="' . $form_id . '"] .fcf7-container', 'column-gap', 120 ],
            'containerRowGap'    => [ '[data-wpcf7-id="' . $form_id . '"] .fcf7-container', 'row-gap', 120 ],
        ];

        foreach ($globals as $key => [$selector, $prop, $max]) {
            if (! isset($schema[ $key ])) {
                continue;
            }

            $gaps = is_array($schema[ $key ]) ? $schema[ $key ] : [ 'desktop' => $schema[ $key ] ];

            foreach (array_merge([ 'desktop' ], array_keys($breaks)) as $device) {
                $value = $gaps[ $device ] ?? null;
                if (! is_numeric($value)) {
                    continue;
                }

                $buckets[ $device ][ $selector ] = array_merge(
                    $buckets[ $device ][ $selector ] ?? [],
                    [ $prop => max(0, min($max, (int) $value)) . 'px' ]
                );
            }
        }

        if (isset($schema['containerPadding']) && is_array($schema['containerPadding'])) {
            $dimensions = $controls->get('dimensions');
            $pad_sel    = '[data-wpcf7-id="' . $form_id . '"] .fcf7-container';

            foreach (array_merge([ 'desktop' ], array_keys($breaks)) as $device) {
                $value = $schema['containerPadding'][ $device ] ?? null;
                if (! $dimensions || ! is_array($value)) {
                    continue;
                }
                $decl = $dimensions->get_css($value, 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};');
                if ('' === trim($decl)) {
                    continue;
                }
                $buckets[ $device ][ $pad_sel ] = array_merge(
                    $buckets[ $device ][ $pad_sel ] ?? [],
                    $this->parse_declarations($decl)
                );
            }
        }

        $this->write_form_css($form_id, $this->render_css($buckets, $breaks));
    }

    protected static function device_chain(string $device): array
    {
        if ('mobile' === $device) {
            return [ 'mobile', 'tablet', 'desktop' ];
        }
        if ('tablet' === $device) {
            return [ 'tablet', 'desktop' ];
        }
        return [ 'desktop' ];
    }

    protected static function condition_passes(array $condition, array $field, array $controls, string $device): bool
    {
        $manager = \CompactForm\Builder\Controls_Manager::instance();

        foreach ($condition as $raw => $expected) {
            $key    = (string) $raw;
            $negate = false;
            if ('!' === substr($key, -1)) {
                $negate = true;
                $key    = substr($key, 0, -1);
            }

            $sub = '';
            if (preg_match('/^(.+)\[(.+)\]$/', $key, $m)) {
                $key = $m[1];
                $sub = $m[2];
            }

            $ref = null;
            foreach ($controls as $c) {
                if (($c['key'] ?? '') === $key) {
                    $ref = $c;
                    break;
                }
            }

            $value = array_key_exists($key, $field)
                ? $field[ $key ]
                : ($ref ? $manager->get_default((string) ($ref['type'] ?? ''), $ref) : '');

            if ($ref && ! empty($ref['responsive']) && is_array($value)) {
                $has_devices = (bool) array_intersect_key($value, array_flip([ 'desktop', 'tablet', 'mobile' ]));
                if ($has_devices) {
                    $value = null;
                    foreach (self::device_chain($device) as $d) {
                        if (isset($field[ $key ][ $d ])) {
                            $value = $field[ $key ][ $d ];
                            break;
                        }
                    }
                }
            }

            if ('' !== $sub) {
                $value = is_array($value) ? ($value[ $sub ] ?? null) : null;
            }
            if (null === $value) {
                $value = '';
            }

            $matched = is_array($expected)
                ? in_array($value, $expected, false)
                : ($value == $expected);

            if ($negate ? $matched : ! $matched) {
                return false;
            }
        }

        return true;
    }

    protected function value_per_device($value, array $ctrl, array $breaks = []): array
    {
        if (! empty($ctrl['responsive']) && is_array($value)) {
            $out = [];
            foreach (array_merge([ 'desktop' ], array_keys($breaks)) as $device) {
                if (isset($value[ $device ])) {
                    $out[ $device ] = $value[ $device ];
                }
            }
            if ($out) {
                return $out;
            }
        }
        return [ 'desktop' => $value ];
    }

    protected function parse_declarations(string $css): array
    {
        $out = [];
        foreach (explode(';', $css) as $chunk) {
            $chunk = trim($chunk);
            if ('' === $chunk) {
                continue;
            }
            $parts = explode(':', $chunk, 2);
            if (2 !== count($parts)) {
                continue;
            }
            $prop = trim($parts[0]);
            $val  = trim($parts[1]);
            if ('' !== $prop && '' !== $val) {
                $out[ $prop ] = $val;
            }
        }
        return $out;
    }

    protected function breakpoints(array $schema = []): array
    {
        $defaults = (array) apply_filters(
            'fcf7_builder_breakpoints',
            [
                'tablet' => 1024,
                'mobile' => 767,
            ]
        );

        $out = [];
        foreach ([ 'tablet', 'mobile' ] as $device) {
            $value = $schema['breakpoints'][ $device ] ?? null;
            $value = is_numeric($value) ? (int) $value : 0;
            if ($value < 320 || $value > 2560) {
                $value = (int) ($defaults[ $device ] ?? 0);
            }
            if ($value > 0) {
                $out[ $device ] = $value;
            }
        }
        return $out;
    }

    protected function render_css(array $buckets, array $breakpoints = []): string
    {
        $out = '';

        foreach (array_merge([ 'desktop' ], array_keys($breakpoints)) as $device) {
            if (empty($buckets[ $device ])) {
                continue;
            }

            $rules = '';
            foreach ($buckets[ $device ] as $selector => $props) {
                $decl = [];
                foreach ($props as $prop => $val) {
                    $decl[] = $prop . ':' . $val;
                }
                if ($decl) {
                    $rules .= $selector . '{' . implode(';', $decl) . '}' . "\n";
                }
            }
            if ('' === trim($rules)) {
                continue;
            }

            $out .= ('desktop' === $device)
                ? $rules
                : '@media (max-width: ' . (int) $breakpoints[ $device ] . 'px){' . "\n" . $rules . '}' . "\n";
        }

        return trim($out);
    }

    protected function write_form_css(int $form_id, string $css): void
    {
        $uploads  = wp_upload_dir();
        $dir      = trailingslashit($uploads['basedir']) . self::CSS_SUBDIR;
        $old_file = basename((string) get_post_meta($form_id, '_fcf7_builder_css_file', true));

        $delete_old = static function () use ($old_file, $dir, $uploads): void {
            if ($old_file && empty($uploads['error'])) {
                wp_delete_file(trailingslashit($dir) . $old_file);
            }
        };

        if ('' === $css) {
            $delete_old();
            delete_post_meta($form_id, '_fcf7_builder_css_file');
            delete_post_meta($form_id, '_fcf7_builder_css_inline');
            return;
        }

        if (empty($uploads['error']) && wp_mkdir_p($dir)) {
            $file = 'form-' . $form_id . '-' . md5($css) . '.css';
            $path = trailingslashit($dir) . $file;

            if (false !== file_put_contents($path, $css . "\n", LOCK_EX)) {
                if ($old_file && $old_file !== $file) {
                    wp_delete_file(trailingslashit($dir) . $old_file);
                }
                update_post_meta($form_id, '_fcf7_builder_css_file', $file);
                delete_post_meta($form_id, '_fcf7_builder_css_inline');
                return;
            }
        }

        $delete_old();
        delete_post_meta($form_id, '_fcf7_builder_css_file');
        update_post_meta($form_id, '_fcf7_builder_css_inline', $css);
    }

    public function ajax_create_form()
    {
        check_ajax_referer('fcf7_builder_save', 'nonce');

        if (! current_user_can('wpcf7_edit_contact_forms')) {
            wp_send_json_error([ 'message' => __('Permission denied.', 'compactform') ], 403);
        }

        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        if ('' === $title) {
            wp_send_json_error([ 'message' => __('Please give this form a title first.', 'compactform') ], 400);
        }

        $contact_form = \WPCF7_ContactForm::get_template([ 'title' => $title ]);
        $form_id = $contact_form->save();

        if (! $form_id) {
            wp_send_json_error([ 'message' => __('Could not create the form.', 'compactform') ], 500);
        }

        update_post_meta($form_id, self::META_ENABLED, '1');

        $edit_url = add_query_arg(
            [ 'page' => 'wpcf7', 'post' => $form_id, 'action' => 'edit' ],
            admin_url('admin.php')
        );

        wp_send_json_success([ 'formId' => $form_id, 'editUrl' => $edit_url ]);
    }

    public function ajax_save()
    {
        check_ajax_referer('fcf7_builder_save', 'nonce');

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;

        if (! $form_id || ! current_user_can('wpcf7_edit_contact_form', $form_id)) {
            wp_send_json_error([ 'message' => __('Permission denied.', 'compactform') ], 403);
        }

        // Raw JSON payload, not plain text — sanitize_text_field() would corrupt the JSON
        // structure. Nonce already verified above via check_ajax_referer(). Sanitized 4 lines
        // below via Fields_Manager::sanitize_schema(), after decode.
        $schema = json_decode(wp_unslash($_POST['schema'] ?? ''), true); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (! is_array($schema) || empty($schema['fields'])) {
            wp_send_json_error([ 'message' => __('Nothing to save.', 'compactform') ], 400);
        }
        $schema = \CompactForm\Builder\Fields_Manager::instance()->sanitize_schema($schema);

        $schema = apply_filters('fcf7_builder_schema_after_sanitize', $schema, $form_id);

        if (! wpcf7_contact_form($form_id)) {
            wp_send_json_error([ 'message' => __('Form not found.', 'compactform') ], 404);
        }

        $this->materialize($form_id, $schema);

        $mail = get_post_meta($form_id, '_mail', true);

        $mail_default = json_decode((string) get_post_meta($form_id, '_fcf7_mail_default_snapshot', true), true);

        wp_send_json_success(apply_filters('fcf7_builder_save_response', [
            'message'     => __('Saved.', 'compactform'),
            'mail'        => is_array($mail) ? $mail : null,
            'mailDefault' => is_array($mail_default) ? $mail_default : null,
        ], $form_id));
    }

    public function materialize(int $form_id, array $schema): void
    {
        update_post_meta($form_id, self::META_SCHEMA, wp_slash(wp_json_encode($schema)));
        $this->generate_form_css($form_id, $schema);

        do_action('fcf7_schema_saved', $form_id, $schema);

        $contact_form = wpcf7_contact_form($form_id);
        if ($contact_form) {
            $properties         = $contact_form->get_properties();
            $properties['form'] = \CompactForm\Builder\Form_Compiler::compile($schema);
            $contact_form->set_properties($properties);
            $contact_form->save();
        }
    }

    public function compile_on_save($contact_form)
    {
        // Hooked on wpcf7_save_contact_form, fired only from inside CF7 core's own admin
        // edit-screen save handler, after CF7's own nonce check — no separate check belongs here.
        if (empty($_POST['fcf7-builder-schema'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return;
        }

        if (self::$fanning) {
            return;
        }

        // Raw JSON payload, not plain text — sanitize_text_field() would corrupt the JSON
        // structure. Sanitized 4 lines below via Fields_Manager::sanitize_schema(), after decode.
        $schema = json_decode(wp_unslash($_POST['fcf7-builder-schema']), true); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (! is_array($schema) || empty($schema['fields'])) {
            return;
        }
        $schema = \CompactForm\Builder\Fields_Manager::instance()->sanitize_schema($schema);
        $schema = apply_filters('fcf7_builder_schema_after_sanitize', $schema, (int) $contact_form->id());

        $markup     = \CompactForm\Builder\Form_Compiler::compile($schema);
        $properties = $contact_form->get_properties();
        $properties['form'] = $markup;
        $contact_form->set_properties($properties);
    }

    public function fan_out_started(): void
    {
        self::$fanning = true;
    }

    public function fan_out_ended(): void
    {
        self::$fanning = false;
    }

    public function store_schema_meta($post_id)
    {
        // Hooked on save_post_wpcf7_contact_form, WP core's own save_post_{type} action — WP
        // never fires save_post without its own edit-post nonce check succeeding first
        // (wp-admin/post.php's edit_post()); the current_user_can() capability check below is
        // also in place before this value is ever used. No separate nonce check belongs here.
        if (empty($_POST['fcf7-builder-schema'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (self::$fanning) {
            return;
        }
        if (! current_user_can('wpcf7_edit_contact_form', $post_id)) {
            return;
        }

        // Raw JSON payload, not plain text — sanitize_text_field() would corrupt the JSON
        // structure. Sanitized a few lines below via Fields_Manager::sanitize_schema(), after decode.
        $raw    = wp_unslash($_POST['fcf7-builder-schema']); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $schema = json_decode($raw, true);
        if (! is_array($schema)) {
            return;
        }
        $schema = \CompactForm\Builder\Fields_Manager::instance()->sanitize_schema($schema);

        $schema = apply_filters('fcf7_builder_schema_after_sanitize', $schema, (int) $post_id);

        update_post_meta($post_id, self::META_ENABLED, '1');

        update_post_meta($post_id, self::META_SCHEMA, wp_slash(wp_json_encode($schema)));
        $this->generate_form_css((int) $post_id, $schema);

        do_action('fcf7_schema_saved', (int) $post_id, $schema);
    }

    protected function get_schema_for($post)
    {
        $id = $post ? $post->id() : 0;

        if ($id) {
            $stored = get_post_meta($id, self::META_SCHEMA, true);
            if ($stored) {
                $decoded = json_decode($stored, true);
                if (is_array($decoded) && ! empty($decoded['fields'])) {
                    return $decoded;
                }
            }
        }

        $markup = $post ? $post->prop('form') : '';
        if ($markup) {
            $parsed = Builder_Parser::parse($markup);
            if (! empty($parsed['fields'])) {
                return $parsed;
            }
        }

        return $this->starter_schema();
    }

    protected function starter_schema()
    {
        return [
            'version' => 1,
            'fields'  => [
                [ 'id' => 'f_name', 'type' => 'text', 'name' => 'your-name', 'label' => 'Your Name', 'required' => true, 'width' => 50, 'placeholder' => 'Jane Doe' ],
                [ 'id' => 'f_email', 'type' => 'email', 'name' => 'your-email', 'label' => 'Email', 'required' => true, 'width' => 50, 'placeholder' => 'jane@example.com' ],
                [ 'id' => 'f_subject', 'type' => 'text', 'name' => 'your-subject', 'label' => 'Subject', 'width' => 100 ],
                [ 'id' => 'f_message', 'type' => 'textarea', 'name' => 'your-message', 'label' => 'Message', 'width' => 100 ],
                [ 'id' => 'f_submit', 'type' => 'submit', 'label' => 'Send Message', 'width' => 100 ],
            ],
        ];
    }
}
