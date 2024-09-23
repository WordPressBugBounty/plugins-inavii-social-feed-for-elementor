<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\LikeComments\Content;

use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Utils\VersionChecker;

class TabLightBox
{
    use VersionedFeaturesTrait;

    public static function add($widget)
    {
        $widget->start_controls_tab(
            'section_content_popup_tab',
            [
                'label' => esc_html__('PopUp', 'inavii-social-feed-e'),
                'condition' => array(
                    'post_linking' => 'popup',
                ),
            ]
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'alert_popup_available_option_comments_and_likes',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'warning',
                    'heading' => esc_html__( 'Option not available', 'inavii-social-feed-e' ),
                    'content' => esc_html__( 'This option is only compatible with the LightBox view.', 'inavii-social-feed-e' ),
                    'condition' => [
                        'post_linking' => 'popup',
                    ],
                ]
            );
        }

        $widget->end_controls_tab();

        $widget->start_controls_tab(
            'section_content_lightbox_style_tab',
            [
                'label' => esc_html__('Lightbox', 'inavii-social-feed-e'),
                'condition' => array(
                    'post_linking' => 'lightbox',
                ),
            ]
        );

        $widget->add_control(
            'date_lightbox_switch',
            array(
                'label' => esc_html__('Show Date', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => self::defaultValueForVersion(),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        $widget->add_control(
            'likes_lightbox_switch',
            array(
                'label' => esc_html__('Show Likes Count', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => self::defaultValueForVersion(),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_box_likes_comments_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'This option only works with a business Instagram account.', 'inavii-social-feed-e' ),
                    'condition' => array(
                        'likes_lightbox_switch' => 'yes',
                    ),
                ]
            );
        }

        $widget->add_control(
            'comments_lightbox_switch',
            array(
                'label' => esc_html__('Show Comments Count', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => self::defaultValueForVersion(),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'tab_info_box_comments_style',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'This option only works with a business Instagram account.', 'inavii-social-feed-e' ),
                    'condition' => array(
                        'comments_lightbox_switch' => 'yes',
                    ),
                ]
            );
        }

        $widget->add_control(
            'tab_lightbox_like_comments_note',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();
    }
}