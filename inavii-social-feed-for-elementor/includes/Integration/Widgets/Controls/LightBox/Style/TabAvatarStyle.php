<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\LightBox\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Utils\VersionChecker;

class TabAvatarStyle
{

    use VersionedFeaturesTrait;
    public static function add($widget): void
    {
        $widget->start_controls_tab(
            'section_lightbox_avatar_style_tab',
            [
                'label' => esc_html__('Avatar', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'lightbox_avatar_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '#modal-{{ID}} .inner-swiper-slide__sidebar-username-text',
            )
        );

        $widget->add_control(
            'lightbox_avatar_color',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar-username-text' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'tab_lightbox_avatar_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();
    }
}