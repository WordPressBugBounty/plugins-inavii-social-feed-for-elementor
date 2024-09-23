<?php

namespace Inavii\Instagram\FeedsManager;

use Inavii\Instagram\Media\Media;
use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\PostTypes\Media\MediaPostType;
use Inavii\Instagram\Utils\VersionChecker;
abstract class AbstractInstagramFeed {
    public abstract function get();

    public abstract function getForApi();

    public abstract function availablePosts();

    public abstract function dateLastPostUpdated();

    public abstract function delete( int $accountID );

    protected function preparePosts( $posts ) : \Generator {
        foreach ( $posts as $post ) {
            (yield new Feed(
                $post['id'],
                $post['mediaType'],
                $post['mediaUrl'],
                $post['permalink'],
                $post['username'] ?? '',
                $post['name'] ?? '',
                $post['children'] ?? [],
                $post['videoUrl'] ?? '',
                $post['date'] ?? '',
                $post['commentsCount'] ?? 0,
                $post['likeCount'] ?? 0,
                $post['caption'] ?? '',
                $post['promotion'] ?? [],
                $post['url'] ?? ''
            ));
        }
    }

    protected function deleteMedia( array $media ) {
        $mediaPostType = new MediaPostType();
        foreach ( $media as $medium ) {
            Media::delete( $medium['mediaId'] ?? $medium['id'] );
            foreach ( $medium['children'] ?? [] as $child ) {
                Media::delete( $child['id'] );
            }
            $mediaPostType->delete( $medium['id'] );
        }
    }

    protected function isLastBusinessAccount( $accountID ) : bool {
        $accountPostType = new AccountPostType();
        $accounts = array_filter( $accountPostType->posts(), function ( $account ) use($accountID) {
            return $account['accountType'] === AccountPostType::BUSINESS && $account['wpAccountID'] !== $accountID && $account['issues']['reconnectRequired'] === false;
        } );
        return count( $accounts ) < 1;
    }

    protected function getDateLastPostUpdated( $source ) : string {
        return (string) ( new MediaPostType() )->getMostRecentPostDate( $source ) ?? '';
    }

    protected function addAdvancedOptions( $media, $feedSettings ) : array {
        return $media;
    }

}
