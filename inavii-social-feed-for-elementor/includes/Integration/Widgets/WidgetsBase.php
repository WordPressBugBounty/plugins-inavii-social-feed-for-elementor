<?php

namespace Inavii\Instagram\Includes\Integration\Widgets;

use Elementor\Widget_Base;
use Elementor\Icons_Manager;
use Inavii\Instagram\Includes\Integration\VersionedFeaturesTrait;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\Caption\Content\SectionCaption;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\Caption\Style\SectionCaptionStyle;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\Feeds\SectionFeed;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\FeedSettings\Style\SectionFeedSettingsStyle;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\FooterBox\Content\SectionFooterBox;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\FooterBox\Style\SectionFooterBoxStyle;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\FeedOptions\SectionFeedOptions;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\HeaderBox\Content\SectionHeaderBox;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\HeaderBox\Style\SectionHeaderBoxStyle;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\LayoutSettings\Style\SectionLayoutSettingsStyle;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\LightBox\Content\SectionLightBox;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\LightBox\Style\SectionLightBoxStyle;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\LikeComments\Content\SectionLikeComments;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\LikeComments\Style\SectionLikeCommentsStyle;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\NumberOfPosts\SectionNumberOfPosts;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\PopUp\Content\SectionPopUp;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\PopUp\Style\SectionPopUpStyle;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\Slider\Content\SectionSlider;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\Slider\Style\SectionSliderStyle;
use Inavii\Instagram\Includes\Integration\Widgets\Controls\Video\Content\SectionVideo;
use Inavii\Instagram\PostTypes\Feed\FeedPostType;

if (!defined('ABSPATH')) {
    exit;
}

abstract class WidgetsBase extends Widget_Base
{
    use VersionedFeaturesTrait;

    public $allFeeds = [];
    public $gridCondition = [];
    public $galleryCondition = [];
    public $rowCondition = [];
    public $waveCondition = [];
    public $waveGridCondition = [];
    public $highlightCondition = [];
    public $highlightSuperCondition = [];
    public $highlightFocusCondition = [];
    public $masonryHorizontalCondition = [];
    public $masonryVerticalCondition = [];
    public $sliderCondition = [];
    public $cardsCondition = [];
    public $shapeMatrixCondition = [];
    public $contentGridCondition = [];
    public $creativeGridCondition = [];

    public const GRID = 'grid';
    public const GALLERY = 'gallery';
    public const ROW = 'row';
    public const WAVE = 'wave';
    public const WAVE_GRID = 'wave-grid';
    public const HIGHLIGHT = 'highlight';
    public const HIGHLIGHT_SUPER = 'highlight-super';
    public const HIGHLIGHT_FOCUS = 'highlight-focus';
    public const MASONRY_HORIZONTAL = 'masonry-horizontal';
    public const MASONRY_VERTICAL = 'masonry-vertical';
    public const SLIDER = 'slider';
    public const CARDS = 'cards';
    public const SHAPE_MATRIX = 'shape-matrix';
    public const CONTENT_GRID = 'content-grid';
    public const CREATIVE_GRID= 'creative-grid';

    public function __construct($data = [], $args = null)
    {
        parent::__construct($data, $args);
        $this->allFeeds = (new FeedPostType())->getAccounts();
        $this->gridCondition = $this->get_feed_by_condition(self::GRID);
        $this->galleryCondition = $this->get_feed_by_condition(self::GALLERY);
        $this->rowCondition = $this->get_feed_by_condition(self::ROW);
        $this->waveCondition = $this->get_feed_by_condition(self::WAVE);
        $this->waveGridCondition = $this->get_feed_by_condition(self::WAVE_GRID);
        $this->highlightCondition = $this->get_feed_by_condition(self::HIGHLIGHT);
        $this->highlightSuperCondition = $this->get_feed_by_condition(self::HIGHLIGHT_SUPER);
        $this->highlightFocusCondition = $this->get_feed_by_condition(self::HIGHLIGHT_FOCUS);
        $this->masonryHorizontalCondition = $this->get_feed_by_condition(self::MASONRY_HORIZONTAL);
        $this->masonryVerticalCondition = $this->get_feed_by_condition(self::MASONRY_VERTICAL);
        $this->sliderCondition = $this->get_feed_by_condition(self::SLIDER);
        $this->cardsCondition = $this->get_feed_by_condition(self::CARDS);
        $this->shapeMatrixCondition = $this->get_feed_by_condition(self::SHAPE_MATRIX);
        $this->contentGridCondition = $this->get_feed_by_condition(self::CONTENT_GRID);
        $this->creativeGridCondition = $this->get_feed_by_condition(self::CREATIVE_GRID);
    }

    protected $settings = [];

    public function get_categories(): array
    {
        return array('inavii-social-feed-e');
    }

    public function get_keywords(): array
    {
        return ['instagram', 'social', 'feed', 'social feed'];
    }

    protected function register_controls()
    {
        $this->register_content_controls();
        $this->register_style_controls();
    }

    protected function register_content_controls()
    {
        SectionFeed::addControls($this);
        SectionNumberOfPosts::addControls($this);
        SectionFeedOptions::addControls($this);
        SectionLikeComments::addControls($this);
        SectionCaption::addControls($this);
        SectionVideo::addControls($this);
        SectionHeaderBox::addControls($this);
        SectionFooterBox::addControls($this);
        SectionLightBox::addControls($this);
        SectionPopUp::addControls($this);
        SectionSlider::addControls($this);
    }

    protected function register_style_controls()
    {
        SectionFeedSettingsStyle::addControls($this);
        SectionLayoutSettingsStyle::addControls($this);
        SectionLikeCommentsStyle::addControls($this);
        SectionCaptionStyle::addControls($this);
        SectionHeaderBoxStyle::addControls($this);
        SectionFooterBoxStyle::addControls($this);
        SectionLightBoxStyle::addControls($this);
        SectionPopUpStyle::addControls($this);
        SectionSliderStyle::addControls($this);
    }

    public function controlInfo(): string
    {
        return __('Control elements will be available after activating the option in the "Content" section.', 'inavii-social-feed-e');
    }

    protected function icon($icon): array
    {
        if (isset($icon['value']['id'])) {
            return [
                'inline_svg' => $this->get_inline_svg($icon['value']['id']),
            ];
        }

        ob_start();
        Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']);
        $rendered_icon = ob_get_clean();

        return [
            'icon_class' => $rendered_icon,
        ];
    }

    protected function get_inline_svg($attachment_id): string
    {
        $svg = get_post_meta($attachment_id, '_elementor_inline_svg', true);

        if (!empty($svg)) {
            return $svg;
        }

        $attachment_file = get_attached_file($attachment_id);

        if (!$attachment_file) {
            return '';
        }

        $svg = file_get_contents($attachment_file);

        if (!empty($svg)) {
            update_post_meta($attachment_id, '_elementor_inline_svg', $svg);
        }

        return $svg;
    }

    protected function feeds_contains($haystack, $needle): bool
    {
        $layout = explode(':', $haystack);

        if (count($layout) === 1) {
            return $layout[0] === $needle;
        }

        return isset($layout[1]) ? $layout[1] === $needle : false;
    }

    protected function get_feed_by_condition($condition): array
    {
        $filteredFeeds = array_filter(array_keys($this->allFeeds), function ($value) use ($condition) {
            return $this->feeds_contains($value, $condition);
        });

        if (!in_array($condition, $filteredFeeds)) {
            $filteredFeeds[] = $condition;
        }

        return $filteredFeeds;
    }

}
