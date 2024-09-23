<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\LightBox\Style;

use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;

class SectionLightBoxStyle implements ControlsInterface {

    use VersionedFeaturesTrait;
    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_modal_lightbox',
            array(
                'label' => __('Lightbox', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-lightbox',
                'tab' => Controls_Manager::TAB_STYLE,
                'conditions' => [
                    'terms' => [
                        [
                            'relation' => 'or',
                            'terms' => [
                                [
                                    'name' => 'post_linking',
                                    'operator' => '==',
                                    'value' => 'lightbox',
                                ],
                                [
                                    'name' => 'post_linking',
                                    'operator' => '==',
                                    'value' => self::isFree() ? 'none' : 'null',
                                ],
                                [
                                    'name' => 'post_linking',
                                    'operator' => '==',
                                    'value' => self::isFree() ? 'popup' : 'null',
                                ],
                                [
                                    'name' => 'post_linking',
                                    'operator' => '==',
                                    'value' => self::isFree() ? 'lightbox' : 'null',
                                ],
                            ],
                        ],
                    ],
                ],
            )
        );

        $widget->add_control(
            'lightbox_follow_button__bg_color',
            array(
                'label' => __('Sidebar Background Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inner-swiper-slide__sidebar' => 'background: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'section_lightbox_follow_button_bg_color_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $widget->start_controls_tabs(
            'section_lightbox_middle_style_tabs'
        );

        TabOverlayStyle::add($widget);
        TabCloseButtonStyle::add($widget);

        $widget->end_controls_tabs();

        $widget->add_control(
            'section_modal_lightbox_middle_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $widget->start_controls_tabs(
            'section_lightbox_buttons_style_tabs'
        );

        TabAvatarStyle::add($widget);
        TabFollowButtonStyle::add($widget);
        TabPromoteButtonStyle::add($widget);

        $widget->end_controls_tabs();

        $widget->add_control(
            'section_modal_lightbox_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $widget->end_controls_section();
    }
}