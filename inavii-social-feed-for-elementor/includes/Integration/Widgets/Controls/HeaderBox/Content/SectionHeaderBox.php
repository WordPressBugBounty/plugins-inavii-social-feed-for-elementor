<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\HeaderBox\Content;


use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;

class SectionHeaderBox implements ControlsInterface{

    use VersionedFeaturesTrait;

    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_content_header_box',
            array(
                'label' => esc_html__('Header Box', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-header',
            )
        );

        $widget->add_control(
            'avatar_header_switch',
            array(
                'label' => esc_html__('Show Avatar', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => 'no',
            )
        );

        if (version_compare(ELEMENTOR_VERSION, '3.19.0', '>')) {
            $widget->add_control(
                'avatar_header_content_info',
                [
                    'type' => Controls_Manager::ALERT,
                    'alert_type' => 'info',
                    'content' => esc_html__( 'You can change your avatar in', 'inavii-social-feed-e' ) . ' <a href="./admin.php?page=inavii-instagram-settings#/accounts">' . esc_html__( 'Inavii Social feed » Accounts » Info', 'textdomain' ) . '</a>',
                    'condition' => [
                        'avatar_header_switch' => 'yes',
                    ],
                ]
            );
        }

        $widget->add_control(
            'bio_header_switch',
            array(
                'label' => esc_html__('Show Bio', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'render_type' => self::renderTypePro(),
                'default' => 'no',
                'classes' => self::titleLabelProClass() . ' ' . self::optionProClass(),
            )
        );

        $widget->add_control(
            'user_name_header_switch',
            array(
                'label' => esc_html__('Show Username', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => 'no',
            )
        );

        $widget->add_control(
            'user_name_header_choice',
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
                    'user_name_header_switch' => 'yes',
                ),
            )
        );

        $widget->add_control(
            'enable_header_follow_button',
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
            'header_follow_button_text',
            array(
                'label' => __('Instagram Button Text', 'inavii-social-feed-e'),
                'type' => Controls_Manager::TEXT,
                'default' => 'Follow on Instagram',
                'condition' => [
                    'enable_header_follow_button' => 'yes',
                ],
            )
        );

        $widget->add_control(
            'section_header_note',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_section();
    }
}