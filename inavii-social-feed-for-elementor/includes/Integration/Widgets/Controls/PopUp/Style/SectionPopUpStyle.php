<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\PopUp\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;
use Inavii\Instagram\Utils\VersionChecker;

class SectionPopUpStyle implements ControlsInterface {

    use VersionedFeaturesTrait;
    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_modal_popup',
            array(
                'label' => __('PopUp', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-popup',
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'post_linking' => 'popup',
                ),
            )
        );

        $widget->start_controls_tabs(
            'section_popup_style_tabs'
        );

        $widget->start_controls_tab(
            'section_popup_overlay_style_tab',
            [
                'label' => esc_html__('Overlay', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_control(
            'overlay_bg_color',
            array(
                'label' => __('Background Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .modal-inavii__overlay' => 'background: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'tab_popup_overlay_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'popup_close_button_style_tab',
            [
                'label' => esc_html__('Close Button', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_control(
            'popup_close_button_icon_size',
            array(
                'label' => __('Size', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '#modal-{{ID}} .modal-inavii__close' => 'height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'popup_close_button_color',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .modal-inavii__close svg *' => 'fill: {{VALUE}}!important;',
                ),
            )
        );

        $widget->add_control(
            'popup_close_button_bg_color',
            array(
                'label' => __('Background color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .modal-inavii__close' => 'background: {{VALUE}}!important;',
                ),
            )
        );

        $widget->add_control(
            'tab_popup_close_button_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();

        $widget->end_controls_tabs();

        $widget->add_control(
            'section_modal_popup_buttons_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $widget->start_controls_tabs(
            'section_popup_style_tabs_buttons'
        );

        $widget->start_controls_tab(
            'popup_follow_button_style_tab',
            [
                'label' => esc_html__('Follow Button', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_responsive_control(
            'popup_follow_button_padding',
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
            'popup_follow_button_radius',
            array(
                'label' => __('Border Radius', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar:not(.inner-swiper-slide__sidebar-promotion)' =>
                        'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'popup_follow_button_gap',
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
            'popup_follow_button_icon_size',
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

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'popup_follow_button_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:not(.inner-swiper-slide__sidebar-button-promotion) .inner-swiper-slide__sidebar-text',
            )
        );

        $widget->add_control(
            'popup_follow_button__color',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:not(.inner-swiper-slide__sidebar-button-promotion) i:before,
                #modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:not(.inner-swiper-slide__sidebar-button-promotion) .inner-swiper-slide__sidebar-text' => 'color: {{VALUE}};',
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:not(.inner-swiper-slide__sidebar-button-promotion) svg *' => 'fill: {{VALUE}}!important;',
                ),
            )
        );
        $widget->add_control(
            'popup_follow_button_color_hover',
            array(
                'label' => __('Color Hover', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:not(.inner-swiper-slide__sidebar-button-promotion):hover i:before,
                #modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:not(.inner-swiper-slide__sidebar-button-promotion):hover .inner-swiper-slide__sidebar-text' => 'color: {{VALUE}};',
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:not(.inner-swiper-slide__sidebar-button-promotion):hover svg *' => 'fill: {{VALUE}}!important;',
                ),
            )
        );

        $widget->add_control(
            'popup_follow_button__bg_color',
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
            'popup_follow_button_bg_color_hover',
            array(
                'label' => __('Background Color Hover', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-view-instagram-button:not(.inner-swiper-slide__sidebar-button-promotion):hover' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'tab_popup_follow_button_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'popup_follow_button_promotion_style_tab',
            [
                'label' => esc_html__('Promote Button', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_responsive_control(
            'popup_promote_button_padding',
            array(
                'label' => __('Padding', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-button-promotion'
                    => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'popup_promote_button_radius',
            array(
                'label' => __('Border Radius', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-promotion' =>
                        'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'popup_promote_button_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '#modal-{{ID}} .inner-swiper-slide__sidebar-button-promotion .inner-swiper-slide__sidebar-text',
            )
        );

        $widget->add_control(
            'popup_promote_button__color',
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
            'popup_promote_button_color_hover',
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
            'popup_promote_button__bg_color',
            array(
                'label' => __('Background Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-button-promotion:not(:hover)' => 'background: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'popup_promote_button_bg_color_hover',
            array(
                'label' => __('Background Color Hover', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-button-promotion:hover' => 'background: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'tab_popup_promote_button_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();

        $widget->end_controls_tabs();

        $widget->end_controls_section();

    }
}