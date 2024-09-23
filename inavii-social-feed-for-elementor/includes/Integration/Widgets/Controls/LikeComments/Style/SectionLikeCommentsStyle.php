<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\LikeComments\Style;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;
use Inavii\Instagram\Utils\VersionChecker;

class SectionLikeCommentsStyle implements ControlsInterface
{

    use VersionedFeaturesTrait;
    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_like_comments_style',
            array(
                'label' => esc_html__('Likes & Comments', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-likes-comments',
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );


        $widget->start_controls_tabs(
            'tabs_likes_comment_style'
        );

        $widget->start_controls_tab(
            'tab_box_likes__comment_style',
            [
                'label' => esc_html__('Box', 'inavii-social-feed-e'),
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_box_likes_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Likes', 'inavii-social-feed-e' ),
                ]
            );
        }

        $widget->add_responsive_control(
            'general_likes_padding',
            array(
                'label' => __('Padding', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-like-count' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'label' => __('Typography', 'inavii-social-feed-e'),
                'name' => 'general_likes_typography',
                'selector' => '{{WRAPPER}} .inavii-like-count .inavii-grid__icon-text',
            )
        );

        $widget->add_control(
            'general_likes_color',
            array(
                'label' => __('Text Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-like-count .inavii-grid__icon-text' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'general_likes_icon_color',
            array(
                'label' => __('Icon Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-like-count i:before' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'general_likes_icon_size',
            array(
                'label' => __('Icon Size', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 50,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid__item .inavii-like-count i' => 'font-size: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_box_comment_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Comments', 'inavii-social-feed-e' ),
                ]
            );
        }

        $widget->add_responsive_control(
            'general_comments_padding',
            array(
                'label' => __('Padding', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-comment-count' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'general_comments_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '{{WRAPPER}} .inavii-comment-count .inavii-grid__icon-text',
            )
        );

        $widget->add_control(
            'general_comments_color',
            array(
                'label' => __('Text Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-comment-count .inavii-grid__icon-text' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'general_comments_icon_color',
            array(
                'label' => __('Icon Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-comment-count i:before' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'general_comments_icon_size',
            array(
                'label' => __('Icon Size', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 50,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid__item .inavii-comment-count i' => 'font-size: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_box_date_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Date', 'inavii-social-feed-e' ),
                ]
            );
        }

        $widget->add_responsive_control(
            'general_date_padding',
            array(
                'label' => __('Padding', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .inavii-grid__icons-box-left' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'general_date_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '{{WRAPPER}} .inavii-grid__icons-box-left .inavii-grid__icon-text',
            )
        );

        $widget->add_control(
            'general_date_color',
            array(
                'label' => __('Text Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-grid__icons-box-left .inavii-grid__icon-text' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'general_date_icon_color',
            array(
                'label' => __('Icon Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => array(
                    '{{WRAPPER}} .inavii-grid__icons-box-left i:before' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'general_date_icon_size',
            array(
                'label' => __('Icon Size', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 50,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}}.elementor-widget-inavii-grid .inavii-grid__item .inavii-grid__icons-box-left i' => 'font-size: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'tab_like_comments_style_note',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'tab_lightbox_likes__comment_style',
            [
                'label' => esc_html__('Lightbox', 'inavii-social-feed-e'),
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_lightbox_likes_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Likes', 'inavii-social-feed-e' ),
                ]
            );
        }

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'lightbox_likes_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '#modal-{{ID}} .inavii__lightbox-like-box',
            )
        );

        $widget->add_control(
            'lightbox_likes_color',
            array(
                'label' => __('Text Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inavii__lightbox-like-box' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'lightbox_likes_icon_color',
            array(
                'label' => __('Icon Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inavii__lightbox-like-box i::before' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'lightbox_likes_icon_size',
            array(
                'label' => __('Icon Size', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 50,
                    ),
                ),
                'selectors' => array(
                    '#modal-{{ID}} .inavii__lightbox-like-box i::before' => 'font-size: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_lightbox_comments_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Comments', 'inavii-social-feed-e' ),
                ]
            );
        }

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'lightbox_comments_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '#modal-{{ID}} .inavii__lightbox-comments-box',
            )
        );

        $widget->add_control(
            'lightbox_comments_color',
            array(
                'label' => __('Text Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inavii__lightbox-comments-box' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'lightbox_comments_icon_color',
            array(
                'label' => __('Icon Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inavii__lightbox-comments-box i::before' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'lightbox_comments_icon_size',
            array(
                'label' => __('Icon Size', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 50,
                    ),
                ),
                'selectors' => array(
                    '#modal-{{ID}} .inavii__lightbox-comments-box i::before' => 'font-size: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_lightbox_date_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'Date', 'inavii-social-feed-e' ),
                ]
            );
        }

        $widget->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'lightbox_date_typography',
                'label' => __('Typography', 'inavii-social-feed-e'),
                'selector' => '#modal-{{ID}} .inavii__lightbox-date-box',
            )
        );

        $widget->add_control(
            'lightbox_date_color',
            array(
                'label' => __('Text Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inavii__lightbox-date-box' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'lightbox_date_icon_color',
            array(
                'label' => __('Icon Color', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '#modal-{{ID}} .inavii__lightbox-date-box i::before' => 'color: {{VALUE}};',
                ),
            )
        );

        $widget->add_control(
            'lightbox_date_icon_size',
            array(
                'label' => __('Icon Size', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 50,
                    ),
                ),
                'selectors' => array(
                    '#modal-{{ID}} .inavii__lightbox-date-box i::before' => 'font-size: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $widget->add_control(
            'tab_lightbox_like_comments_style_note',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();

        $widget->end_controls_tabs();

        $widget->end_controls_section();
    }
}