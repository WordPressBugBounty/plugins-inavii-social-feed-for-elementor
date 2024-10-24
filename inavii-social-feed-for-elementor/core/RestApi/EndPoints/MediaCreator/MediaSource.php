<?php

namespace Inavii\Instagram\RestApi\EndPoints\MediaCreator;

use Inavii\Instagram\MediaSourceCreators\HashtagPosts;
use Inavii\Instagram\MediaSourceCreators\InstagramPosts;
use Inavii\Instagram\MediaSourceCreators\TaggedPosts;
use Inavii\Instagram\Services\Instagram\InstagramOAuthException;
use Inavii\Instagram\Services\Instagram\MessageNotProvidedException;
use Inavii\Instagram\Utils\VersionChecker;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;
class MediaSource {
    private $api;

    private $taggedPosts;

    private $hashtagPosts;

    private $instagramPosts;

    public function __construct() {
        $this->api = new ApiResponse();
        $this->instagramPosts = new InstagramPosts();
    }

    public function create( WP_REST_Request $request ) : WP_REST_Response {
        $source = $request->get_param( 'source' );
        $media = [];
        try {
            $media = array_merge( $media, $this->processAccounts( $source['accounts'] ?? [] ) );
        } catch ( \RuntimeException|\InvalidArgumentException|InstagramOAuthException|MessageNotProvidedException $e ) {
            return $this->api->response( [], false, $e->getMessage() );
        }
        $flattenedMedia = array_merge( [], ...$media );
        $uniqueMedia = [];
        foreach ( $flattenedMedia as $mediaItem ) {
            if ( !isset( $uniqueMedia[$mediaItem['id']] ) ) {
                $uniqueMedia[$mediaItem['id']] = $mediaItem;
            }
        }
        return $this->api->response( [
            'media' => array_values( $uniqueMedia ),
        ] );
    }

    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    private function processAccounts( array $accountsIds ) : array {
        $media = [];
        foreach ( $accountsIds as $accountID ) {
            $media[] = $this->instagramPosts->handleUserRequest( $accountID );
        }
        return $media;
    }

}
