<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\FeedSettings\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Css_Filter;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;

class TabHoverStyle
{
    use VersionedFeaturesTrait;

    public static function add($widget): void
    {
        $widget->start_controls_tab('hover',
            [
                'label' => __('Hover', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_control(
            'opacity_hover',
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
                    '{{WRAPPER}} .inavii-grid__item:hover .inavii-grid__item-box .inavii-grid__image' => 'opacity: {{SIZE}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'grayscale_hover',
            array(
                'label' => __('Grayscale', 'inavii-social-feed-e'),
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
                    '{{WRAPPER}} .inavii-grid__item:hover .inavii-grid__item-box' => 'filter: grayscale({{SIZE}}{{UNIT}});',
                ],
            )
        );

        $widget->add_responsive_control(
            'scale_hover',
            array(
                'label' => __('Scale', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => [
                        'max' => 6,
                        'min' => 1,
                        'step' => 0.01,
                    ],
                ),
                'selectors' => [
                    '{{WRAPPER}} .inavii-grid__item-box .inavii-grid__image:hover' => 'transform: scale({{SIZE}});',
                ],
            )
        );

        $widget->add_group_control(
            Group_Control_Css_Filter::get_type(),
            [
                'name' => 'css_filters_hover',
                'selector' => '{{WRAPPER}} .inavii-grid__item:hover .inavii-grid__item-box .inavii-grid__image',
            ]
        );

        $widget->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'items_box_shadow_hover',
                'selector' => '{{WRAPPER}} .inavii-grid__item:hover',
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
                'name' => 'box_content_border_hover',
                'selector' => '{{WRAPPER}} .inavii-grid__item-box:hover',
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

        $widget->add_control(
            'background_hover_transition',
            [
                'label' => __('Transition Duration', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'max' => 3,
                        'step' => 0.1,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .inavii-grid__item' => 'transition-duration: {{SIZE}}s',
                ],
            ]
        );

        $widget->add_control(
            'hover_animation',
            [
                'label' => __('Hover Animation', 'inavii-social-feed-e'),
                'type' => Controls_Manager::HOVER_ANIMATION,
            ]
        );

        $widget->end_controls_tab();
    }
}