<?php

namespace Inavii\Instagram\Includes\Legacy\Integration\Widgets\Controls\Feeds;

use Elementor\Controls_Manager;

use Inavii\Instagram\Config\Plugin;
use Inavii\Instagram\Includes\Legacy\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Legacy\Integration\Widgets\Controls\ControlsInterface;
use Inavii\Instagram\Includes\Legacy\PostTypes\Feed\FeedPostType;

class SectionFeed implements ControlsInterface{

    use VersionedFeaturesTrait;

    public static function addControls($widget): void
    {
        $feeds =  (new FeedPostType())->getAccounts();

        $widget->start_controls_section(
            'section_account',
            array(
                'label' => esc_html__('Instagram Feeds', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-feeds',
            )
        );

        if (empty($feeds)) {
            $widget->add_control(
                'no_account_connected',
                array(
                    'type' => Controls_Manager::RAW_HTML,
                    'raw' => sprintf(
                        esc_html__('You have not added your Instagram account yet, to do it go %1$s',
                            'inavii-social-feed-e'),
                        '<a target="_blank" href="' . self::settingsPageLink() . '">' . esc_html__('here',
                            'inavii-social-feed-e') . '</a>'
                    ),
                )
            );
        } else {
            $widget->add_control(
                'account_type',
                array(
                    'label' => esc_html__('Account Type', 'inavii-social-feed-e'),
                    'type' => Controls_Manager::SELECT,
                    'default' => 'personal',
                    'options' => array(
                        'personal' => esc_html__('Personal', 'inavii-social-feed-e'),
                        'business' => esc_html__('Business', 'inavii-social-feed-e'),
                    ),
                    'classes' => 'elementor-hidden',
                )
            );

            $widget->add_control(
                'feeds_personal',
                array(
                    'label' => esc_html__('Select Feed', 'inavii-social-feed-e'),
                    'type' => Controls_Manager::SELECT,
                    'options' => [],
                    'condition' => array(
                        'account_type' => 'personal',
                    ),
                    'classes' => 'elementor-hidden',
                )
            );

            $widget->add_control(
                'feeds_business',
                array(
                    'label' => esc_html__('Select Feed', 'inavii-social-feed-e'),
                    'type' => Controls_Manager::SELECT,
                    'options' => [],
                    'condition' => array(
                        'account_type' => 'business',
                    ),
                    'classes' => 'elementor-hidden',
                )
            );

            $widget->add_control(
                'feeds_layout',
                array(
                    'label' => esc_html__('Feed', 'inavii-social-feed-e'),
                    'type' => Controls_Manager::TEXT,
                    'classes' => 'feed-layout elementor-hidden',
                    'default' => 'grid',
                )
            );

            $widget->add_control(
                'feeds_id',
                array(
                    'label' => esc_html__('Feed ID', 'inavii-social-feed-e'),
                    'type' => Controls_Manager::TEXT,
                    'classes' => 'elementor-hidden',
                    'default' => 0,
                )
            );

            if (Plugin::isLegacyUi()) {
                $widget->add_control(
                    'predefined_section_library',
                    [
                        'label' => esc_html__( 'Resources', 'inavii-social-feed-e' ),
                        'type' => Controls_Manager::BUTTON,
                        'classes' => 'inavii-templates-popup__button-open-library',
                        'button_type' => 'success',
                        'text' => esc_html__( 'Select Feed', 'inavii-social-feed-e' ),
                        'event' => 'inavii/template/library/editor/button/click',
                    ]
                );
            } else {
                $globalSettingsUrl = add_query_arg(
                    [
                        'page'   => 'inavii-instagram-settings',
                        'screen' => 'global_settings',
                    ],
                    admin_url('admin.php')
                );

                $widget->add_control(
                    'legacy_widget_v3_notice',
                    [
                        'type' => Controls_Manager::ALERT,
                        'alert_type' => 'info',
                        'content' => wp_kses_post(
                            sprintf(
                                __(
                                    'This widget is legacy in version 3.0. Please use the new "Inavii Social Feed" widget, or switch to V2 in <a href="%s">Global Settings</a>.',
                                    'inavii-social-feed-e'
                                ),
                                esc_url($globalSettingsUrl)
                            )
                        ),
                    ]
                );
            }
        }

        $widget->end_controls_section();
    }
}
