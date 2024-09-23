<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\Caption\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Utils\VersionChecker;

class TabBoxStyle
{
    use VersionedFeaturesTrait;

    public static function add($widget): void
    {
        $widget->start_controls_tab(
            'section_general_overlay_style_tab',
            [
                'label' => esc_html__('Box', 'inavii-social-feed-e'),
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_box_caption_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Box', 'inavii-social-feed-e' ),
                ]
            );
        }

        $widget->add_control(
            'general_overlay_vertical_position',
            array(
                'label' => __('Vertical Position', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'flex-start' => array(
                        'title' => __('Top', 'inavii-social-feed-e'),
                        'icon' => 'eicon-v-align-top',
                    ),
                    'center' => array(
                        'title' => __('Center', 'inavii-social-feed-e'),
                        'icon' => 'eicon-v-align-middle',
                    ),
                    'flex-end' => array(
                        'title' => __('Bottom', 'inavii-social-feed-e'),
                        'icon' => 'eicon-v-align-bottom',
                    ),
                    'space-between' => array(
                        'title' => __('Space Between', 'inavii-social-feed-e'),
                        'icon' => 'eicon-v-align-stretch',
                    ),
                ),
                'default' => 'space-between',
                'toggle' => true,
                'selectors' => array(
                    '{{WRAPPER}} .inavii-grid__box-description' => 'justify-content: {{VALUE}};',
                ),
                'conditions' => array(
                    'relation' => 'and',
                    'terms' => array(
                        array(
                            'name' => 'layout',
                            'operator' => 'in',
                            'value' => ['overlay', 'flip-box'],
                        ),
                        array(
                            'name' => 'feeds_layout',
                            'operator' => '!in',
                            'value' => array_values($widget->cardsCondition),
                        ),
                    ),
                ),

            )
        );

        $widget->add_control(
            'general_overlay_bg_color',
            array(
                'label' => __('Background Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .inavii-grid .inavii-grid__item.grid-item .inavii-grid__box-description' => 'background-color: {{VALUE}}!important;',
                ),
            )
        );

        $widget->add_responsive_control(
            'general_overlay_padding',
            array(
                'label' => __('Padding', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-grid__box-description' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_caption_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Caption', 'inavii-social-feed-e' ),
                ]
            );
        }

        $widget->add_responsive_control(
            'general_description_line_clamp',
            [
                'label' => __('Line Clamp', 'inavii'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ),
                'default' => '3',
                'selectors' => [
                    '{{WRAPPER}} .inavii-grid__description' => '-webkit-line-clamp: {{VALUE}};',
                ],
            ]
        );

        $widget->add_responsive_control(
            'general_description_padding',
            array(
                'label' => __('Margin', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-grid__description' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_responsive_control(
            'general_description_text_alignment',
            array(
                'label' => __('Text Alignment', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'left' => array(
                        'title' => __('Left', 'inavii-social-feed-e'),
                        'icon' => 'eicon-h-align-left',
                    ),
                    'center' => array(
                        'title' => __('Center', 'inavii-social-feed-e'),
                        'icon' => 'eicon-h-align-center',
                    ),
                    'right' => array(
                        'title' => __('Right', 'inavii-social-feed-e'),
                        'icon' => 'eicon-h-align-right',
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-grid__description'
                    => 'text-align: {{VALUE}};',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'general_description_typography',
                'label' => __('Typography', 'plugin-domain'),
                'selector' => '{{WRAPPER}} .inavii-grid__description',
            )
        );

        $widget->add_control(
            'general_description_color',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-grid__description' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'tab_box_caption_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();
    }
}