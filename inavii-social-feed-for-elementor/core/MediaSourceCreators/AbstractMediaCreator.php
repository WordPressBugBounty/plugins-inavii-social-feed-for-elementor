<?php

namespace Inavii\Instagram\MediaSourceCreators;

use Inavii\Instagram\Utils\TimeChecker;

abstract class AbstractMediaCreator
{
    private $mediaPostType;

    public function __construct($mediaPostType)
    {
        $this->mediaPostType = $mediaPostType;
    }

    abstract public function handleUserRequest($dataSource): array;

    abstract public function handleCronRequest($dataSource): array;

    protected function createFeeds($media): array
    {
        $results = [];

        foreach ($media as $key => $medium) {
            $newId = $this->mediaPostType->insert($medium['id'], $medium);

            $results[$key] = $medium;
            $results[$key]['id'] = $newId;
        }

        return $results;
    }

    protected function findMedia(string $source, $time = 3600): array
    {
        $mostRecentPostDate = $this->mediaPostType->getMostRecentPostDate($source);

        try {
            if (TimeChecker::postShouldBeRequest($mostRecentPostDate, $time)) {
                return [];
            }
            return $this->mediaPostType->getMediaBySource($source);
        } catch (\Exception $e) {
            return [];
        }
    }
}