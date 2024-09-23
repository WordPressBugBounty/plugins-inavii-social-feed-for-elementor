<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\Slider\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\Slider\SliderLayoutConditions;

class SectionSliderStyle implements ControlsInterface
{
    use VersionedFeaturesTrait;

    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_style_slider',
            array(
                'label' => __('Slider', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-slider',
                'tab' => Controls_Manager::TAB_STYLE,
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

        $widget->start_controls_tabs(
            'section_slider_style_tabs'
        );

        $widget->start_controls_tab(
            'section_slider_navigation_style_tab',
            [
                'label' => esc_html__('Navigation', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_control(
            'navigation_vertical_position',
            array(
                'label' => __('Vertical position', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'top' => array(
                        'title' => __('Top', 'inavii-social-feed-e'),
                        'icon' => 'eicon-v-align-top',
                    ),
                    'middle' => array(
                        'title' => __('Middle', 'inavii-social-feed-e'),
                        'icon' => 'eicon-v-align-middle',
                    ),
                    'bottom' => array(
                        'title' => __('Bottom', 'inavii-social-feed-e'),
                        'icon' => 'eicon-v-align-bottom',
                    ),
                ),
                'default' => 'middle',
                'prefix_class' => 'inavii-arrow-vertical-',
                'toggle' => true,
            )
        );

        $widget->add_responsive_control(
            'box_navigation_pagination_width',
            array(
                'label' => __('Navigation Box Width ', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'default' => array(
                    'unit' => '%',
                ),
                'tablet_default' => array(
                    'unit' => '%',
                ),
                'mobile_default' => array(
                    'unit' => '%',
                ),
                'size_units' => array('%', 'px', 'vw'),
                'range' => array(
                    '%' => array(
                        'min' => 1,
                        'max' => 100,
                    ),
                    'px' => array(
                        'min' => 1,
                        'max' => 1000,
                    ),
                    'vw' => array(
                        'min' => 1,
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-carousel-navigation' => 'width: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'arrow_border_radius',
            array(
                'label' => __('Border Radius', 'mns'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .swiper-button-next, {{WRAPPER}} .swiper-button-prev' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'arrow_normal_state_normal_top_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'arrow_normal_state_normal',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__('NORMAL', 'inavii-social-feed-e'),
                ]
            );
        }

        $widget->add_control(
            'arrow_size_normal',
            array(
                'label' => __('Size', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .swiper-button-next, 
            {{WRAPPER}} .swiper-button-prev'
                    => 'font-size: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .swiper-button-next svg, 
            {{WRAPPER}} .swiper-button-prev svg' => 'width: calc({{SIZE}}{{UNIT}} / 2); height: auto;',
                ),
            )
        );

        $widget->add_control(
            'arrow_color_normal',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .swiper-button-next, {{WRAPPER}} .swiper-button-prev' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .swiper-button-next svg *, {{WRAPPER}} .swiper-button-prev svg *' => 'fill: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'arrow_bg_color_normal',
            array(
                'label' => __('Background color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(122,60,255, .5)',
                'selectors' => array(
                    '{{WRAPPER}} .swiper-button-next, {{WRAPPER}} .swiper-button-prev' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'arrow_border_normal',
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'selector' => '{{WRAPPER}} .swiper-button-next, {{WRAPPER}} .swiper-button-prev',
            )
        );

        $widget->add_control(
            'arrow_hover_top_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'arrow_hover',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__('HOVER', 'inavii-social-feed-e'),
                ]
            );
        }

        $widget->add_control(
            'arrow_size_hover',
            array(
                'label' => __('Size', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .swiper-button-next:hover, {{WRAPPER}} .swiper-button-prev:hover' => 'font-size: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .swiper-button-next:hover svg, {{WRAPPER}} .swiper-button-prev:hover svg' => 'width: calc({{SIZE}}{{UNIT}} / 2) !important; height: auto;',
                ),
            )
        );

        $widget->add_control(
            'arrow_color_hover',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .swiper-button-next:hover, {{WRAPPER}} .swiper-button-prev:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .swiper-button-next:hover svg *, {{WRAPPER}} .swiper-button-prev:hover svg *' => 'fill: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'arrow_bg_color_hover',
            array(
                'label' => __('Background color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(122,60,255, 1)',
                'selectors' => array(
                    '{{WRAPPER}} .swiper-button-next:hover, {{WRAPPER}} .swiper-button-prev:hover' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'arrow_border_hover',
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'selector' => '{{WRAPPER}} .swiper-button-next:hover, {{WRAPPER}} .swiper-button-prev:hover',
            )
        );

        $widget->add_control(
            'tab_navigation_slider_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->add_control(
            'slider_bottom_section_style_tab_navigation_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'pagination_style_tab',
            [
                'label' => esc_html__('Pagination', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_control(
            'pagination_horizontal_position',
            array(
                'label' => __('Horizontal position', 'inavii-social-feed-e'),
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
                'default' => 'center',
                'prefix_class' => 'inavii-pagination-horizontal-',
                'toggle' => true,
            )
        );

        $widget->add_responsive_control(
            'pagination_padding',
            array(
                'label' => __('Padding', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .swiper-pagination' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'pagination_spacing',
            array(
                'label' => __('Spacing', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .swiper-pagination .swiper-pagination-bullet' => 'margin: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .swiper-pagination-fraction .swiper-pagination-current' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .swiper-pagination-fraction .swiper-pagination-total' => 'margin-left: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'pagination_normal_top_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'pagination_normal',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__('NORMAL', 'inavii-social-feed-e'),
                ]
            );
        }

        $widget->add_control(
            'pagination_size_height_normal',
            array(
                'label' => __('Size Height', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .swiper-pagination .swiper-pagination-bullet' => 'height: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'pagination_type' => 'bullets',
                ),
            )
        );

        $widget->add_control(
            'pagination_size_width_normal',
            array(
                'label' => __('Size Width', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .swiper-pagination .swiper-pagination-bullet' => 'width: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'pagination_type' => 'bullets',
                ),
            )
        );

        $widget->add_control(
            'pagination_size_normal',
            array(
                'label' => __('Size', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .swiper-pagination-fraction' => 'font-size: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'pagination_type' => 'fraction',
                ),
            )
        );

        $widget->add_control(
            'pagination_color_normal',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'default' => false,
                'selectors' => array(
                    '{{WRAPPER}} .swiper-pagination .swiper-pagination-bullet' => 'background: {{VALUE}};',
                    '{{WRAPPER}} .swiper-pagination-fraction' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'pagination_hover_top_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'pagination_hover_info',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__('HOVER', 'inavii-social-feed-e'),
                ]
            );
        }

        $widget->add_control(
            'pagination_color_hover',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'default' => '#7A3CFF',
                'selectors' => array(
                    '{{WRAPPER}} .swiper-pagination .swiper-pagination-bullet:hover' => 'background: {{VALUE}};',
                    '{{WRAPPER}} .swiper-pagination-fraction:hover' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'pagination_active_top_hr',
            [
                'type' => Controls_Manager::DIVIDER,
                'condition' => array(
                    'pagination_type' => 'bullets',
                ),
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'pagination_hover',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__('ACTIVE', 'inavii-social-feed-e'),
                    'condition' => array(
                        'pagination_type' => 'bullets',
                    ),
                ]
            );
        }

        $widget->add_control(
            'pagination_size_height_active',
            array(
                'label' => __('Size Height', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .swiper-pagination .swiper-pagination-bullet.swiper-pagination-bullet-active' => 'height: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'pagination_type' => 'bullets',
                ),
            )
        );

        $widget->add_control(
            'pagination_size_width_active',
            array(
                'label' => __('Size Width', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .swiper-pagination .swiper-pagination-bullet.swiper-pagination-bullet-active' => 'width: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'pagination_type' => 'bullets',
                ),
            )
        );

        $widget->add_control(
            'pagination_size_active',
            array(
                'label' => __('Size', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .swiper-pagination-fraction' => 'font-size: {{SIZE}}{{UNIT}};',
                ),
                'condition' => array(
                    'pagination_type' => 'fraction',
                ),
            )
        );

        $widget->add_control(
            'pagination_color_active',
            array(
                'label' => __('Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'default' => '#7A3CFF',
                'selectors' => array(
                    '{{WRAPPER}} .swiper-pagination .swiper-pagination-bullet.swiper-pagination-bullet-active' => 'background: {{VALUE}};',
                ),
                'condition' => array(
                    'pagination_type' => 'bullets',
                ),
            )
        );

        $widget->add_control(
            'tab_pagination_slider_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->add_control(
            'slider_bottom_section_style_tab_pagination_hr',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $widget->end_controls_tab();

        $widget->end_controls_tabs();

        $widget->end_controls_section();
    }
}