<?php

namespace CompactForm\Builder\Abstracts;

use CompactForm\Builder\Controls_Manager;
use CompactForm\Builder\Field_Helpers;

defined('ABSPATH') || die();

abstract class Extension_Field extends Base_Field
{
    /** Cached handles from register_style()/register_script(), set once on wp_enqueue_scripts. */
    private array $registered_styles = [];
    private array $registered_scripts = [];

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [ $this, '_fcf7_register_assets' ]);
        add_action('admin_enqueue_scripts', [ $this, '_fcf7_admin_enqueue_assets' ]);
        add_action('wpcf7_init', [ $this, '_fcf7_add_tag' ]);

        if ($this->name_attr()) {
            add_action('wpcf7_swv_create_schema', [ $this, '_fcf7_swv_create_schema' ], 10, 2);
        }
    }

    /** The custom CF7 tag, e.g. 'fcf7_date_picker'. */
    abstract protected function get_tag(): string;

    /**
     * Which schema keys map to tag options, in emit order.
     * @return array[] each: [ 'prop' => key, 'opt' => option, 'type' => 'value'|'bool', 'on' => 'on' ]
     */
    abstract protected function option_map(): array;

    protected function name_attr(): bool
    {
        return true;
    }

    protected function star_supported(): bool
    {
        return true;
    }

    /**
     * Whether this field's caption wrapper must be a <div> rather than a
     * <label> (Field_Helpers::label_wrap()'s $group flag).
     *
     * Return true for a field that is not a single labelable control — one
     * that renders several elements, or any element a click must not be
     * forwarded to. A <label> ACTIVATES the control it contains, so e.g. a
     * canvas sitting next to a hidden file input inside one would open the
     * file picker the moment the user touched the canvas.
     */
    protected function label_group(): bool
    {
        return false;
    }

    /**
     * Register (wp_register_style) this field's frontend stylesheet(s) and
     * return the handle(s) so the base class can enqueue them only when a
     * form on the page actually renders this tag. Override to add styles.
     *
     * @return string[] Registered style handles.
     */
    protected function register_style(): array
    {
        return [];
    }

    protected function register_preview_style(): array
    {
        return [];
    }

    protected function register_preview_script(): array
    {
        return [];
    }

    /**
     * Register (wp_register_script) this field's frontend script(s) and
     * return the handle(s) so the base class can enqueue them only when a
     * form on the page actually renders this tag. Override to add scripts.
     *
     * @return string[] Registered script handles.
     */
    protected function register_script(): array
    {
        return [];
    }

    /**
     * Frontend markup for this tag. Same single-argument contract CF7 itself
     * gives a form-tag render callback: `function ( $tag ) { … }`, plus the
     * current form's id (0 for a hand-written form with no builder schema)
     * so a field can look up its own schema entry via
     * Field_Helpers::get_form_schema( WPCF7_ContactForm::get_current() ).
     *
     * @param \WPCF7_FormTag $tag
     * @param int            $form_id
     * @return string
     */
    public function render_front($tag, $form_id = 0)
    {
        return '';
    }

    /**
     * Add this field's SWV validation rule(s) for ONE tag. Same two-argument
     * shape CF7's own field modules use to build wpcf7_swv_create_schema
     * rules (e.g. modules/text.php's wpcf7_swv_add_text_rules( $schema,
     * $contact_form ), which scans its own tags and calls
     * $schema->add_rule(...) per tag) — except the base class already scans
     * this field's tags (by basetype, so both the plain and required `*`
     * variant are covered in one pass) and calls you once per matching tag.
     * Call $schema->add_rule( wpcf7_swv_create_rule( … ) ) for whatever this
     * field needs; check $tag->is_required() yourself for required-only rules.
     *
     * $form_schema is the full builder JSON (`[ 'fields' => [ … ] ]`, via
     * Field_Helpers::get_form_schema()) — use it to look past this one $tag at
     * the rest of the form (e.g. a sibling field's options or conditional rule).
     * Empty array for a hand-written form with no builder schema. $contact_form
     * is the same object the wpcf7_swv_create_schema hook received.
     *
     * @param \WPCF7_SWV_Schema        $schema
     * @param \WPCF7_FormTag           $tag
     * @param array                    $form_schema
     * @param \WPCF7_ContactForm|null  $contact_form
     */
    public function validate_req_data($schema, $tag, array $form_schema = [], $contact_form = null) {}

    /**
     * Optional admin-side script/style for this field (e.g. something its
     * builder canvas preview needs, like Rich Text's wp_enqueue_editor()),
     * enqueued only on the CF7 form editor screen. Override to add one; no-op
     * by default.
     *
     * @param string $hook Current admin page hook, same as admin_enqueue_scripts.
     */
    public function enqueue_admin_assets($hook) {}

    /**
     * Register + enqueue this field's FRONT-END stylesheet(s) inside the builder,
     * so its canvas preview matches the real form. Front-end registration only
     * runs on wp_enqueue_scripts, so the editor (admin) has to trigger it itself.
     */
    public function enqueue_preview_styles(): void
    {
        foreach ((array) $this->register_preview_style() as $handle) {
            wp_enqueue_style($handle);
        }
    }

    /**
     * Same for SCRIPTS: a field whose canvas preview is the real widget (the
     * three flatpickr pickers) needs its front-end library and init script on
     * the builder screen. `register_script()` only runs on wp_enqueue_scripts,
     * so `register_preview_script()` has to do the admin-side wp_register_script
     * itself and return the handles.
     */
    public function enqueue_preview_scripts(): void
    {
        foreach ((array) $this->register_preview_script() as $handle) {
            wp_enqueue_script($handle);
        }
    }

    /** @internal wp_enqueue_scripts callback — registers assets, caches handles. */
    public function _fcf7_register_assets(): void
    {
        $this->registered_styles  = (array) $this->register_style();
        $this->registered_scripts = (array) $this->register_script();
    }

    /** @internal wpcf7_init callback — registers this field's CF7 form tag. */
    public function _fcf7_add_tag(): void
    {
        $star  = $this->star_supported() ? $this->get_tag() . '*' : null;
        $types = $star ? [ $this->get_tag(), $star ] : [ $this->get_tag() ];

        wpcf7_add_form_tag($types, [ $this, '_fcf7_render_cb' ], $this->tag_features());
    }

    protected function tag_features(): array
    {
        return [ 'name-attr' => $this->name_attr() ];
    }

    /** @internal Tag render callback — conditional enqueue, then render_front(). */
    public function _fcf7_render_cb($tag)
    {
        $this->enqueue_frontend_assets();

        $contact_form = \WPCF7_ContactForm::get_current();
        $form_id      = $contact_form ? (int) $contact_form->id() : 0;

        return $this->render_front($tag, $form_id);
    }

    public function enqueue_frontend_assets(): array
    {
        foreach ($this->registered_styles as $handle) {
            wp_enqueue_style($handle);
        }
        foreach ($this->registered_scripts as $handle) {
            if (wp_script_is($handle, 'registered')) {
                wp_enqueue_script($handle);
            }
        }

        return $this->registered_styles;
    }

    /** @internal wpcf7_swv_create_schema callback — scans this field's tags, delegates per tag. */
    public function _fcf7_swv_create_schema($schema, $contact_form): void
    {
        $tags        = $contact_form->scan_form_tags([ 'basetype' => [ $this->get_tag() ] ]);
        $form_schema = Field_Helpers::get_form_schema($contact_form);

        foreach ($tags as $tag) {
            $this->validate_req_data($schema, $tag, $form_schema, $contact_form);
        }
    }

    /** @internal admin_enqueue_scripts callback. */
    public function _fcf7_admin_enqueue_assets($hook): void
    {
        $this->enqueue_admin_assets($hook);
    }

    public function get_category(): string
    {
        return 'extensions';
    }

    public function compile(array $data): string
    {
        $star   = ($this->star_supported() && ! empty($data['required'])) ? '*' : '';
        $markup = '[' . $this->get_tag() . $star;

        if ($this->name_attr()) {
            $markup .= ' ' . Field_Helpers::name($data);
        }

        $controls_by_prop = [];
        foreach ($this->get_controls() as $control) {
            foreach ($this->option_map() as $o) {
                if ($control['key'] === $o['prop']) {
                    $controls_by_prop[ $o['prop'] ] = $control;
                }
            }
        }

        $controls_manager = Controls_Manager::instance();

        foreach ($this->option_map() as $o) {
            $control_def = $controls_by_prop[ $o['prop'] ] ?? null;
            $control     = $control_def ? $controls_manager->get($control_def['type']) : null;
            $raw         = $data[ $o['prop'] ] ?? '';
            $value       = $control ? $control->get_option_value_stringify($raw) : (is_scalar($raw) ? (string) $raw : '');

            if ('' === $value) {
                continue;
            }

            if (($o['type'] ?? 'value') === 'bool') {
                $markup .= ' ' . $o['opt'] . ':' . ($o['on'] ?? 'on');
                continue;
            }

            // Restrict to CF7's tag-option charset so an option value can't
            // inject a `]`/`"`/space and break out of the [tag …] shortcode.
            $value = preg_replace('/[^A-Za-z0-9:.!?#$&@_\/|%+=-]/', '', trim($value));
            if ('' !== $value) {
                $markup .= ' ' . $o['opt'] . ':' . $value;
            }
        }

        foreach ($this->tag_values($data) as $value) {
            $value = trim((string) $value);
            if ('' !== $value) {
                $markup .= ' "' . Field_Helpers::q($value) . '"';
            }
        }

        $markup .= Field_Helpers::atts($data, true);
        $markup .= ']';

        return Field_Helpers::label_wrap($data, $markup, $this->label_group());
    }

    /**
     * Quoted "values" to append to the tag, in order.
     *
     * Tag OPTIONS cannot contain spaces — CF7's syntax forbids it and compile()
     * strips them — so any free text a field needs (a prompt, a button label)
     * has to travel as a value instead. Read them back in render_front() via
     * `$tag->values[0]`, `[1]`, …
     *
     * @param array $data The schema field data.
     * @return string[]
     */
    protected function tag_values(array $data): array
    {
        return [];
    }
}
