<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\FooterBox\Style;

use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;

class SectionFooterBoxStyle implements ControlsInterface{

    use VersionedFeaturesTrait;
    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_style_footer_box',
            array(
                'label' => __('Footer Box', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-footer',
                'tab' => Controls_Manager::TAB_STYLE,
                'conditions' => [
                    'terms' => [
                        [
                            'relation' => 'or',
                            'terms' => [
                                [
                                    'name' => 'enable_follow_button',
                                    'operator' => '===',
                                    'value' => 'yes',
                                ],
                                [
                                    'name' => 'enable_load_more_button',
                                    'operator' => '===',
                                    'value' => 'yes',
                                ],
                            ],
                        ],
                    ],
                ],
            )
        );

        $widget->add_responsive_control(
            'button_box_alignment',
            array(
                'label' => __('Alignment', 'inavii-social-feed-e'),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'flex-start' => array(
                        'title' => __('Left', 'inavii-social-feed-e'),
                        'icon' => 'eicon-h-align-left',
                    ),
                    'center' => array(
                        'title' => __('Center', 'inavii-social-feed-e'),
                        'icon' => 'eicon-h-align-center',
                    ),
                    'flex-end' => array(
                        'title' => __('Right', 'inavii-social-feed-e'),
                        'icon' => 'eicon-h-align-right',
                    ),
                    'space-between' => array(
                        'title' => __('Space Between', 'inavii-social-feed-e'),
                        'icon' => 'eicon-h-align-stretch',
                    ),
                ),
                'default' => 'center',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__box' => 'justify-content: {{VALUE}}; align-items: {{VALUE}};',
                ),
            )
        );

        $widget->add_responsive_control(
            'button_box_direction',
            array(
                'label' => __('Direction', 'inavii-social-feed-e'),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'row' => array(
                        'title' => __('Row - horizontal', 'inavii-social-feed-e'),
                        'icon' => 'eicon-arrow-right',
                    ),
                    'column' => array(
                        'title' => __('Column - vertical', 'inavii-social-feed-e'),
                        'icon' => 'eicon-arrow-down',
                    ),
                    'row-reverse' => array(
                        'title' => __('Row - reversed', 'inavii-social-feed-e'),
                        'icon' => 'eicon-arrow-left',
                    ),
                    'column-reverse' => array(
                        'title' => __('Column - reversed', 'inavii-social-feed-e'),
                        'icon' => 'eicon-arrow-up',
                    ),
                ),
                'default' => 'row',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__box' => 'flex-direction: {{VALUE}};',
                ),
            )
        );

        $widget->add_responsive_control(
            'box_buttons_margin',
            array(
                'label' => __('Margin', 'inavii-social-feed-e'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__box' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'load_more_box_gap',
            array(
                'label' => __('Gap', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__box' => 'gap: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'footer_box_top_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $widget->start_controls_tabs(
            'section_style_footer_box_tabs'
        );

        $widget->start_controls_tab(
            'section_style_footer_box_tab_follow_button',
            [
                'label' => esc_html__('Follow Button', 'inavii-social-feed-e'),
            ]
        );

        TabFollowButtonStyle::add($widget);
        TabNormalStyle::add($widget);
        TabHoverStyle::add($widget);

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'section_style_footer_box_tab_load_more',
            [
                'label' => esc_html__('Load More', 'inavii-social-feed-e'),
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => '!in',
                            'value' => array_values($widget->sliderCondition),
                        ),
                    ),
                ),
            ]
        );

        TabLoadMoreStyle::add($widget);
        TabLoadMoreNormalStyle::add($widget);
        TabLoadMoreHoverStyle::add($widget);

        $widget->end_controls_tab();

        $widget->end_controls_tabs();

        $widget->end_controls_section();
    }
}