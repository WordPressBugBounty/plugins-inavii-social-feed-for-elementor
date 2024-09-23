<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\LightBox\Content;

use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;

class SectionLightBox implements ControlsInterface{

    use VersionedFeaturesTrait;
    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_lightbox_settings',
            array(
                'label' => esc_html__('LightBox', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-lightbox',
                'conditions' => [
                    'terms' => [
                        [
                            'relation' => 'or',
                            'terms' => [
                                [
                                    'name' => 'post_linking',
                                    'operator' => '==',
                                    'value' => 'lightbox',
                                ],
                                [
                                    'name' => 'post_linking',
                                    'operator' => '==',
                                    'value' => self::isFree() ? 'none' : 'null',
                                ],
                                [
                                    'name' => 'post_linking',
                                    'operator' => '==',
                                    'value' => self::isFree() ? 'popup' : 'null',
                                ],
                                [
                                    'name' => 'post_linking',
                                    'operator' => '==',
                                    'value' => self::isFree() ? 'lightbox' : 'null',
                                ],
                            ],
                        ],
                    ],
                ],
            )
        );

        $widget->add_control(
            'avatar_lightbox_switch',
            array(
                'label' => esc_html__('Show Avatar', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => self::defaultValueForVersion() ,
            )
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'avatar_lightbox_content_info',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'content' => esc_html__( 'You can change your avatar in', 'inavii-social-feed-e' ) . ' <a href="./admin.php?page=inavii-instagram-settings#/accounts">' . esc_html__( 'Inavii Social feed » Accounts » Info', 'textdomain' ) . '</a>',
                    'condition' => [
                        'avatar_header_switch' => self::defaultValueForVersion(),
                    ],
                ]
            );
        }

        $widget->add_control(
            'user_name_lightbox_switch',
            array(
                'label' => esc_html__('Show Username / Hashtag Name', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => self::defaultValueForVersion(),
            )
        );

        $widget->add_control(
            'user_name_lightbox_choice',
            array(
                'label' => __('Name Display Choice', 'inavii-social-feed-e'),
                'label_block' => true,
                'type' => Controls_Manager::CHOOSE,
                'default' => 'username',
                'options' => array(
                    'username' => array(
                        'title' => __('Username', 'inavii-social-feed-e'),
                        'icon' => 'eicon-site-identity',
                    ),
                    'name' => array(
                        'title' => __('Name', 'inavii-social-feed-e'),
                        'icon' => 'eicon-site-identity',
                    ),
                ),
                'toggle' => true,
                'classes' => self::customChoicesClass(),
                'condition' => array(
                    'user_name_lightbox_switch' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'enable_lightbox_follow_button',
            array(
                'label' => esc_html__('Show Follow Post Button Text', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => self::defaultValueForVersion(),
            )
        );

        $widget->add_control(
            'lightbox_follow_button_text',
            array(
                'label' => __('Instagram Button Text', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::TEXT,
                'default' => 'View on Instagram',
                'condition' => [
                    'enable_lightbox_follow_button' => self::defaultValueForVersion(),
                ],
            )
        );

        $widget->add_control(
            'enable_lightbox_icon_follow_button',
            array(
                'label' => esc_html__('Show Follow Post Button Icon', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => self::defaultValueForVersion(),
            )
        );

        $widget->add_control(
            'lightbox_follow_icon_button',
            array(
                'label' => __('Icon', 'inavii-social-feed-e'),
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
                'type' => Controls_Manager::ICONS,
                'default' => array(
                    'value' => 'fab fa-instagram',
                    'library' => 'fa-brands',
                ),
                'condition' => [
                    'enable_lightbox_icon_follow_button' => self::defaultValueFreeEmpty(),
                ],
            )
        );

        $widget->add_control(
            'tab_lightbox_content_get_pro_style',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_section();
    }
}