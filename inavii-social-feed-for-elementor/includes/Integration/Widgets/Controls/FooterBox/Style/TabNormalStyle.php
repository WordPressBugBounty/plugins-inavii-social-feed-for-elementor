<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\FooterBox\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;

class TabNormalStyle
{
    use VersionedFeaturesTrait;

    public static function add($widget): void
    {
        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_footer_follow_button_normal_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Follow Button Normal', 'inavii-social-feed-e' ),
                ]
            );
        }

        $widget->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'follow_button_box_shadow_normal',
                'selector' => '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header)',
            )
        );

        $widget->add_control(
            'follow_button_color_normal',
            array(
                'label' => __('Text Color', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header)' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'follow_button_background_normal',
            array(
                'label' => __('Background Color', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header)' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'follow_button_border_normal',
                'selector' => '{{WRAPPER}} .inavii-button__follow-instagram-button:not(.inavii__header)',
            )
        );

        $widget->add_control(
            'tab_info_footer_normal_hr_style',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );
    }
}