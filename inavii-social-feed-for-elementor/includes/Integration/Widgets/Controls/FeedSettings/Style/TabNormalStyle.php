<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\FeedSettings\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Css_Filter;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;

class TabNormalStyle
{
    use VersionedFeaturesTrait;

    public static function add($widget): void
    {
        $widget->start_controls_tab('normal',
            [
                'label' => __('Normal', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_control(
            'opacity',
            [
                'label' => __('Opacity', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'max' => 1,
                        'min' => 0.10,
                        'step' => 0.01,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .inavii-grid__item-box .inavii-grid__image' => 'opacity: {{SIZE}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'grayscale',
            array(
                'label' => __('Grayscale', 'inavii'),
                'type' => Controls_Manager::SLIDER,
                'default' => array(
                    'unit' => '%',
                ),
                'tablet_default' => array(
                    'unit' => '%',
                ),
                'mobile_default' => array(
                    'unit' => '%',
                ),
                'size_units' => array('%'),
                'range' => array(
                    '%' => array(
                        'min' => 0,
                        'max' => 100,
                    ),
                ),
                'selectors' => [
                    '{{WRAPPER}} .inavii-grid__item-box' => 'filter: grayscale({{SIZE}}{{UNIT}});',
                ],
            )
        );

        $widget->add_group_control(
            Group_Control_Css_Filter::get_type(),
            [
                'name' => 'css_filters',
                'selector' => '{{WRAPPER}} .inavii-grid__item-box .inavii-grid__image',
            ]
        );

        $widget->add_control(
            'transition',
            [
                'label' => __('Transition (ms)', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'max' => 3000,
                        'min' => 100,
                        'step' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .inavii-grid__item .inavii-grid__item-box .inavii-grid__image' => 'transition: all {{SIZE}}ms;',
                ],
            ]
        );

        $widget->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'items_box_shadow',
                'selector' => '{{WRAPPER}} .inavii-grid__item',
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => '!in',
                            'value' => array_merge(array_values($widget->shapeMatrixCondition), array_values($widget->creativeGridCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'items_border_normal',
                'selector' => '{{WRAPPER}} .inavii-grid__item-box',
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => '!in',
                            'value' => array_merge(array_values($widget->shapeMatrixCondition), array_values($widget->creativeGridCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->end_controls_tab();
    }
}