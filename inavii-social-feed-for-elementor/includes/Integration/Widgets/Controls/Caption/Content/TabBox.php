<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\Caption\Content;

use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;

class TabBox
{
    use VersionedFeaturesTrait;

    public static function add($widget): void
    {
        $widget->start_controls_tab(
            'section_content_general_style_tab_1',
            [
                'label' => esc_html__('Box', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_control(
            'post_caption',
            array(
                'label' => esc_html__('Show Captions', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => self::defaultValueForVersion(),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        $widget->add_control(
            'tab_caption_box_note',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();
    }
}