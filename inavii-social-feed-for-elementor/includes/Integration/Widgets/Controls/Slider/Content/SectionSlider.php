<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\Slider\Content;

use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\Slider\SliderLayoutConditions;

class SectionSlider implements ControlsInterface
{
    use VersionedFeaturesTrait;

    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'carousel',
            array(
                'label' => esc_html__('Slider', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-slider',
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => SliderLayoutConditions::get($widget),
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'autoplay',
            array(
                'type' => Controls_Manager::SWITCHER,
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'label' => __('Autoplay', 'inavii-social-feed-e'),
                'label_on' => __('Yes', 'inavii-social-feed-e'),
                'label_off' => __('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => self::defaultValueForVersion(),
            )
        );

        $widget->add_control(
            'autoplay_speed',
            array(
                'label' => __('Autoplay speed(delay between slides)', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::NUMBER,
                'return_value' => 'yes',
                'default' => '3000',
                'condition' => array(
                    'autoplay' => self::defaultValueForVersion(),
                ),
            )
        );

        $widget->add_control(
            'speed',
            array(
                'label' => __('Speed of transitions', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::NUMBER,
                'return_value' => 'yes',
                'default' => '300',
            )
        );

        $widget->add_control(
            'loop',
            array(
                'type' => Controls_Manager::SWITCHER,
                'label' => __('Infinite Loop', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'label_on' => __('Yes', 'inavii-social-feed-e'),
                'label_off' => __('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => 'no',
                'conditions' => array(
                    'relation' => 'or',
                    'terms' => array(
                        array(
                            'name' => 'row_active',
                            'operator' => '!==',
                            'value' => 'yes',
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'display_pagination',
            array(
                'type' => Controls_Manager::SWITCHER,
                'label' => __('Display pagination', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'label_on' => __('Show', 'inavii-social-feed-e'),
                'label_off' => __('Hide', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => self::defaultValueForVersion(),
                'prefix_class' => 'inavii-pagination-',
            )
        );

        $widget->add_control(
            'pagination_type',
            array(
                'label' => __('Pagination type', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'bullets' => 'Bullets',
                    'fraction' => 'Fraction',
                ),
                'default' => 'bullets',
                'condition' => array(
                    'display_pagination' => self::defaultValueForVersion(),
                ),
            )
        );

        $widget->add_control(
            'display_navigation',
            array(
                'type' => Controls_Manager::SWITCHER,
                'label' => __('Display navigation', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'label_on' => __('Show', 'inavii-social-feed-e'),
                'label_off' => __('Hide', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => false,
            )
        );

        $widget->add_control(
            'navigation_icon_prev',
            array(
                'label' => __('Previous slide icon', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::ICONS,
                'default' => array(
                    'value' => 'fas fa-arrow-left',
                    'library' => 'fa-solid',
                ),
                'condition' => array(
                    'display_navigation' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'navigation_icon_next',
            array(
                'label' => __('Next slide icon', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::ICONS,
                'default' => array(
                    'value' => 'fas fa-arrow-right',
                    'library' => 'fa-solid',
                ),
                'condition' => array(
                    'display_navigation' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'autoplay_pause_on_hover',
            array(
                'type' => Controls_Manager::SWITCHER,
                'label' => __('Pause on hover', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'label_on' => __('Yes', 'inavii-social-feed-e'),
                'label_off' => __('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => false,
                'condition' => array(
                    'autoplay' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'tab_navigation_slider_get_pro_content',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->add_control(
            'slider_bottom_section_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $widget->end_controls_section();
    }
}