<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\Caption\Style;

use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;

class SectionCaptionStyle implements ControlsInterface {

    use VersionedFeaturesTrait;
    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_caption_style',
            array(
                'label' => esc_html__('Captions', 'inavii-social-feed-e'),
                'tab' => Controls_Manager::TAB_STYLE,
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-caption',
            )
        );

        $widget->start_controls_tabs(
            'section_general_middle_style_tabs'
        );

        TabBoxStyle::add($widget);
        TabLightboxStyle::add($widget);

        $widget->end_controls_tabs();

        $widget->end_controls_section();
    }
}