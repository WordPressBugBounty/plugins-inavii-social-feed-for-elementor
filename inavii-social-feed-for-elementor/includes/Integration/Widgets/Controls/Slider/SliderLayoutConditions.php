<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\Slider;

use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;

class SliderLayoutConditions
{
    use VersionedFeaturesTrait;

    public static function get($widget)
    {
        $commonConditions = array_merge(
            array_values($widget->rowCondition),
            array_values($widget->gridCondition),
            array_values($widget->waveCondition),
            array_values($widget->galleryCondition),
            array_values($widget->highlightCondition),
            array_values($widget->highlightSuperCondition),
            array_values($widget->highlightFocusCondition),
            array_values($widget->masonryHorizontalCondition),
            array_values($widget->masonryVerticalCondition),
            array_values($widget->waveGridCondition),
            array_values($widget->sliderCondition),
            array_values($widget->cardsCondition)
        );

        return self::isFree() ? $commonConditions : array_merge(array_values($widget->sliderCondition));
    }
}