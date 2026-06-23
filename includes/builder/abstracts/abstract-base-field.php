<?php

namespace CompactForm\Builder\Abstracts;

defined('ABSPATH') || die();

abstract class Base_Field extends Controls_Stack
{

    /** CF7 tag / schema type, e.g. 'text', 'fcf7_date_picker'. */
    abstract public function get_type(): string;

    /** Human label shown in the palette + panel. */
    abstract public function get_title(): string;

    /** Remixicon class for the palette. */
    public function get_icon(): string
    {
        return 'ri-shape-line';
    }

    /** Palette group: basic | choice | layout | extensions. */
    public function get_category(): string
    {
        return 'basic';
    }

    /** Pro-only field type — shows a "Pro" badge on its palette item. */
    public function is_pro(): bool
    {
        return false;
    }

    /**
     * A generic canvas-preview shape the JS renders (no per-field JS logic):
     * input | textarea | select | radio | checkbox | acceptance | heading |
     * divider | submit | stars.
     */
    public function get_preview(): string
    {
        return 'input';
    }

    /**
     * May this field be linked to its type's site-wide global (Global Fields)?
     *
     * False for a CONTAINER: its children are separate schema entries belonging
     * to one particular form, so "the same container everywhere" has no meaning
     * — the shared record would describe a box whose contents differ per form.
     */
    public function is_globalizable(): bool
    {
        return true;
    }

    abstract public function compile(array $data): string;

    public function get_config(): array
    {
        $this->reset_stack();

        $this->register_controls();

        $defaults = $this->get_defaults();

        return array_merge(
            [
                'type'     => $this->get_type(),
                'title'    => $this->get_title(),
                'icon'     => $this->get_icon(),
                'category' => $this->get_category(),
                'preview'  => $this->get_preview(),
                'isPro'    => $this->is_pro(),
                // Whether the builder offers "Make Global" on this type at all.
                'globalizable' => $this->is_globalizable(),
            ],
            // sections, popovers, tab_groups, tab_items, controls.
            $this->get_stack(),
            [
                'defaults'      => $defaults,
                // Static canvas-preview markup, baked once here (from this TYPE's
                // defaults, not any live instance) and shipped as part of this same
                // get_config() payload — no per-field AJAX round trip. See
                // render_builder() below for the contract/trade-offs.
                'builderMarkup' => $this->render_builder($defaults),
            ]
        );
    }

    /** Default schema data for a freshly-dropped instance of this field. */
    public function get_defaults(): array
    {
        $manager  = \CompactForm\Builder\Controls_Manager::instance();
        $defaults = ['type' => $this->get_type()];

        foreach ($this->get_controls() as $control) {
            $default = $manager->get_default($control['type'], $control);

            // Responsive controls hold a per-device map; other devices are only
            // created once the user actually edits them (empty = inherit desktop).
            $defaults[$control['key']] = ! empty($control['responsive'])
                ? ['desktop' => $default]
                : $default;
        }

        return $defaults;
    }

    /**
     * Optional raw canvas-preview markup, in lieu of writing a JS preview
     * component. Called exactly ONCE per field TYPE, from get_config() at
     * localize time (with $data = get_defaults()) — the result is shipped
     * inline as part of the same FCF7Builder.fields payload every field's
     * config already rides on, so there is no per-field/per-edit network
     * round trip. Because it's baked once from defaults rather than any live
     * instance, it's for STRUCTURE only: per-instance state (colours, sizes,
     * anything a style control drives) still updates live because those are
     * rendered separately by the selectors-driven CSS generator against
     * whatever classes this markup contains — this method never re-runs as
     * the user edits a field.
     *
     * On the JS side the returned markup is injected as-is (no escaping) into
     * the card's control area, still wrapped in the normal label/description
     * chrome — see fields/ServerMarkupPreview.jsx. If a preview is ALSO
     * registered for this type via registry.registerComponent()/register()
     * (first-party in fields/index.js, or third-party via
     * FCF7Builder.hooks.addAction()), that JS registration wins outright and
     * this markup is never used.
     *
     * Return '' (default) to opt out.
     *
     * @param array $data This field type's default schema (get_defaults()).
     * @return string
     */
    public function render_builder(array $data): string
    {
        return '';
    }
}
