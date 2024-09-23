<?php

namespace Inavii\Instagram\PostTypes\Media;

use Inavii\Instagram\Utils\FeedAdvancedFilters;
use Inavii\Instagram\Utils\FeedOrder;
use Inavii\Instagram\Wp\PostType;
use Inavii\Instagram\Wp\Query;
use WP_Post;

class MediaPostType extends PostType
{
    const MEDIA_ID = '_inavii_media_id';
    const MEDIA_TYPE = '_inavii_media_type';
    const MEDIA_URL = '_inavii_media_url';
    const URL = '_inavii_url';
    const PERMALINK = '_inavii_permalink';
    const USERNAME = '_inavii_username';
    const VIDEO_URL = '_inavii_video_url';
    const DATE = '_inavii_date';
    const COMMENTS_COUNT = '_inavii_comments_count';
    const LIKES_COUNT = '_inavii_likes_count';
    const CAPTION = '_inavii_caption';
    const CHILDREN = '_inavii_children';
    const MEDIA_PRODUCT_TYPE = '_inavii_media_product_type';
    const LAST_REQUESTED = '_inavii_last_requested';
    const SOURCE = '_inavii_source';

    public function slug(): string
    {
        return 'inavii_ig_media';
    }

    public function get(int $postID)
    {
        // TODO: Implement get() method.
    }

    public function insert(string $title, array $data)
    {
        return (new Query($this->slug()))->withPostTitle($title)
            ->withMetaInput(self::MEDIA_ID, $data['id'])
            ->withMetaInput(self::MEDIA_TYPE, $data['mediaType'])
            ->withMetaInput(self::MEDIA_URL, $data['mediaUrl'])
            ->withMetaInput(self::URL, $data['url'] ?? '')
            ->withMetaInput(self::PERMALINK, $data['permalink'])
            ->withMetaInput(self::USERNAME, $data['username'])
            ->withMetaInput(self::VIDEO_URL, $data['videoUrl'])
            ->withMetaInput(self::DATE, $data['date'])
            ->withMetaInput(self::COMMENTS_COUNT, $data['commentsCount'])
            ->withMetaInput(self::LIKES_COUNT, $data['likeCount'])
            ->withMetaInput(self::CAPTION, wp_encode_emoji($data['caption'] ?? ''))
            ->withMetaInput(self::CHILDREN, $data['children'])
            ->withMetaInput(self::MEDIA_PRODUCT_TYPE, $data['media_product_type'] ?? '')
            ->withMetaInput(self::LAST_REQUESTED, date('c'))
            ->withMetaInput(self::SOURCE, $data['source'])
            ->saveMedia(self::SOURCE);
    }

    public function getMediaCount($source, $settings): int
    {
        return (new Query($this->slug()))->withMetaQuery(MediaPostType::SOURCE, $source)
            ->withMetaQueryRelation(MediaPostType::MEDIA_TYPE, $settings['typesOfPosts'] ?? [])
            ->withMetaQueryRelation(MediaPostType::CAPTION, $settings['hashTagFilter']['include'] ?? [], 'LIKE')
            ->withMetaQueryRelation(MediaPostType::CAPTION, $settings['hashTagFilter']['exclude'] ?? [], 'NOT LIKE', 'AND')
            ->withMetaQueryRelation(MediaPostType::CAPTION, $settings['captionFilter']['include'] ?? [], 'LIKE')
            ->withMetaQueryRelation(MediaPostType::CAPTION, $settings['captionFilter']['exclude'] ?? [], 'NOT LIKE', 'AND')
            ->withSpecificPosts(FeedAdvancedFilters::customOrderPostIds($settings))
            ->withExcludePosts(FeedAdvancedFilters::moderatePosts($settings))
            ->countPosts();
    }

    public function getMostRecentPostDate($source)
    {
        $order = FeedOrder::create('lastRequested');

        $query = (new Query($this->slug()))->withMetaQuery(MediaPostType::SOURCE, $source)
            ->numberOfPosts(1)
            ->orderByMetaValue($order->key, $order->valueType, $order->order, $order->isRandom)
            ->posts();

        if (!$query) {
            return null;
        }

        return $query[0]->{self::LAST_REQUESTED};
    }

    public function getMediaBySource($source): array
    {
        $order = FeedOrder::create('mostRecentFirst');

        $query = (new Query($this->slug()))->withMetaQuery(MediaPostType::SOURCE, $source)
            ->numberOfPosts()
            ->orderByMetaValue($order->key, $order->valueType, $order->order, $order->isRandom)
            ->posts();

        return $this->processQuery($query);
    }

    public function getMedia($source, array $settings, int $postsCount = 30, $offset = 0): array
    {
        $order = FeedOrder::create($settings['postOrder'] ?? 'mostRecentFirst');

        $query = (new Query($this->slug()))->withMetaQuery(MediaPostType::SOURCE, $source)
            ->withMetaQueryRelation(MediaPostType::MEDIA_TYPE, $settings['typesOfPosts'] ?? [])
            ->withMetaQueryRelation(MediaPostType::CAPTION, $settings['hashTagFilter']['include'] ?? [], 'LIKE')
            ->withMetaQueryRelation(MediaPostType::CAPTION, $settings['hashTagFilter']['exclude'] ?? [], 'NOT LIKE', 'AND')
            ->withMetaQueryRelation(MediaPostType::CAPTION, $settings['captionFilter']['include'] ?? [], 'LIKE')
            ->withMetaQueryRelation(MediaPostType::CAPTION, $settings['captionFilter']['exclude'] ?? [], 'NOT LIKE', 'AND')
            ->orderByMetaValue($order->key, $order->valueType, $order->order, $order->isRandom)
            ->withSpecificPosts(FeedAdvancedFilters::customOrderPostIds($settings))
            ->withExcludePosts(FeedAdvancedFilters::moderatePosts($settings))
            ->numberOfPosts($postsCount)
            ->withOffset($offset)
            ->posts();

        return $this->processQuery($query);
    }

    public function getMediaForApi($source, array $settings, int $postsCount = -1): array
    {
        $order = FeedOrder::create($settings['postOrder']);

        $query = (new Query($this->slug()))->withMetaQuery(MediaPostType::SOURCE, $source)
            ->orderByMetaValue($order->key, $order->valueType, $order->order, $order->isRandom)
            ->numberOfPosts($postsCount)
            ->withSpecificPosts(FeedAdvancedFilters::customOrderPostIdsForApi($settings))
            ->posts();

        return $this->processQuery($query);
    }

    private function processQuery($query): array
    {
        if (!$query) {
            return [];
        }

        return array_map(function ($post) {
            return $this->buildMediaAlbum($post);
        }, $query);
    }

    private function buildMediaAlbum(WP_Post $post): array
    {
        return [
            'id' => $post->ID,
            'mediaId' => $post->{self::MEDIA_ID},
            'mediaType' => $post->{self::MEDIA_TYPE},
            'mediaUrl' => $post->{self::MEDIA_URL},
            'url' => $post->{self::URL},
            'permalink' => $post->{self::PERMALINK},
            'username' => $post->{self::USERNAME},
            'videoUrl' => $post->{self::VIDEO_URL},
            'date' => $post->{self::DATE},
            'commentsCount' => $post->{self::COMMENTS_COUNT},
            'likeCount' => $post->{self::LIKES_COUNT},
            'caption' => html_entity_decode($post->{self::CAPTION}, ENT_QUOTES, 'UTF-8'),
            'children' => $post->{self::CHILDREN},
            'mediaProductType' => $post->{self::MEDIA_PRODUCT_TYPE},
            'lastRequested' => $post->{self::LAST_REQUESTED},
            'source' => $post->{self::SOURCE},
        ];
    }

    public function posts(): array
    {
        return [];
    }

    protected function args(): array
    {
        return array_merge(
            parent::args(),
            [
                'labels' => [
                    'menu_name' => __('Inavii Ig Media', 'text_domain'),
                ],
            ]
        );
    }
}