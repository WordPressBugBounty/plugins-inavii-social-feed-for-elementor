<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\Video\Content;

use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;

class SectionVideo
{
    use VersionedFeaturesTrait;

    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_video',
            array(
                'label' => esc_html__('Video Playback', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-video',
            )
        );

        $widget->add_control(
            'show_video_playback',
            array(
                'label' => esc_html__('Enable video playback', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'default' => self::defaultValueForVersion(),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'show_video_playback_info',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'content' => esc_html__( 'Enabling this option allows video playback in a popup or lightbox when the play button is clicked.'),
                ]
            );
        }

        $widget->end_controls_section();
    }
}