<?php

namespace CompactForm\Extensions;

if (! defined('ABSPATH')) {
    exit;
}

class Admin_Bar
{
    private const META_ENABLED = '_fcf7_builder_enabled';
    private const HANDLE       = 'fcf7-admin-bar';

    private array $forms = [];

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [ $this, 'register_assets' ], 5);
        add_filter('wpcf7_form_class_attr', [ $this, 'collect_rendered_form' ]);
        add_action('wp_footer', [ $this, 'enqueue_node' ], 5);
    }

    public function register_assets(): void
    {
        wp_register_script(self::HANDLE, FCF7_ASSETS . 'js/admin-bar.min.js', [], FCF7_VERSION, true);
    }

    public function collect_rendered_form($class)
    {
        if (is_admin() || ! is_admin_bar_showing() || ! function_exists('wpcf7_get_current_contact_form')) {
            return $class;
        }

        $form = wpcf7_get_current_contact_form();

        if (! $form instanceof \WPCF7_ContactForm) {
            return $class;
        }

        $form_id = (int) $form->id();

        if (! $form_id || isset($this->forms[ $form_id ])) {
            return $class;
        }

        if ('1' !== (string) get_post_meta($form_id, self::META_ENABLED, true)) {
            return $class;
        }

        if (! current_user_can('wpcf7_edit_contact_form', $form_id)) {
            return $class;
        }

        $title = trim((string) $form->title());

        if ('' === $title) {
            /* translators: %d: contact form ID. */
            $title = sprintf(__('Form #%d', 'compactform'), $form_id);
        }

        $this->forms[ $form_id ] = [
            'title' => $title,
            'url'   => $this->edit_url($form_id),
        ];

        return $class;
    }

    public function enqueue_node(): void
    {
        if (empty($this->forms) || ! is_admin_bar_showing()) {
            return;
        }

        wp_localize_script(
            self::HANDLE,
            'fcf7AdminBar',
            [
                'label' => __('Edit form with CompactForm', 'compactform'),
                'icon'  => FCF7_URL . 'assets/branding/menu-icon.png',
                'forms' => array_values($this->forms),
            ]
        );

        wp_enqueue_script(self::HANDLE);
    }

    private function edit_url(int $form_id): string
    {
        return add_query_arg(
            [
                'page'       => 'wpcf7',
                'post'       => $form_id,
                'action'     => 'edit',
                'active-tab' => 'fcf7-form-builder',
            ],
            admin_url('admin.php')
        );
    }
}
