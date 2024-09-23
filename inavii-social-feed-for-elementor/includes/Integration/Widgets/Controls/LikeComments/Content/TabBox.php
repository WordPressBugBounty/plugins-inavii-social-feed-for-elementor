<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\LikeComments\Content;

use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;

class TabBox
{
    use VersionedFeaturesTrait;

    public static function add($widget)
    {
        $widget->start_controls_tab(
            'section_content_general_style_tab',
            [
                'label' => esc_html__('Box', 'inavii-social-feed-e'),
            ]
        );

        $widget->add_control(
            'date_switch',
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
            'likes_switch',
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
                'likes_switch_info',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'This option only works with a business Instagram account.', 'inavii-social-feed-e' ),
                    'condition' => array(
                        'likes_switch' => 'yes',
                    ),
                ]
            );
        }

        $widget->add_control(
            'comments_switch',
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
                'comments_switch_info',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'heading' => esc_html__( 'This option only works with a business Instagram account.', 'inavii-social-feed-e' ),
                    'condition' => array(
                        'comments_switch' => 'yes',
                    ),
                ]
            );
        }

        $widget->add_control(
            'section_content_box_note',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_tab();
    }
}