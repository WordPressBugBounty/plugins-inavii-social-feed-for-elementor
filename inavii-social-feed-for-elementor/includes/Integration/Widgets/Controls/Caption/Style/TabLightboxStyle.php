<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\Caption\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;

class TabLightboxStyle
{
    use VersionedFeaturesTrait;

    public static function add($widget): void
    {
        $widget->start_controls_tab(
            'tab_lightbox_caption_style',
            [
                'label' => esc_html__('Lightbox', 'inavii-social-feed-e'),
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'alert_popup_available_option_caption',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'warning',
                    'heading' => esc_html__( 'Option not available', 'inavii-social-feed-e' ),
                    'content' => esc_html__( 'This option is only compatible with the LightBox view.', 'inavii-social-feed-e' ),
                    'condition' => [
                        'post_linking' => 'popup',
                    ],
                ]
            );
        }

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'lightbox_description_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '#modal-{{ID}} .inner-swiper-slide__sidebar-description',
            )
        );

        $widget->add_control(
            'lightbox_description_color',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-description' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'tab_lightbox_caption_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();
    }
}