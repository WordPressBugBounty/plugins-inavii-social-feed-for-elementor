<?php

namespace Inavii\Instagram\PostTypes\Feed;

use Inavii\Instagram\FeedsManager\GetAccountsBySource;
use Inavii\Instagram\FeedsManager\MyInstagramFeed;
use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\Wp\PostType;
use Inavii\Instagram\Wp\Query;

class FeedPostType extends PostType
{
    public const META_KEY_FEEDS = 'inavii_social_feed_feeds';
    public const META_KEY_FEEDS_SETTINGS = 'inavii_social_feed_settings';
    public const META_KEY_FEEDS_TYPE = 'inavii_social_feed_type';
    public const META_KEY_ACCOUNT_RELATED = 'inavii_social_feed_account_related';

    public const INSTAGRAM_POSTS = 'instagram_posts';

    public function slug(): string
    {
        return 'inavii_feed';
    }

    /**
     * @deprecated 2.4.3 Use create() instead of this method.
     */
    public function insert(string $title, array $data): int
    {
        return (new Query($this->slug()))->withPostTitle($title)
            ->withMetaInput(self::META_KEY_FEEDS, $data)
            ->save();
    }

    public function create(string $title): int
    {
        return (new Query($this->slug()))->withPostTitle($title)->save();
    }

    public function addFeedType(int $postID, string $feedType): void
    {
        $this->updateMeta($postID, self::META_KEY_FEEDS_TYPE, $feedType);
    }

    public function getFeedType(int $postID): string
    {
        return $this->getMeta($postID, self::META_KEY_FEEDS_TYPE) ?: self::INSTAGRAM_POSTS;
    }

    public function addOrUpdateSettings($postID, $data): void
    {
        $this->updateMeta($postID, self::META_KEY_FEEDS_SETTINGS, $data);
    }

    public function getSettings($postID): array
    {
        return (array)$this->getMeta($postID, self::META_KEY_FEEDS_SETTINGS);
    }

    public function countAvailablePosts(int $postID, int $desiredCount): int
    {
        $feedsManager = new MyInstagramFeed($postID);

        $availableCount = $feedsManager->availablePosts();

        return min($desiredCount, $availableCount);
    }

    public function getForApi(int $postID): array
    {
        $settings = $this->getSettings($postID);
        $feedType = $this->getFeedType($postID);

        $feedsManager = new MyInstagramFeed($postID);

        return [
            'media' => array_values($feedsManager->getForApi()),
            'settings' => $settings,
            'feedType' => $feedType,
            // Deprecated from 2.4.3
            'migrateAccountId' => (new AccountPostType())->getAccountRelatedWithFeed($postID)->wpAccountID()
        ];
    }

    public function get(int $postID, int $numberOfPosts = 30, $offset = 0): array
    {
        return (new MyInstagramFeed($postID, $numberOfPosts, $offset))->get();
    }

    public function deleteFeedsRelatedToAccount($accountID): array
    {
        $allFeeds = $this->posts();
        $results = [];

        foreach ($allFeeds as $feed) {

            $feedsManager = new MyInstagramFeed($feed['id']);

            $results[] = $feedsManager->delete($accountID);
        }

        return $results;
    }

    public function posts(): array
    {
        return array_map(function ($post) {
            return $this->serializeData($post);
        }, (new Query($this->slug()))->numberOfPosts()->order('ASC')->posts());
    }

    public function post($postID): array
    {
        return $this->serializeData((new Query($this->slug()))->post($postID));
    }

    private function serializeData($post): array
    {
        $account = (new AccountPostType())->getAccountRelatedWithFeed($post->ID);
        $settings = $this->getSettings($post->ID);

        if ($account->wpAccountID() === 0) {
            $account = (new GetAccountsBySource($settings))->get();
        }

        $feedType = $this->getFeedType($post->ID);

        $feedsManager = new MyInstagramFeed($post->ID);

        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'accountID' => $account->wpAccountID(),
            'settings' => $settings,
            'lastUpdatedPost' => $feedsManager->dateLastPostUpdated(),
            'feedType' => $feedType
        ];
    }

    public function getAccounts(): array
    {
        $posts = (new Query($this->slug()))
            ->numberOfPosts()
            ->order('DESC')
            ->posts();

        if (empty($posts)) {
            return [];
        }

        $formattedPosts = [];

        foreach ($posts as $post) {
            $layout = $this->getSettings($post->ID)['layout'] ?? 'grid';
            $key = $post->ID . ':' . $layout;
            $value = "{$post->post_title} ({$layout})";
            $formattedPosts[$key] = $value;
        }

        return $formattedPosts;
    }

    /**
     * @deprecated 2.4.3.
     */
    public function getRelatedFeedsIds($accountID): array
    {
        return (new Query($this->slug()))->withMetaQuery(self::META_KEY_ACCOUNT_RELATED, $accountID)
            ->withFields('ids')
            ->numberOfPosts()
            ->posts();
    }

    /**
     * @deprecated 2.4.3.
     */
    public function updateMedia(int $postID, array $data): void
    {
        $this->updateMeta($postID, self::META_KEY_FEEDS, $data);
    }

    protected function args(): array
    {
        return array_merge(
            parent::args(),
            [
                'labels' => [
                    'menu_name' => __('Inavii Feed', 'text_domain'),
                ],
            ]
        );
    }
}


