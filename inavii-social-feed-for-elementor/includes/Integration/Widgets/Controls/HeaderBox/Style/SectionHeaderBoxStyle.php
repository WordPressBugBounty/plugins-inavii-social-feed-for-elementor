<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\HeaderBox\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;

class SectionHeaderBoxStyle implements ControlsInterface {

    use VersionedFeaturesTrait;
    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_header_box',
            array(
                'label' => __('Header Box', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-header',
                'tab' => Controls_Manager::TAB_STYLE,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'relation' => 'or',
                            'terms' => array(
                                array(
                                    'name' => 'avatar_header_switch',
                                    'operator' => '==',
                                    'value' => 'yes',
                                ),
                                array(
                                    'name' => 'enable_header_follow_button',
                                    'operator' => '==',
                                    'value' => 'yes',
                                ),
                                array(
                                    'name' => 'bio_header_switch',
                                    'operator' => '==',
                                    'value' => 'yes',
                                ),
                                array(
                                    'name' => 'user_name_header_switch',
                                    'operator' => '==',
                                    'value' => 'yes',
                                ),
                            ),
                        ),
                    ),
                ),
            )
        );

        $widget->add_responsive_control(
            'header_box_alignment',
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
                'selectors' => array(
                    '{{WRAPPER}} .inavii__header-container'
                    => 'justify-content: {{VALUE}};',
                ),
            )
        );

        $widget->add_responsive_control(
            'header_box_padding',
            array(
                'label' => __('Padding', 'inavii-social-feed-e'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii__header-container'
                    => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'header_box_gap',
            array(
                'label' => __('Gap', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'default' => array(
                    'size' => 30,
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii__header-container' => 'gap: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'header_box_top_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $widget->start_controls_tabs(
            'section_header_box_style_tabs'
        );

        $widget->start_controls_tab(
            'section_header_box_avatar_style_tab',
            [
                'label' => esc_html__('Avatar', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_control(
            'section_header_box_avatar_style_tab_info',
            array(
                'type' => Controls_Manager::RAW_HTML,
                'raw' => $widget->controlInfo(),
                'classes' => 'inavii-pro__info',
                'content_classes' => 'elementor-descriptor',
                'conditions' => array(
                    'terms' => array(
                        array(
                            'relation' => 'and',
                            'terms' => array(
                                array(
                                    'name' => 'avatar_header_switch',
                                    'operator' => '!==',
                                    'value' => 'yes',
                                ),
                                array(
                                    'name' => 'user_name_header_switch',
                                    'operator' => '!==',
                                    'value' => 'yes',
                                ),
                            ),
                        ),
                    ),
                ),
            )
        );

        $widget->add_responsive_control(
            'header_avatar_size',
            array(
                'label' => __('Avatar Size', 'inavii'),
                'type' => Controls_Manager::SLIDER,
                'default' => array(
                    'unit' => 'px',
                ),
                'tablet_default' => array(
                    'unit' => 'px',
                ),
                'mobile_default' => array(
                    'unit' => 'px',
                ),
                'size_units' => array('px'),
                'range' => array(
                    'px' => array(
                        'min' => 1,
                        'max' => 90,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii__header-avatar-content'
                    => 'height: {{SIZE}}{{UNIT}}; min-height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};min-width: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'avatar_header_switch' => 'yes',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'header_avatar_border',
                'selector' => '{{WRAPPER}} .inavii__header-avatar-content',
                'condition' => array(
                    'avatar_header_switch' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'header_avatar_spacing',
            array(
                'label' => __('Spacing', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 50,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii__header-avatar-content' => 'margin-right: {{SIZE}}{{UNIT}};',
                ),
                'conditions' => array(
                    'relation' => 'and',
                    'terms' => array(
                        array(
                            'name' => 'avatar_header_switch',
                            'operator' => '===',
                            'value' => 'yes',
                        ),
                        array(
                            'name' => 'user_name_header_switch',
                            'operator' => '===',
                            'value' => 'yes',
                        ),
                    ),
                ),
            )
        );

        $widget->add_responsive_control(
            'header_username_text_alignment',
            array(
                'label' => __('Text Alignment', 'inavii-social-feed-e'),
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
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii__header-bio-box'
                    => 'justify-content: {{VALUE}};',
                ),
                'condition' => array(
                    'user_name_header_switch' => 'yes',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'header_nickname_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '{{WRAPPER}} .inavii__header-username-text',
                'condition' => array(
                    'user_name_header_switch' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'header_nickname_color',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii__header-username-text' => 'color: {{VALUE}};',
                ),
                'condition' => array(
                    'user_name_header_switch' => 'yes',
                ),
            )
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'section_header_box_follow_button_style_tab',
            [
                'label' => esc_html__('Follow Button', 'inavii-social-feed-e'),
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_header_follow_button_style_info',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'content' =>  $widget->controlInfo(),
                    'conditions' => array(
                        'terms' => array(
                            array(
                                'terms' => array(
                                    array(
                                        'name' => 'enable_header_follow_button',
                                        'operator' => '!==',
                                        'value' => 'yes',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ]
            );
        }

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'follow_button_header_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header',
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_responsive_control(
            'follow_button_header_margin',
            array(
                'label' => __('Margin', 'inavii-social-feed-e'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_responsive_control(
            'follow_button_header_padding',
            array(
                'label' => __('Padding', 'inavii-social-feed-e'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'follow_button_header_border_radius',
            array(
                'label' => __('Border Radius', 'inavii-social-feed-e'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'follow_button_header_state_normal_top_hr',
            [
                'type' => Controls_Manager::DIVIDER,
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'follow_button_header_state_normal',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'NORMAL', 'inavii-social-feed-e' ),
                    'condition' => array(
                        'enable_header_follow_button' => 'yes',
                    ),
                ]
            );
        }

        $widget->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'follow_button_header_box_shadow_normal',
                'selector' => '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header',
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'follow_button_header_color_normal',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header' => 'color: {{VALUE}};',
                ),
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'follow_button_header_background_normal',
            array(
                'label' => __('Background Color', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header' => 'background-color: {{VALUE}};',
                ),
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'follow_button_header_border_normal',
                'selector' => '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header',
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'follow_button_header_state_hover_hr',
            [
                'type' => Controls_Manager::DIVIDER,
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'follow_button_header_state_hover',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'HOVER', 'inavii-social-feed-e' ),
                    'condition' => array(
                        'enable_header_follow_button' => 'yes',
                    ),
                ]
            );
        }

        $widget->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'follow_button_header_box_shadow_hover',
                'selector' => '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header:hover',
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'follow_button_header_color_hover',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header:hover' => 'color: {{VALUE}};',
                ),
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'follow_button_header_background_hover',
            array(
                'label' => __('Background Color', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header:hover' => 'background-color: {{VALUE}};',
                ),
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'follow_button_header_border_hover',
                'selector' => '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header:hover',
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'follow_button_header_state_hover_bottom_hr',
            [
                'type' => Controls_Manager::DIVIDER,
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'follow_header_separator_icon_title',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'FOLLOW ICON', 'inavii-social-feed-e' ),
                    'condition' => array(
                        'enable_header_follow_button' => 'yes',
                    ),
                ]
            );
        }

        $widget->add_control(
            'header_follow_icon_button',
            array(
                'label' => __('Icon', 'inavii-social-feed-e'),
                'type' => Controls_Manager::ICONS,
                'default' => array(
                    'value' => 'fab fa-instagram',
                    'library' => 'fa-brands',
                ),
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'follow_header_icon_size',
            array(
                'label' => __('Icon Size', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header span i:before' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header span svg' => 'height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'follow_header_text_spacing',
            array(
                'label' => __('Text spacing', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'default' => array(
                    'size' => 5,
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header .inavii-button__text' => 'margin-right: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'follow_header_icon_color',
            array(
                'label' => __('Icon Color', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header span i:before' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header span svg *' => 'fill: {{VALUE}}!important;',
                ),
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'follow_header_icon_color_hover',
            array(
                'label' => __('Icon Color Hover', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header:hover span i:before' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .inavii-button__follow-instagram-button.inavii__header:hover span svg *' => 'fill: {{VALUE}}!important;',
                ),
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'section_header_box_bio_style_tab',
            [
                'label' => esc_html__('Bio', 'inavii-social-feed-e'),
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_header_box_bio_style_info',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'content' =>  $widget->controlInfo(),
                    'conditions' => array(
                        'terms' => array(
                            array(
                                'terms' => array(
                                    array(
                                        'name' => 'bio_header_switch',
                                        'operator' => '!==',
                                        'value' => 'yes',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ]
            );
        }

        $widget->add_responsive_control(
            'bio_text_alignment',
            array(
                'label' => __('Text Alignment', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'left' => array(
                        'title' => __('Left', 'inavii-social-feed-e'),
                        'icon' => 'eicon-h-align-left',
                    ),
                    'center' => array(
                        'title' => __('Center', 'inavii-social-feed-e'),
                        'icon' => 'eicon-h-align-center',
                    ),
                    'right' => array(
                        'title' => __('Right', 'inavii-social-feed-e'),
                        'icon' => 'eicon-h-align-right',
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii__header-bio'
                    => 'text-align: {{VALUE}};',
                ),
                'conditions' => [
                    'terms' => [
                        [
                            'relation' => 'or',
                            'terms' => [
                                [
                                    'name' => 'bio_header_switch',
                                    'operator' => '===',
                                    'value' => 'yes',
                                ],
                                [
                                    'name' => 'bio_header_switch',
                                    'operator' => '==',
                                    'value' => self::defaultValueFreeEmpty(),
                                ],
                            ],
                        ],
                    ],
                ],
            )
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'bio_header_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'selector' => '{{WRAPPER}} .inavii__header-bio',
                'conditions' => [
                    'terms' => [
                        [
                            'relation' => 'or',
                            'terms' => [
                                [
                                    'name' => 'bio_header_switch',
                                    'operator' => '===',
                                    'value' => 'yes',
                                ],
                                [
                                    'name' => 'bio_header_switch',
                                    'operator' => '==',
                                    'value' => self::defaultValueFreeEmpty(),
                                ],
                            ],
                        ],
                    ],
                ],
            )
        );

        $widget->add_control(
            'bio_header_color_normal',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .inavii__header-bio' => 'color: {{VALUE}};',
                ),
                'conditions' => [
                    'terms' => [
                        [
                            'relation' => 'or',
                            'terms' => [
                                [
                                    'name' => 'bio_header_switch',
                                    'operator' => '===',
                                    'value' => 'yes',
                                ],
                                [
                                    'name' => 'bio_header_switch',
                                    'operator' => '==',
                                    'value' => self::defaultValueFreeEmpty(),
                                ],
                            ],
                        ],
                    ],
                ],
            )
        );

        $widget->add_responsive_control(
            'bio_header_padding',
            array(
                'label' => __('Padding', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii__header-bio' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
                'conditions' => [
                    'terms' => [
                        [
                            'relation' => 'or',
                            'terms' => [
                                [
                                    'name' => 'bio_header_switch',
                                    'operator' => '===',
                                    'value' => 'yes',
                                ],
                                [
                                    'name' => 'bio_header_switch',
                                    'operator' => '==',
                                    'value' => self::defaultValueFreeEmpty(),
                                ],
                            ],
                        ],
                    ],
                ],
            )
        );

        $widget->add_control(
            'tab_header_box_bio_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->add_control(
            'tab_header_box_bio_style_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $widget->end_controls_tab();

        $widget->end_controls_tabs();

        $widget->end_controls_section();

    }
}