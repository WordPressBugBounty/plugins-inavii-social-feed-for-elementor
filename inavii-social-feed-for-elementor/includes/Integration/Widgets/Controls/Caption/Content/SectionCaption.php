<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\Caption\Content;

use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;

class SectionCaption
{
    use VersionedFeaturesTrait;

    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_caption',
            array(
                'label' => esc_html__('Captions', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-caption',
            )
        );

        $widget->start_controls_tabs(
            'section_caption_tabs'
        );

        TabBox::add($widget);
        TabLightBox::add($widget);

        $widget->end_controls_tabs();

        $widget->end_controls_section();
    }
}