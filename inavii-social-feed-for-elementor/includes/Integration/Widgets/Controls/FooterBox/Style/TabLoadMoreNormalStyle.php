<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\FooterBox\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;

class TabLoadMoreNormalStyle
{
    use VersionedFeaturesTrait;

    public static function add($widget): void
    {
        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_footer_load_more_normal_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Load More Normal', 'inavii-social-feed-e' ),
                ]
            );
        }

        $widget->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'load_more_box_shadow_normal',
                'selector' => '{{WRAPPER}} .inavii-button__load-more-button',
            )
        );

        $widget->add_control(
            'load_more_color_normal',
            array(
                'label' => __('Text Color', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__load-more-button .inavii-button__text' => 'color: {{VALUE}};',
                ),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        $widget->add_control(
            'load_more_background_normal',
            array(
                'label' => __('Background Color', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__load-more-button' => 'background-color: {{VALUE}};',
                ),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'load_more_border_normal',
                'selector' => '{{WRAPPER}} .inavii-button__load-more-button',
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        $widget->add_control(
            'tab_info_footer_load_more_normal_hr_style',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );
    }
}