<?php

namespace CompactForm\Extensions;

if (! defined('ABSPATH')) {
    exit;
}

class Container
{
    public function __construct()
    {
        add_action('wpcf7_init', [ __CLASS__, 'add_shortcodes' ], 10, 0);
        add_action('wp_enqueue_scripts', [ __CLASS__, 'register_assets' ], 5);

        add_filter('wpcf7_contact_form_properties', [ $this, 'fcf7_container_properties' ], 10, 2);
    }

    public static function add_shortcodes()
    {
        wpcf7_add_form_tag('fcf7_container', [ __CLASS__, 'container_tag_handler' ], [ 'name-attr' => false ]);
    }

    public static function register_assets()
    {
        wp_enqueue_style('fcf7-container', FCF7_ASSETS . 'css/container.min.css', [], FCF7_VERSION);
    }

    public static function container_tag_handler($tag)
    {
        return '<div></div>';
    }

    private static function is_cf7_admin_editor()
    {
        return is_admin()
            && ! (defined('DOING_AJAX') && DOING_AJAX)
            && 'wpcf7' === sanitize_text_field(wpcf7_superglobal_get('page'));
    }

    public function fcf7_container_properties($properties, $cfform)
    {
        if (self::is_cf7_admin_editor()) {
            return $properties;
        }

        $form = $properties['form'];

        if (strpos($form, '[fcf7_container') === false) {
            return $properties;
        }

        $form_parts = preg_split('/(\[\/?fcf7_container(?:\]|\s.*?\]))/', $form, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        ob_start();

        foreach ($form_parts as $form_part) {
            if (substr($form_part, 0, 15) === '[fcf7_container' && $form_part !== '[/fcf7_container]') {
                echo '<div class="fcf7-container">';
            } elseif ($form_part === '[/fcf7_container]') {
                echo '</div>';
            } else {
                // wp_kses_post(), not esc_html(): $form_part is form-editor-authored HTML mixed
                // with CF7 shortcode tags (e.g. "[text* your-name]") — square brackets pass
                // through kses untouched, so this only strips disallowed HTML while keeping the
                // shortcode tags CF7 will expand later.
                echo wp_kses_post( $form_part );
            }
        }

        $properties['form'] = ob_get_clean();

        return $properties;
    }
}
