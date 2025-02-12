<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\FooterBox\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;

class TabLoadMoreStyle
{
    use VersionedFeaturesTrait;

    public static function add($widget): void
    {
        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_footer_load_more_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Load More General', 'inavii-social-feed-e' ),
                    'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                ]
            );
        }

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'load_more_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '{{WRAPPER}} .inavii-button__load-more-button',
            )
        );

        $widget->add_responsive_control(
            'load_more_padding',
            array(
                'label' => __('Padding', 'inavii-social-feed-e'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__load-more-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'load_more_border_radius',
            array(
                'label' => __('Border Radius', 'inavii-social-feed-e'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__load-more-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        $widget->add_control(
            'load_more_text_spacing',
            array(
                'label' => __('Text spacing', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__load-more .inavii-button__text' => 'gap: {{SIZE}}{{UNIT}};',
                ),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        $widget->add_control(
            'tab_info_footer_load_more_icon_hr_style',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_footer_load_more_icon_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Loader', 'inavii-social-feed-e' ),
                    'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                ]
            );
        }

        $widget->add_control(
            'load_more_icon_color',
            array(
                'label' => __('Loader Color', 'inavii-social-feed-e'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__load-more .inavii-button__text::after' => 'border-color: {{VALUE}};',
                ),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        $widget->add_control(
            'load_more_icon_size',
            array(
                'label' => __('Loader Size', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__load-more .inavii-button__text::after' => 'height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};',
                ),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        $widget->add_control(
            'load_more_icon_weight',
            array(
                'label' => __('Loader Weight', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 10,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__load-more .inavii-button__text::after' => 'border-width: {{SIZE}}{{UNIT}};',
                ),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        $widget->add_control(
            'tab_info_footer_load_more_icon_bottom_hr_style',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );
    }
}