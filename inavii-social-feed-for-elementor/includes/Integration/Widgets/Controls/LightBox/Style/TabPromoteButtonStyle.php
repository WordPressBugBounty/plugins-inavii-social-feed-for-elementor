<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\LightBox\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Utils\VersionChecker;

class TabPromoteButtonStyle
{

    use VersionedFeaturesTrait;
    public static function add($widget): void
    {
        $widget->start_controls_tab(
            'lightbox_promote_button_style_tab',
            [
                'label' => esc_html__('Promote Button', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_responsive_control(
            'lightbox_promote_button_padding',
            array(
                'label' => __('Padding', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button.inner-swiper-slide__sidebar-button-promotion'
                    => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'lightbox_promote_button_bg_color',
            array(
                'label' => __('Background Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-button-promotion:not(:hover)' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'lightbox_promote_button_bg_color_hover',
            array(
                'label' => __('Background Color Hover', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-button-promotion:hover' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'lightbox_promote_button_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '#modal-{{ID}} .inner-swiper-slide__sidebar-button-promotion .inner-swiper-slide__sidebar-text',
            )
        );

        $widget->add_control(
            'lightbox_promote_button_color',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-button-promotion .inner-swiper-slide__sidebar-text' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'lightbox_promote_button_color_hover',
            array(
                'label' => __('Color Hover', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-button-promotion:hover .inner-swiper-slide__sidebar-text' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'tab_lightbox_promote_button_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();
    }
}