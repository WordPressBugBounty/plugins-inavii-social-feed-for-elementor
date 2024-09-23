<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\LightBox\Style;

use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Utils\VersionChecker;

class TabOverlayStyle
{

    use VersionedFeaturesTrait;
    public static function add($widget): void
    {
        $widget->start_controls_tab(
            'section_lightbox_overlay_style_tab',
            [
                'label' => esc_html__('Overlay', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_control(
            'overlay_lightbox_bg_color',
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
            'tab_lightbox_overlay_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();
    }
}