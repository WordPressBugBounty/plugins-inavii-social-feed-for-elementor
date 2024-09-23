<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\LightBox\Style;

use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Utils\VersionChecker;

class TabCloseButtonStyle
{
    use VersionedFeaturesTrait;

    public static function add($widget): void
    {
        $widget->start_controls_tab(
            'lightbox_close_button_style_tab',
            [
                'label' => esc_html__('Close Button', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_control(
            'lightbox_close_button_icon_size',
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
            'lightbox_close_button_color',
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
            'lightbox_close_button_bg_color',
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
            'tab_lightbox_close_button_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();
    }
}