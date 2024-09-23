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
                'condition' => array(
                    'enable_follow_button' => 'yes',
                ),
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
                ),
                'default' => 'center',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__box' => 'justify-content: {{VALUE}};',
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

        $widget->add_responsive_control(
            'box_buttons_padding',
            array(
                'label' => __('Padding', 'inavii-social-feed-e'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-button__box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'footer_box_top_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        TabFollowButtonStyle::add($widget);
        TabNormalStyle::add($widget);
        TabHoverStyle::add($widget);

        $widget->end_controls_section();
    }
}