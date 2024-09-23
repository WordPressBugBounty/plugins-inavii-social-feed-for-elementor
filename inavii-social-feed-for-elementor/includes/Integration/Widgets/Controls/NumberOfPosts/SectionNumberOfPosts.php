<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\NumberOfPosts;

use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;

class SectionNumberOfPosts implements ControlsInterface
{

    use VersionedFeaturesTrait;

    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_query',
            array(
                'label' => esc_html__('Number of posts', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-number-of-posts',
                'conditions' => [
                    'terms' => [
                        [
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => self::getLayoutConditions($widget)
                        ],
                    ],
                ],
            )
        );

        $widget->add_control(
            'posts_counter',
            array(
                'label' => esc_html__('Number of Posts on Desktop', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'default' => 20,
                'min' => 1,
                'step' => 1,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => '!in',
                            'value' => array_merge(array_values($widget->rowCondition),
                                array_values($widget->waveCondition),
                                array_values($widget->gridCondition),
                                array_values($widget->galleryCondition),
                                array_values($widget->highlightCondition),
                                array_values($widget->highlightSuperCondition),
                                array_values($widget->highlightFocusCondition),
                                array_values($widget->masonryHorizontalCondition),
                                array_values($widget->masonryVerticalCondition),
                                array_values($widget->waveGridCondition),
                                array_values($widget->sliderCondition),
                                array_values($widget->cardsCondition),
                                array_values($widget->shapeMatrixCondition),
                                array_values($widget->contentGridCondition),
                                array_values($widget->creativeGridCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'posts_counter_grid',
            array(
                'label' => esc_html__('Number of Posts on Desktop', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'default' => 20,
                'min' => 1,
                'step' => 1,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_merge(array_values($widget->gridCondition),
                                array_values($widget->masonryVerticalCondition), array_values($widget->cardsCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'posts_counter_highlight',
            array(
                'label' => esc_html__('Number of Posts on Desktop', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'default' => 12,
                'min' => 1,
                'step' => 1,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_values($widget->highlightCondition),
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'posts_counter_highlight_super',
            array(
                'label' => esc_html__('Number of Posts on Desktop', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'default' => 5,
                'min' => 1,
                'step' => 1,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_merge(array_values($widget->highlightSuperCondition), array_values($widget->rowCondition),
                                array_values($widget->waveCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'posts_counter_desktop_6',
            array(
                'label' => esc_html__('Number of Posts on Desktop', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'default' => 6,
                'min' => 1,
                'step' => 1,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_values($widget->contentGridCondition),
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'posts_counter_highlight_focus',
            array(
                'label' => esc_html__('Number of Posts on Desktop', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'default' => 13,
                'min' => 1,
                'step' => 1,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_merge(array_values($widget->highlightFocusCondition),
                                array_values($widget->creativeGridCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'posts_counter_masonry_horizontal',
            array(
                'label' => esc_html__('Number of Posts on Desktop', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'default' => 18,
                'min' => 1,
                'step' => 1,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_merge(array_values($widget->masonryHorizontalCondition), array_values($widget->shapeMatrixCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'posts_counter_slider',
            array(
                'label' => esc_html__('Number of Posts on Desktop', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'default' => 8,
                'min' => 1,
                'step' => 1,
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
            'posts_counter_gallery',
            array(
                'label' => esc_html__('Number of Posts on Desktop', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'default' => 15,
                'min' => 1,
                'step' => 1,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_merge(array_values($widget->galleryCondition),
                                array_values($widget->waveGridCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'posts_counter_mobile',
            array(
                'label' => esc_html__('Number of Posts on Mobile', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'default' => 6,
                'min' => 1,
                'step' => 1,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => '!in',
                            'value' => array_merge(array_values($widget->waveCondition),
                                array_values($widget->rowCondition),
                                array_values($widget->waveGridCondition), array_values($widget->highlightFocusCondition),
                                array_values($widget->highlightSuperCondition), array_values($widget->shapeMatrixCondition),
                                array_values($widget->contentGridCondition), array_values($widget->creativeGridCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'posts_counter_mobile_9',
            array(
                'label' => esc_html__('Number of Posts on Mobile', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'default' => 9,
                'min' => 1,
                'step' => 1,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_values($widget->waveGridCondition)
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'posts_counter_mobile_7',
            array(
                'label' => esc_html__('Number of Posts on Mobile', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'default' => 7,
                'min' => 1,
                'step' => 1,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_values($widget->highlightFocusCondition)
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'posts_counter_mobile_5',
            array(
                'label' => esc_html__('Number of Posts on Mobile', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'default' => 5,
                'min' => 1,
                'step' => 1,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_merge(array_values($widget->highlightSuperCondition),
                                array_values($widget->shapeMatrixCondition),
                                array_values($widget->creativeGridCondition))
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'posts_counter_mobile_3',
            array(
                'label' => esc_html__('Number of Posts on Mobile', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'default' => 3,
                'min' => 1,
                'step' => 1,
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => 'in',
                            'value' => array_merge(array_values($widget->rowCondition), array_values($widget->waveCondition), array_values($widget->contentGridCondition))
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'feed_offset',
            [
                'label' => esc_html__( 'Offset', 'inavii-social-feed-e' ),
                'type' => Controls_Manager::NUMBER,
                'description' => esc_html__( 'Specify the number of posts to skip at the beginning of the results list.', 'inavii-social-feed-e' ),
                'default' => 0,
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            ]
        );

        $widget->end_controls_section();
    }

    private static function getLayoutConditions($widget)
    {
        $commonConditions = array_merge(
            array_values($widget->rowCondition),
            array_values($widget->gridCondition),
            array_values($widget->galleryCondition),
            array_values($widget->highlightCondition),
            array_values($widget->highlightSuperCondition),
            array_values($widget->highlightFocusCondition),
            array_values($widget->masonryHorizontalCondition),
            array_values($widget->masonryVerticalCondition),
            array_values($widget->waveGridCondition),
            array_values($widget->sliderCondition),
            array_values($widget->cardsCondition),
            array_values($widget->shapeMatrixCondition),
            array_values($widget->contentGridCondition),
            array_values($widget->creativeGridCondition)
        );

        return self::isFree() ? $commonConditions : array_merge(array_values($widget->waveCondition), $commonConditions);
    }
}