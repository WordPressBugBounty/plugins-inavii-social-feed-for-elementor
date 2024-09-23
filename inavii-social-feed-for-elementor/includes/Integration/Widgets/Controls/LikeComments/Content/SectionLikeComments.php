<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\LikeComments\Content;

use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;

class SectionLikeComments implements ControlsInterface {

    use VersionedFeaturesTrait;
    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_like_comments',
            array(
                'label' => esc_html__('Likes & Comments', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-likes-comments',
            )
        );

        $widget->start_controls_tabs(
            'section_content_style_tabs_1'
        );

        TabBox::add($widget);
        TabLightBox::add($widget);

        $widget->end_controls_tabs(
            'section_content_style_tabs_1'
        );

        $widget->end_controls_section();
    }
}