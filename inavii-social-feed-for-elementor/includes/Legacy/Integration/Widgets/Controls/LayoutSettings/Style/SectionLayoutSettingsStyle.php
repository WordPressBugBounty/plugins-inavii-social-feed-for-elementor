<?php

namespace Inavii\Instagram\Includes\Legacy\Integration\Widgets\Controls\LayoutSettings\Style;

use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Legacy\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Legacy\Integration\Widgets\Controls\ControlsInterface;
use Inavii\Instagram\Includes\Legacy\Integration\Widgets\Controls\Slider\SliderLayoutConditions;
use Inavii\Instagram\Freemius\FreemiusAccess;

class SectionLayoutSettingsStyle implements ControlsInterface
{

    use VersionedFeaturesTrait;
    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_general_style',
            array(
                'label' => __('Layout Settings', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-layout-settings',
                'tab' => Controls_Manager::TAB_STYLE,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => '!in',
                            'value' => array_merge(array_values($widget->shapeMatrixCondition), array_values($widget->creativeGridCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->add_responsive_control(
            'item-column',
            array(
                'label' => __('Column', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                ),
                'selectors' => array(
                    '{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid' =>
                        'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ),
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => '!in',
                            'value' => array_merge(array_values($widget->rowCondition),
                                array_values($widget->waveCondition), array_values($widget->waveGridCondition),
                                array_values($widget->highlightCondition), array_values($widget->highlightSuperCondition),
                                array_values($widget->highlightFocusCondition), array_values($widget->masonryHorizontalCondition),
                                array_values($widget->masonryVerticalCondition), array_values($widget->sliderCondition),
                                array_values($widget->cardsCondition), array_values($widget->contentGridCondition)),
                        ),
                    ),
                ),
            )
        );

        if (FreemiusAccess::canUsePremiumCode()) {
            if (FreemiusAccess::canUsePremiumCode()) {
                $widget->add_responsive_control(
                    'slider_height',
                    array(
                        'label' => __('Height of Slider on the Desktop (px)', 'inavii-social-feed-e'),
                        'type' => Controls_Manager::SLIDER,
                        'range' => array(
                            'px' => array(
                                'min' => 1,
                                'max' => 1000,
                            ),
                        ),
                        'conditions' => array(
                            'terms' => array(
                                array(
                                    'name' => 'feeds_layout',
                                    'operator' => 'in',
                                    'value' => array_values($widget->sliderCondition),
                                ),
                            ),
                        ),
                    )
                );

                $widget->add_control(
                    'cards_columns_slider_hr_style',
                    [
                        'type' => Controls_Manager::DIVIDER,
                        'conditions' => array(
                            'terms' => array(
                                array(
                                    'name' => 'feeds_layout',
                                    'operator' => 'in',
                                    'value' => array_values($widget->sliderCondition),
                                ),
                            ),
                        ),
                    ]
                );

                $widget->start_controls_tabs('cards_columns',
                    array(
                        'conditions' => array(
                            'terms' => array(
                                array(
                                    'name' => 'feeds_layout',
                                    'operator' => 'in',
                                    'value' => array_values($widget->cardsCondition),
                                ),
                            ),
                        ),
                    ));

                $widget->start_controls_tab(
                    'cards_columns_desktop',
                    array(
                        'label' => __('Desktop', 'inavii-social-feed-e'),
                    )
                );

                $widget->add_control(
                    'item_column_cards',
                    array(
                        'label' => __('Column', 'inavii-social-feed-e'),
                        'type' => Controls_Manager::SELECT,
                        'default' => '4',
                        'options' => array(
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                            '5' => '5',
                            '6' => '6',
                            '7' => '7',
                            '8' => '8',
                            '9' => '9',
                            '10' => '10',
                        ),
                    )
                );

                $widget->end_controls_tab();

                $widget->start_controls_tab(
                    'cards_columns_tablet',
                    array(
                        'label' => __('Tablet', 'inavii-social-feed-e'),
                    )
                );

                $widget->add_control(
                    'cards_columns_tablet_value',
                    array(
                        'label' => __('Column', 'inavii-social-feed-e'),
                        'type' => Controls_Manager::SELECT,
                        'default' => '3',
                        'options' => array(
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                            '5' => '5',
                            '6' => '6',
                        ),
                    )
                );

                $widget->end_controls_tab();

                $widget->start_controls_tab(
                    'cards_columns_mobile',
                    array(
                        'label' => __('Mobile', 'inavii-social-feed-e'),
                    )
                );

                $widget->add_control(
                    'cards_columns_mobile_value',
                    array(
                        'label' => __('Column', 'inavii-social-feed-e'),
                        'type' => Controls_Manager::SELECT,
                        'default' => '2',
                        'options' => array(
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                        ),
                    )
                );

                $widget->end_controls_tab();

                $widget->end_controls_tabs();
            }
        }

        $widget->add_responsive_control(
            'item-column-wave-grid',
            array(
                'label' => __('Column', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SELECT,
                'default' => '5',
                'tablet_default' => '4',
                'mobile_default' => '3',
                'selectors' => array(
                    '{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid__type-wave,
                    {{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid__type-wave-grid' =>
                        'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ),
                'classes' => 'elementor-hidden',
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_merge(array_values($widget->waveCondition), array_values($widget->waveGridCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->add_responsive_control(
            'item-column-row',
            array(
                'label' => __('Column', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SELECT,
                'default' => '5',
                'tablet_default' => '3',
                'mobile_default' => '3',
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                    '7' => '7',
                    '8' => '8',
                    '9' => '9',
                    '10' => '10',
                ),
                'selectors' => array(
                    '{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid__type-row' =>
                        'grid-template-columns: repeat({{VALUE}}, 1fr);',
                ),
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_values($widget->rowCondition),
                        ),
                    ),
                ),
            )
        );

        if (FreemiusAccess::canUsePremiumCode()) {
            if (FreemiusAccess::canUsePremiumCode()) {
                $widget->start_controls_tabs('masonry_columns',
                    array(
                        'conditions' => array(
                            'terms' => array(
                                array(
                                    'name' => 'feeds_layout',
                                    'operator' => 'in',
                                    'value' => array_values($widget->masonryVerticalCondition),
                                ),
                            ),
                        ),
                    ));

                $widget->start_controls_tab(
                    'masonry_columns_desktop',
                    array(
                        'label' => __('Desktop', 'inavii-social-feed-e'),
                    )
                );

                $widget->add_control(
                    'masonry_columns_desktop_value',
                    array(
                        'label' => __('Column', 'inavii-social-feed-e'),
                        'type' => Controls_Manager::SELECT,
                        'default' => '6',
                        'options' => array(
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                            '5' => '5',
                            '6' => '6',
                            '7' => '7',
                            '8' => '8',
                            '9' => '9',
                            '10' => '10',
                        ),
                    )
                );

                $widget->end_controls_tab();

                $widget->start_controls_tab(
                    'masonry_columns_tablet',
                    array(
                        'label' => __('Tablet', 'inavii-social-feed-e'),
                    )
                );

                $widget->add_control(
                    'masonry_columns_tablet_value',
                    array(
                        'label' => __('Column', 'inavii-social-feed-e'),
                        'type' => Controls_Manager::SELECT,
                        'default' => '3',
                        'options' => array(
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                            '5' => '5',
                            '6' => '6',
                        ),
                    )
                );

                $widget->end_controls_tab();

                $widget->start_controls_tab(
                    'masonry_columns_mobile',
                    array(
                        'label' => __('Mobile', 'inavii-social-feed-e'),
                    )
                );

                $widget->add_control(
                    'masonry_columns_mobile_value',
                    array(
                        'label' => __('Column', 'inavii-social-feed-e'),
                        'type' => Controls_Manager::SELECT,
                        'default' => '2',
                        'options' => array(
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                        ),
                    )
                );

                $widget->end_controls_tab();

                $widget->end_controls_tabs();
            }
        }

        if (FreemiusAccess::canUsePremiumCode()) {
            if (FreemiusAccess::canUsePremiumCode()) {
                $widget->add_control(
                    'item-column-slider',
                    array(
                        'label' => __('Number of Columns on the Desktop', 'inavii-social-feed-e'),
                        'type' => Controls_Manager::SELECT,
                        'default' => '4',
                        'options' => array(
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                            '5' => '5',
                            '6' => '6',
                        ),
                        'conditions' => array(
                            'terms' => array(
                                array(
                                    'name' => 'feeds_layout',
                                    'operator' => 'in',
                                    'value' => array_values($widget->sliderCondition),
                                ),
                            ),
                        ),
                    )
                );

                $widget->add_control(
                    'item_column_slider_tablet',
                    array(
                        'label' => __('Number of Columns on the Tablet', 'inavii-social-feed-e'),
                        'type' => Controls_Manager::SELECT,
                        'default' => '2',
                        'options' => array(
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                            '5' => '5',
                            '6' => '6',
                        ),
                        'conditions' => array(
                            'terms' => array(
                                array(
                                    'name' => 'feeds_layout',
                                    'operator' => 'in',
                                    'value' => array_values($widget->sliderCondition),
                                ),
                            ),
                        ),
                    )
                );

                $widget->add_control(
                    'item_column_slider_mobile',
                    array(
                        'label' => __('Number of Columns on the Mobile', 'inavii-social-feed-e'),
                        'type' => Controls_Manager::SELECT,
                        'default' => '1',
                        'options' => array(
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                        ),
                        'conditions' => array(
                            'terms' => array(
                                array(
                                    'name' => 'feeds_layout',
                                    'operator' => 'in',
                                    'value' => array_values($widget->sliderCondition),
                                ),
                            ),
                        ),
                    )
                );

                $widget->add_control(
                    'cards_columns_slider_bottom_hr_style',
                    [
                        'type' => Controls_Manager::DIVIDER,
                        'conditions' => array(
                            'terms' => array(
                                array(
                                    'name' => 'feeds_layout',
                                    'operator' => 'in',
                                    'value' => array_values($widget->sliderCondition),
                                ),
                            ),
                        ),
                    ]
                );
            }
        }

        $widget->add_control(
            'row_active',
            array(
                'type' => Controls_Manager::SWITCHER,
                'label' => __('Row', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'label_on' => __('Yes', 'inavii-social-feed-e'),
                'label_off' => __('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => 'no',
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => SliderLayoutConditions::get($widget)
                        ),
                    ),
                ),
            )
        );

        if (FreemiusAccess::canUsePremiumCode()) {
            if (FreemiusAccess::canUsePremiumCode()) {
                $widget->add_control(
                    'item_row',
                    array(
                        'label' => __('Rows number', 'inavii-social-feed-e'),
                        'type' => Controls_Manager::SELECT,
                        'default' => 'null',
                        'options' => array(
                            'null' => '1',
                            '2' => '2',
                            '3' => '3',
                        ),
                        'conditions' => array(
                            'operator' => 'and',
                            'terms' => array(
                                array(
                                    'name' => 'feeds_layout',
                                    'operator' => 'in',
                                    'value' => array_values($widget->sliderCondition),
                                ),
                                array(
                                    'name' => 'row_active',
                                    'operator' => '==',
                                    'value' => 'yes'
                                ),
                            ),
                        ),
                    )
                );
            }
        }

        $widget->add_responsive_control(
            'items-gap',
            array(
                'label' => __('Items gap', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 100,
                    ),
                ),
                'default' => array(
                    'size' => 10,
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-grid__type-masonry-vertical .grid-item' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid:not(.inavii-grid.inavii-grid__type-cards):not(.inavii-grid.inavii-grid__type-shape-matrix)' => 'gap: {{SIZE}}{{UNIT}}!important;',
                    '{{WRAPPER}} .inavii-grid__type-masonry-horizontal .grid-item' => '--gap: {{SIZE}}{{UNIT}};',
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
                                array_values($widget->masonryVerticalCondition)),
                        ),
                    ),
                ),
            )
        );

        if (FreemiusAccess::canUsePremiumCode()) {
            if (FreemiusAccess::canUsePremiumCode()) {
                $widget->add_control(
                    'cards_gap_value',
                    array(
                        'label' => __('Items Gap', 'inavii-social-feed-e'),
                        'type' => Controls_Manager::SELECT,
                        'default' => '10',
                        'options' => array(
                            '5' => '5',
                            '10' => '10',
                            '15' => '15',
                            '20' => '20',
                            '25' => '25',
                            '30' => '30',
                            '35' => '35',
                            '40' => '40',
                            '45' => '45',
                            '50' => '50',
                            '55' => '55',
                            '60' => '60',
                            '65' => '65',
                            '70' => '70',
                            '75' => '75',
                            '80' => '80',
                            '85' => '85',
                            '90' => '90',
                            '95' => '95',
                            '100' => '100'
                        ),
                        'conditions' => array(
                            'terms' => array(
                                array(
                                    'name' => 'feeds_layout',
                                    'operator' => 'in',
                                    'value' => array_values($widget->cardsCondition),
                                ),
                            ),
                        ),
                    )
                );
            }
        }

        $widget->add_responsive_control(
            'items_no_gap',
            array(
                'label' => __('Items gap', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 100,
                    ),
                ),
                'default' => array(
                    'size' => 0,
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid.inavii-grid__type-row , 
                    {{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid.inavii-grid__type-gallery,
                    {{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid.inavii-grid__type-content-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ),
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_merge(array_values($widget->galleryCondition),
                                array_values($widget->rowCondition), array_values($widget->contentGridCondition)),
                        ),
                    ),
                ),
            )
        );

        if (FreemiusAccess::canUsePremiumCode()) {
            if (FreemiusAccess::canUsePremiumCode()) {
                $widget->add_responsive_control(
                    'space_between',
                    array(
                        'label' => __('Items gap', 'inavii-social-feed-e'),
                        'type' => Controls_Manager::SLIDER,
                        'size_units' => array('px'),
                        'range' => array(
                            'px' => array(
                                'min' => 0,
                                'max' => 100,
                            ),
                        ),
                        'default' => array(
                            'size' => 10,
                            'unit' => 'px',
                        ),
                        'conditions' => array(
                            'terms' => array(
                                array(
                                    'name' => 'feeds_layout',
                                    'operator' => 'in',
                                    'value' => array_values($widget->sliderCondition),
                                ),
                            ),
                        ),
                    )
                );
            }
        }

        $widget->add_responsive_control(
            'items_height',
            array(
                'label' => __('Items Height', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SLIDER,
                'default' => array(
                    'unit' => 'px',
                ),
                'tablet_default' => array(
                    'unit' => 'px',
                ),
                'mobile_default' => array(
                    'unit' => 'px',
                ),
                'size_units' => array('px', 'vw'),
                'range' => array(
                    'px' => array(
                        'min' => 1,
                        'max' => 1000,
                    ),
                    'vh' => array(
                        'min' => 1,
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid .grid-item' => 'height: {{SIZE}}{{UNIT}};--height-value: {{SIZE}};',
                ),
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => '!in',
                            'value' => array_merge(array_values($widget->highlightCondition),
                                array_values($widget->highlightSuperCondition), array_values($widget->highlightFocusCondition),
                                array_values($widget->masonryVerticalCondition), array_values($widget->cardsCondition), array_values($widget->sliderCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->end_controls_section();

    }
}
