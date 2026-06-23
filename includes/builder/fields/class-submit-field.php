<?php

namespace CompactForm\Builder\Fields;

use CompactForm\Builder\Controls_Manager;
use CompactForm\Builder\Abstracts\Base_Field;
use CompactForm\Builder\Abstracts\Traits\Width_Control;
use CompactForm\Builder\Abstracts\Traits\Flex_Item_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Control;
use CompactForm\Builder\Abstracts\Traits\Conditional_Redirect_Control;
use CompactForm\Builder\Field_Helpers;

defined('ABSPATH') || die();

class Submit_Field extends Base_Field
{
    use Width_Control;
    use Flex_Item_Control;
    use Conditional_Control;
    use Conditional_Redirect_Control;

    public function get_type(): string
    {
        return 'submit';
    }
    public function get_title(): string
    {
        return __('Submit', 'compactform');
    }
    public function get_icon(): string
    {
        return 'ri-send-plane-2-line';
    }
    public function get_category(): string
    {
        return 'basic';
    }
    public function get_preview(): string
    {
        return 'submit';
    }

    protected function register_controls(): void
    {
        $this->start_section('general', [
            'label' => __('Button', 'compactform'),
            'tab'   => Controls_Manager::TAB_SETTINGS,
        ]);
        $this->add_control('label', [
            'type' => Controls_Manager::TEXT,
            'label' => __('Button Text', 'compactform'),
        ]);
        $this->add_control('width', self::width_control_args());
        $this->register_flex_item_controls();
        $this->end_section();

        $button = '{{WRAPPER}} .wpcf7-form-control';

        $this->start_section('style', [
            'label' => __('Style', 'compactform'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control('typography', [
            'type' => Controls_Manager::TYPOGRAPHY,
            'label'    => __('Typography', 'compactform'),
            'selector'   => $button,
            'responsive' => true,
        ]);
        $this->add_control('align', [
            'type' => Controls_Manager::CHOOSE,
            'label'       => __('Alignment', 'compactform'),
            'label_block' => false,
            'toggle'      => true,
            'options'     => [
                'left'   => [
                    'title' => __('Left', 'compactform'),
                    'icon' => 'ri-align-left'
                ],
                'center' => [
                    'title' => __('Center', 'compactform'),
                    'icon' => 'ri-align-center'
                ],
                'right'  => [
                    'title' => __('Right', 'compactform'),
                    'icon' => 'ri-align-right'
                ],
            ],
            'responsive' => true,
            'selectors'  => [ '{{WRAPPER}}' => 'text-align: {{VALUE}};' ],
        ]);
        $this->add_control('button_width', [
            'type' => Controls_Manager::SLIDER,
            'label'      => __('Width', 'compactform'),
            'units'      => [ 'px', '%', 'custom' ],
            'ranges'     => [
                'px' => [ 'min' => 0, 'max' => 600, 'step' => 1 ],
                '%'  => [ 'min' => 0, 'max' => 100, 'step' => 1 ],
            ],
            'responsive' => true,
            'selectors'  => [ $button => 'width: {{SIZE}}{{UNIT}}; display: inline-block;' ],
        ]);
        $this->add_control('border_radius', [
            'type' => Controls_Manager::DIMENSIONS,
            'label'     => __('Border Radius', 'compactform'),
            'selectors'  => [ $button => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
            'responsive' => true,
        ]);
        $this->add_control('padding', [
            'type' => Controls_Manager::DIMENSIONS,
            'label'     => __('Padding', 'compactform'),
            'selectors'  => [ $button => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
            'responsive' => true,
        ]);
        $this->add_control('margin', [
            'type' => Controls_Manager::DIMENSIONS,
            'label'     => __('Margin', 'compactform'),
            'selectors'  => [ '{{WRAPPER}}' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
            'responsive' => true,
        ]);
        $this->add_control('transition_duration', [
            'type' => Controls_Manager::SLIDER,
            'label'     => __('Transition Duration', 'compactform'),
            'units'     => [ 's' ],
            'min'       => 0,
            'max'       => 3,
            'step'      => 0.1,
            'selectors' => [ $button => 'transition: all {{SIZE}}{{UNIT}} ease;' ],
        ]);

        $this->start_controls_tabs('button_style_tabs', [ 'separator' => 'before' ]);

            $this->start_controls_tab('button_tab_normal', [
                'label' => __('Normal', 'compactform'),
            ]);
                $this->add_control('text_color', [
                    'type' => Controls_Manager::COLOR,
                    'label'     => __('Text Color', 'compactform'),
                    'selectors' => [ $button => 'color: {{VALUE}};' ],
                ]);
                $this->add_control('background_color', [
                    'type' => Controls_Manager::COLOR,
                    'label'     => __('Background Color', 'compactform'),
                    'selectors' => [ $button => 'background-color: {{VALUE}};' ],
                ]);
                $this->add_control('border', [
                    'type' => Controls_Manager::BORDER,
                    'label'      => __('Border', 'compactform'),
                    'selector'   => $button,
                ]);
                $this->add_control('box_shadow', [
                    'type' => Controls_Manager::BOX_SHADOW,
                    'label'    => __('Box Shadow', 'compactform'),
                    'selector' => $button,
                ]);
            $this->end_controls_tab();

            $this->start_controls_tab('button_tab_hover', [
                'label' => __('Hover', 'compactform'),
            ]);
                $this->add_control('text_color_hover', [
                    'type' => Controls_Manager::COLOR,
                    'label'     => __('Text Color', 'compactform'),
                    'selectors' => [ $button . ':hover' => 'color: {{VALUE}};' ],
                ]);
                $this->add_control('background_color_hover', [
                    'type' => Controls_Manager::COLOR,
                    'label'     => __('Background Color', 'compactform'),
                    'selectors' => [ $button . ':hover' => 'background-color: {{VALUE}};' ],
                ]);
                $this->add_control('border_color_hover', [
                    'type' => Controls_Manager::COLOR,
                    'label'     => __('Border Color', 'compactform'),
                    'selectors' => [ $button . ':hover' => 'border-color: {{VALUE}} !important;' ],
                    'condition' => [ 'border[style]!' => '' ],
                ]);
                $this->add_control('box_shadow_hover', [
                    'type' => Controls_Manager::BOX_SHADOW,
                    'label'    => __('Box Shadow', 'compactform'),
                    'selector' => $button . ':hover',
                ]);
            $this->end_controls_tab();

        $this->end_controls_tabs();
        $this->end_section();

        $this->start_section('advanced', [
            'label' => __('Advanced', 'compactform'),
            'tab'   => Controls_Manager::TAB_ADVANCED,
        ]);
        $this->add_control('cssClass', [
            'type' => Controls_Manager::TEXT,
            'label' => __('CSS Class', 'compactform'),
        ]);
        $this->end_section();

        $this->register_conditional_controls();
        $this->register_conditional_redirect_controls();
    }

    public function compile(array $data): string
    {
        $text = ! empty($data['label']) ? $data['label'] : 'Send';
        return '[submit "' . Field_Helpers::q($text) . '"]';
    }
}
