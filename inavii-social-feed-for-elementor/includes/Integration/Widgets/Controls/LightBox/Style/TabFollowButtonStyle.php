<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\LightBox\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Utils\VersionChecker;

class TabFollowButtonStyle
{

    use VersionedFeaturesTrait;
    public static function add($widget): void
    {
        $widget->start_controls_tab(
            'lightbox_follow_button_style_tab',
            [
                'label' => esc_html__('Follow Button', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_responsive_control(
            'lightbox_follow_button_padding',
            array(
                'label' => __('Padding', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:not(.inner-swiper-slide__sidebar-button-promotion)'
                    => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'lightbox_follow_button_gap',
            array(
                'label' => __('Text Spacing', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button' => 'gap: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'lightbox_follow_button_icon_size',
            array(
                'label' => __('Icon Size', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button i:before' => 'font-size: {{SIZE}}{{UNIT}};',
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button svg' => 'height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'lightbox_follow_button_background_color',
            array(
                'label' => __('Background Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:not(.inner-swiper-slide__sidebar-button-promotion):not(:hover)' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'lightbox_follow_button_background_color_hover',
            array(
                'label' => __('Background Color Hover', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:not(.inner-swiper-slide__sidebar-button-promotion):hover' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'lightbox_follow_button_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:not(.inner-swiper-slide__sidebar-button-promotion) .inner-swiper-slide__sidebar-text',
            )
        );

        $widget->add_control(
            'lightbox_follow_button__color',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button i:before,
                #modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:not(.inner-swiper-slide__sidebar-button-promotion) .inner-swiper-slide__sidebar-text' => 'color: {{VALUE}};',
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button svg *' => 'fill: {{VALUE}}!important;',
                ),
            )
        );

        $widget->add_control(
            'lightbox_follow_button_color_hover',
            array(
                'label' => __('Color Hover', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:hover i:before,
                #modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:not(.inner-swiper-slide__sidebar-button-promotion):hover .inner-swiper-slide__sidebar-text' => 'color: {{VALUE}};',
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:hover svg *' => 'fill: {{VALUE}}!important;',
                ),
            )
        );

        $widget->add_control(
            'tab_lightbox_follow_button_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();
    }
}