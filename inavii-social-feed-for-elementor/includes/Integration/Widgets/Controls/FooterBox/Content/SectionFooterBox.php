<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\FooterBox\Content;


use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;

class SectionFooterBox implements ControlsInterface
{

    use VersionedFeaturesTrait;
    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_content_footer_box',
            array(
                'label' => esc_html__('Footer Box', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-footer',
            )
        );

        $widget->start_controls_tabs(
            'section_content_footer_box_tabs'
        );

        $widget->start_controls_tab(
            'section_content_footer_box_tab_follow_button',
            [
                'label' => esc_html__('Follow Button', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_control(
            'enable_follow_button',
            array(
                'label' => esc_html__('Show Follow Instagram Button', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => 'no',
            )
        );

        $widget->add_control(
            'follow_button_text',
            array(
                'label' => __('Instagram Button Text', 'inavii-social-feed-e'),
                'type' => Controls_Manager::TEXT,
                'default' => 'Follow on Instagram',
                'condition' => [
                    'enable_follow_button' => 'yes',
                ],
            )
        );

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'section_content_footer_box_tab_load_more',
            [
                'label' => esc_html__('Load More', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-caption',
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => '!in',
                            'value' => array_values($widget->sliderCondition),
                        ),
                    ),
                ),
            ]
        );

        $widget->add_control(
            'enable_load_more_button',
            array(
                'label' => esc_html__('Show Load More Button', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => 'no',
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        $widget->add_control(
            'load_more_number_posts_to_load',
            array(
                'label' => esc_html__('Number of Posts to Load', 'inavii-social-feed-e'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'step' => 1,
                'default' => 5,
                'condition' => [
                    'enable_load_more_button' => 'yes',
                ],
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        $widget->add_control(
            'load_more_button_text',
            array(
                'label' => __('Instagram Button Text', 'inavii-social-feed-e'),
                'type' => Controls_Manager::TEXT,
                'default' => 'Load More',
                'condition' => [
                    'enable_load_more_button' => 'yes',
                ],
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        $widget->add_control(
            'load_more_button_content_info',
            [
                'type' => Controls_Manager::ALERT,
                'alert_type' => 'info',
                'content' => esc_html__( 'The number of posts to be loaded can be changed globally in', 'inavii-social-feed-e' ) .
                    ' <a href="./admin.php?page=inavii-instagram-settings#/global-settings">' .
                    esc_html__( 'Global Settings » Max number of posts imported per account', 'inavii-social-feed-e' ) .
                    '</a>.',
                'condition' => [
                    'enable_load_more_button' => 'yes',
                ],
            ]
        );

        $widget->add_control(
            'tab_load_more_box_note',
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