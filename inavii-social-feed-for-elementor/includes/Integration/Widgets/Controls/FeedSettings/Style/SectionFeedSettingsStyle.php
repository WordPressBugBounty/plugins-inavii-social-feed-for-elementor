<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\FeedSettings\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Css_Filter;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;

class SectionFeedSettingsStyle implements ControlsInterface
{

    use VersionedFeaturesTrait;
    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_images_style',
            array(
                'label' => __('Feed Settings', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-feed-settings',
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );

        $widget->add_responsive_control(
            'object-fit',
            [
                'label' => __('Object Fit', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    '' => 'Default',
                    'fill' => 'Fill',
                    'cover' => 'Cover',
                    'contain' => 'Contain',
                ),
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .inavii-grid__item-box .inavii-grid__image' => 'object-fit: {{VALUE}};',
                ],
            ]
        );

        $widget->add_control(
            'items_border_radius',
            array(
                'label' => __('Items border radius', 'inavii-social-feed-e'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid .inavii-grid__item,
					{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid .inavii-grid__item .inavii-grid__image-box,
					{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid .inavii-grid__item .inavii-grid__item-box,
					{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid .inavii-grid__top-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_merge(array_values($widget->gridCondition),
                                array_values($widget->waveCondition), array_values($widget->waveGridCondition),
                                array_values($widget->highlightCondition), array_values($widget->highlightSuperCondition),
                                array_values($widget->highlightFocusCondition), array_values($widget->masonryHorizontalCondition),
                                array_values($widget->masonryVerticalCondition), array_values($widget->sliderCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'items_border_no_radius',
            array(
                'label' => __('Items border radius', 'inavii-social-feed-e'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'default' => array(
                    'top' => 0,
                    'right' => 0,
                    'bottom' => 0,
                    'left' => 0,
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid .inavii-grid__item,
					{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid .inavii-grid__item .inavii-grid__image-box,
					{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid .inavii-grid__item .inavii-grid__item-box,
					{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid .inavii-grid__item .inavii-grid__top-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_merge(array_values($widget->galleryCondition),
                                array_values($widget->rowCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'separator-style',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $widget->start_controls_tabs('image-effects');

        TabNormalStyle::add($widget);
        TabHoverStyle::add($widget);

        $widget->end_controls_tabs();

        $widget->end_controls_section();
    }
}