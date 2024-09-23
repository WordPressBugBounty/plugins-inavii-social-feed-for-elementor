<?php

namespace Inavii\Instagram\Includes\Integration\Widgets\Controls\FeedOptions;

use Elementor\Controls_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\ControlsInterface;

class SectionFeedOptions implements ControlsInterface
{
    use VersionedFeaturesTrait;

    public static function addControls($widget): void
    {
        $widget->start_controls_section(
            'section_general',
            array(
                'label' => esc_html__('Feed Options', 'inavii-social-feed-e'),
                'classes' => self::titleIconClass() . ' inavii-pro__title-icon-feed-options',
            )
        );

        $widget->add_control(
            'layout',
            array(
                'label' => __('Card Layout', 'inavii-social-feed-e'),
                'label_block' => true,
                'type' => Controls_Manager::CHOOSE,
                'default' => self::isFree() ? 'none' : 'overlay',
                'options' => array(
                    'overlay' => array(
                        'title' => __('Overlay', 'inavii-social-feed-e'),
                        'icon' => 'eicon-parallax',
                    ),
                    'flip-box' => array(
                        'title' => __('Flip Box', 'inavii-social-feed-e'),
                        'icon' => 'eicon-flip-box',
                    ),
                ),
                'render_type' => self::renderTypePro(),
                'toggle' => true,
                'classes' => self::customChoicesClass() . ' ' . self::customChoicesLabelProClass(),
                'conditions' => array(
                    'terms' => array(
                        array(
                            'name' => 'feeds_layout',
                            'operator' => '!in',
                            'value' => array_merge(array_values($widget->cardsCondition), array_values($widget->contentGridCondition)),
                        ),
                    ),
                ),
            )
        );

        $widget->add_control(
            'image_size_feeds',
            array(
                'label' => __('Image Size', 'inavii-social-feed-e'),
                'label_block' => true,
                'type' => Controls_Manager::CHOOSE,
                'default' => 'medium',
                'options' => array(
                    'medium' => array(
                        'title' => __('Medium', 'inavii-social-feed-e'),
                        'icon' => 'eicon-image',
                    ),
                    'large' => array(
                        'title' => __('Large', 'inavii-social-feed-e'),
                        'icon' => 'eicon-image',
                    ),
                ),
                'toggle' => true,
                'classes' => self::customChoicesClass(),
            )
        );

        $widget->add_control(
            'post_linking',
            array(
                'label' => __('Open posts in', 'inavii-social-feed-e'),
                'label_block' => true,
                'type' => Controls_Manager::CHOOSE,
                'default' => self::imageClickActions(),
                'options' => array(
                    'none' => array(
                        'title' => __('Do not open', 'inavii-social-feed-e'),
                        'icon' => 'eicon-editor-unlink',
                    ),
                    'linking_post' => array(
                        'title' => __('Instagram', 'inavii-social-feed-e'),
                        'icon' => 'eicon-instagram-post',
                    ),
                    'popup' => array(
                        'title' => __('PopUp', 'inavii-social-feed-e'),
                        'icon' => 'eicon-lightbox-expand',
                    ),
                    'lightbox' => array(
                        'title' => __('LightBox', 'inavii-social-feed-e'),
                        'icon' => 'eicon-sidebar',
                    ),
                ),
                'toggle' => true,
                'classes' => self::customChoicesClass() . ' ' . self::customChoicesTwoRowClass(),
            )
        );

        $widget->add_control(
            'post_link_target',
            array(
                'label' => esc_html__('Open in a New Window ', 'inavii-social-feed-e'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'inavii-social-feed-e'),
                'label_off' => esc_html__('No', 'inavii-social-feed-e'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => array(
                    'post_linking' => 'linking_post',
                ),
            )
        );

        $widget->add_control(
            'section_layout_note',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => self::premiumInfo(),
                'classes' => self::buttonClassGetPro(),
            ]
        );

        $widget->end_controls_section();
    }
}