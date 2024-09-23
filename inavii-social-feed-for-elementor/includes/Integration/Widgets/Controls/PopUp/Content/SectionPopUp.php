<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\PopUp\Content;

use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;

class SectionPopUp implements ControlsInterface
{

    use VersionedFeaturesTrait;
    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_popup_settings',
            array(
                'label' => esc_html__('PopUp', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-popup',
                'condition' => array(
                    'post_linking' => 'popup',
                ),
            )
        );

        $widget->add_control(
            'enable_popup_follow_button',
            array(
                'label' => esc_html__('Show Follow Post Button Text', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );

        $widget->add_control(
            'popup_follow_button_text',
            array(
                'label' => __('Instagram Button Text', 'inavii-social-feed-e'),
                'type' => Controls_Manager::TEXT,
                'default' => 'View on Instagram',
                'condition' => [
                    'enable_popup_follow_button' => 'yes',
                ],
            )
        );

        $widget->add_control(
            'enable_popup_icon_follow_button',
            array(
                'label' => esc_html__('Show Follow Post Button Icon', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );

        $widget->add_control(
            'popup_follow_icon_button',
            array(
                'label' => __('Icon', 'inavii-social-feed-e'),
                'type' => Controls_Manager::ICONS,
                'default' => array(
                    'value' => 'fab fa-instagram',
                    'library' => 'fa-brands',
                ),
                'condition' => [
                    'enable_popup_icon_follow_button' => 'yes',
                ],
            )
        );
        $widget->end_controls_section();
    }
}