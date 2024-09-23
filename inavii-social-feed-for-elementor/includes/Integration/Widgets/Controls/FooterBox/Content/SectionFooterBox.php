<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\FooterBox\Content;


use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;

class SectionFooterBox implements ControlsInterface
{

    use VersionedFeaturesTrait;
    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_content_footer_box',
            array(
                'label' => esc_html__('Footer Box', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-footer',
            )
        );

        $widget->add_control(
            'enable_follow_button',
            array(
                'label' => esc_html__('Show Follow Instagram Button', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => 'no',
            )
        );

        $widget->add_control(
            'follow_button_text',
            array(
                'label' => __('Instagram Button Text', 'inavii-social-feed-e'),
                'type' => Controls_Manager::TEXT,
                'default' => 'Follow on Instagram',
                'condition' => [
                    'enable_follow_button' => 'yes',
                ],
            )
        );

        $widget->end_controls_section();
    }
}