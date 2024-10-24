<?php

namespace Inavii\Instagram\Cron;

use Inavii\Instagram\Cron\CustomOrder\UpdateCustomOrder;
use Inavii\Instagram\MediaSourceCreators\HashtagPosts;
use Inavii\Instagram\MediaSourceCreators\InstagramPosts;
use Inavii\Instagram\MediaSourceCreators\TaggedPosts;
use Inavii\Instagram\FeedsManager\Source\HashtagSource;
use Inavii\Instagram\FeedsManager\Source\InstagramSource;
use Inavii\Instagram\FeedsManager\Source\TaggedSource;
use Inavii\Instagram\Media\Media;
use Inavii\Instagram\PostTypes\Feed\FeedPostType;
use Inavii\Instagram\PostTypes\Media\MediaPostType;
use Inavii\Instagram\Services\Instagram\InstagramOAuthException;
use Inavii\Instagram\Services\Instagram\MessageNotProvidedException;
use Inavii\Instagram\Utils\VersionChecker;
class UpdateMedia {
    private $feedPostType;

    private $mediaPostType;

    private $taggedPosts;

    private $hashtagPosts;

    private $instagramPosts;

    private $instagramMediaComparer;

    public function __construct() {
        $this->feedPostType = new FeedPostType();
        $this->mediaPostType = new MediaPostType();
        $this->instagramMediaComparer = new InstagramMediaComparer();
        $this->instagramPosts = new InstagramPosts();
    }

    /**
     * @throws MessageNotProvidedException
     * @throws InstagramOAuthException
     */
    public function updateManually( $sources ) : void {
        try {
            if ( isset( $sources['accounts'] ) ) {
                $this->processAccounts( $sources['accounts'] );
            }
        } catch ( \RuntimeException|\InvalidArgumentException|InstagramOAuthException|MessageNotProvidedException $e ) {
            throw new $e($e->getMessage(), $e->getCode());
        }
    }

    public function update() : void {
        $sources = $this->extractSources();
        foreach ( $sources as $source ) {
            $this->fetch( $source );
        }
    }

    private function fetch( $source ) : void {
        try {
            $this->processAccounts( $source['accounts'] ?? [] );
        } catch ( \RuntimeException|\InvalidArgumentException|InstagramOAuthException|MessageNotProvidedException $e ) {
        }
    }

    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    private function processAccounts( array $accountsIds ) {
        foreach ( $accountsIds as $accountID ) {
            $media = $this->instagramPosts->handleCronRequest( $accountID );
            $this->deleteMedia( InstagramSource::create( $accountID ), $media );
        }
    }

    private function extractSources() : array {
        $feeds = $this->feedPostType->posts();
        $sources = [];
        foreach ( $feeds as $feed ) {
            if ( isset( $feed['settings']['source'] ) ) {
                if ( !empty( $feed['settings']['source']['accounts'] ) && $feed['settings']['dragAndDrop'] === true ) {
                    $this->updateCustomOrder( $feed['settings']['source'], $feed['settings'], $feed['id'] );
                } else {
                    $sources[] = $feed['settings']['source'];
                }
            }
        }
        return $sources;
    }

    private function deleteMedia( $source, $media ) {
        $mediaToDelete = $this->instagramMediaComparer->findElementsToDelete( $this->mediaPostType->getMediaBySource( $source ), $media );
        foreach ( $mediaToDelete as $medium ) {
            Media::delete( $medium['mediaId'] );
            foreach ( $medium['children'] ?? [] as $child ) {
                Media::delete( $child['id'] );
            }
            $this->mediaPostType->delete( $medium['id'] );
        }
    }

    private function updateCustomOrder( $source, $settings, $feedId ) {
    }

}
