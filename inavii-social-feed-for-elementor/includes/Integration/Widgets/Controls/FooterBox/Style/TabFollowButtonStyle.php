<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\FooterBox\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;

class TabFollowButtonStyle
{
    use VersionedFeaturesTrait;

    public static function add($widget): void
    {
        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_footer_follow_button_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Follow Button', 'inavii-social-feed-e' ),
                ]
            );
        }

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'follow_button_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header)',
            )
        );

        $widget->add_responsive_control(
            'follow_button_margin',
            array(
                'label' => __('Margin', 'inavii-social-feed-e'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header)' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_responsive_control(
            'follow_button_padding',
            array(
                'label' => __('Padding', 'inavii-social-feed-e'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header)' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'follow_button_border_radius',
            array(
                'label' => __('Border Radius', 'inavii-social-feed-e'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header)' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'tab_info_footer_follow_button_icon_hr_style',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_footer_follow_button_icon_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Follow Button Icon', 'inavii-social-feed-e' ),
                ]
            );
        }

        $widget->add_control(
            'follow_icon_button',
            array(
                'label' => __('Icon', 'inavii-social-feed-e'),
                'type' => Controls_Manager::ICONS,
                'default' => array(
                    'value' => 'fab fa-instagram',
                    'library' => 'fa-brands',
                ),
            )
        );

        $widget->add_control(
            'follow_icon_color',
            array(
                'label' => __('Icon Color', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header) span i:before' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header) span svg *' => 'fill: {{VALUE}}!important;',
                ),
            )
        );

        $widget->add_control(
            'follow_icon_size',
            array(
                'label' => __('Icon Size', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header) span i:before' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header) span svg' => 'height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'follow_text_spacing',
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
                    '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header) .inavii-button__text' => 'margin-right: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'follow_footer_icon_color_hover',
            array(
                'label' => __('Icon Color Hover', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header):hover span i:before' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header):hover span svg *' => 'fill: {{VALUE}}!important;',
                ),
                'condition' => array(
                    'enable_header_follow_button' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'tab_info_footer_icon_bottom_hr_style',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );
    }
}