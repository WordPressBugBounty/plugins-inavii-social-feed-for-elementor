<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\FooterBox\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;

class TabHoverStyle
{
    use VersionedFeaturesTrait;

    public static function add($widget): void
    {
        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_footer_follow_button_hover_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Follow Button Hover', 'inavii-social-feed-e' ),
                ]
            );
        }

        $widget->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'follow_button_box_shadow_hover',
                'selector' => '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header):hover',
            )
        );

        $widget->add_control(
            'follow_button_color_hover',
            array(
                'label' => __('Text Color', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header):hover' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'follow_button_background_hover',
            array(
                'label' => __('Background Color', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header):hover' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'follow_button_border_hover',
                'selector' => '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header):hover',
            )
        );
    }
}