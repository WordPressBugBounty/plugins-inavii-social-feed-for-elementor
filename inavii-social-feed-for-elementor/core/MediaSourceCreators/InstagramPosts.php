<?php

namespace Inavii\Instagram\MediaSourceCreators;

use Inavii\Instagram\FeedsManager\Source\InstagramSource;
use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\PostTypes\Media\MediaPostType;
use Inavii\Instagram\RestApi\EndPoints\Media\GenerateThumbnailsProcessor;
use Inavii\Instagram\RestApi\EndPoints\Media\ImporterMediaProcessor;
use Inavii\Instagram\RestApi\EndPoints\Media\ImportMedia;
use Inavii\Instagram\Services\Instagram\InstagramOAuthException;
use Inavii\Instagram\Services\Instagram\MediaRequest;
use Inavii\Instagram\Services\Instagram\MessageNotProvidedException;
use Inavii\Instagram\Utils\TransformRemotenIstagramData;

class InstagramPosts extends AbstractMediaCreator
{
    private $accountPostType;
    private $mediaPostType;
    private $import;

    public function __construct()
    {
        $this->mediaPostType = new MediaPostType();
        $this->import = new ImportMedia();
        $this->accountPostType = new AccountPostType();
        parent::__construct($this->mediaPostType);
    }

    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    public function handleUserRequest($dataSource): array
    {
        $source = InstagramSource::create($dataSource);

        $media = $this->findMedia($source);

        if ($media) {
            return $media;
        }

        $media = $this->fetchMedia($dataSource, $source);

        $this->import->startImport($media);

        return $this->createFeeds($media);
    }

    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    public function handleCronRequest($dataSource): array
    {
        $source = InstagramSource::create($dataSource);

        $media = $this->findMedia($source, 60);

        if ($media) {
            return $media;
        }

        $media = $this->fetchMedia($dataSource, $source);

        (new ImporterMediaProcessor())->task($media);
        (new GenerateThumbnailsProcessor())->task($media);

        return $this->createFeeds($media);
    }

    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    private function fetchMedia($dataSource, $source): array
    {
        $account = $this->accountPostType->get($dataSource);
        $newMedia = (new MediaRequest($account))->request();

        return TransformRemotenIstagramData::addSource($newMedia, 'source', $source);
    }
}