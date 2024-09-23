<?php

namespace Inavii\Instagram\FeedsManager;

use Inavii\Instagram\FeedsManager\Source\FeedSourceException;
use Inavii\Instagram\FeedsManager\Source\HashtagSource;
use Inavii\Instagram\FeedsManager\Source\InstagramSource;
use Inavii\Instagram\FeedsManager\Source\TaggedSource;
use Inavii\Instagram\PostTypes\Feed\FeedPostType;
use Inavii\Instagram\PostTypes\Media\MediaPostType;
use Inavii\Instagram\Utils\FiltersFeed;

class MyInstagramFeed extends AbstractInstagramFeed
{
    private $feedId;
    private $feedSettings = [];
    private $feedPostType;
    private $mediaPostType;
    private $numberOfPosts;
    private $offset;

    public function __construct(int $feedId, $numberOfPosts = null, $offset = 0)
    {
        $this->feedId = $feedId;
        $this->feedPostType = new FeedPostType();
        $this->mediaPostType = new MediaPostType();
        $this->numberOfPosts = $numberOfPosts;
        $this->offset = $offset;
        $this->feedSettings = $this->feedPostType->getSettings($feedId);
    }

    public function get(): array
    {
        try {
            $source = $this->getSources() ?? [];
            $media = $this->mediaPostType->getMedia($source, $this->feedSettings, $this->numberOfPosts, $this->offset);

            if (!$media) {
                return $this->getMediaOldVersionSupport();
            }

            $posts = $this->addAdvancedOptions($media, $this->feedSettings);

            return \iterator_to_array($this->preparePosts($posts));
        } catch (FeedSourceException $e) {
            return $this->getMediaOldVersionSupport();
        }
    }

    public function getForApi(): array
    {
        try {
            $source = $this->getSources() ?? [];
            return $this->mediaPostType->getMediaForApi($source, $this->feedSettings);
        } catch (FeedSourceException $e) {
            return [];
        }
    }

    /**
     * @deprecated 2.4.3.
     */
    private function getMediaOldVersionSupport(): array
    {
        $media = $this->callBackMedia();

        if (!$media) {
            return [];
        }

        $posts = (new FiltersFeed($media, $this->feedSettings))->filter();

        if ($this->numberOfPosts) {
            $posts = array_slice($posts, 0, $this->numberOfPosts);
        }

        return \iterator_to_array($this->preparePosts($posts));
    }

    private function callBackMedia(): array
    {
        $media = get_post_meta($this->feedId, FeedPostType::META_KEY_FEEDS, true);

        return is_array($media) ? $media : [];
    }

    /**
     * @throws FeedSourceException
     */
    private function getSources(): array
    {
        $results = [];

        $source = $this->feedSettings['source'] ?? [];

        foreach ($source as $key => $value) {
            if (!empty($value)) {
                if ($key === 'hashtags') {
                    $results = array_merge($results, HashtagSource::get($value));
                }
                if ($key === 'tagged') {
                    $results = array_merge($results, TaggedSource::get($value));
                }
                if ($key === 'accounts') {
                    $results = array_merge($results, InstagramSource::get($value));
                }
            }
        }

        return $results;
    }

    public function availablePosts(): int
    {
        try {
            return (int)$this->mediaPostType->getMediaCount($this->getSources(), $this->feedSettings);
        } catch (FeedSourceException $e) {
            return 0;
        }
    }

    public function dateLastPostUpdated(): string
    {
        try {
            return parent::getDateLastPostUpdated($this->getSources() ?? []);
        } catch (FeedSourceException $e) {
            return '';
        }
    }

    public function delete(int $accountID): void
    {
        $source = $this->feedSettings['source'] ?? [];

        foreach ($source as $key => $value) {
            if (!empty($value)) {
                $mediaSource = null;

                switch ($key) {
                    case 'tagged':
                    case 'accounts':
                        if (in_array($accountID, $value)) {
                            $mediaSource = ($key === 'tagged')
                                ? TaggedSource::create($accountID)
                                : InstagramSource::create($accountID);
                            try {
                                $media = $this->mediaPostType->getMediaBySource($mediaSource);
                                $this->deleteMedia($media);
                            } catch (FeedSourceException $e) {
                            }
                        }
                        break;

                    case 'hashtags':
                        if ($this->isLastBusinessAccount($accountID)) {
                            $mediaSource = HashtagSource::get($value);
                            try {
                                $media = $this->mediaPostType->getMediaBySource($mediaSource);
                                $this->deleteMedia($media);
                            } catch (FeedSourceException $e) {
                            }
                        }
                        break;
                }
            }
        }
        $this->shouldDeleteFeed($source, $accountID);
    }


    private function shouldDeleteFeed($source, $accountID): void
    {
        $ids = $this->extractIDs($source);

        if (in_array($accountID, $ids) && count($ids) === 1 && count($source['hashtags']) === 0) {
            $this->feedPostType->delete($this->feedId);
        }

        if (in_array($accountID, $ids) && count($ids) === 1 && count($source['hashtags']) > 0 && $this->isLastBusinessAccount($accountID)) {
            $this->feedPostType->delete($this->feedId);
        }

        if (count($ids) === 0 && $this->isLastBusinessAccount($accountID)) {
            $this->feedPostType->delete($this->feedId);
        }
    }

    private function extractIDs(array $source): array
    {
        $ids = array_merge($source['accounts'], $source['tagged']);

        return array_unique($ids);
    }
}
