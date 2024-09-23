<?php

namespace Inavii\Instagram\Services\Instagram\Post;

use Inavii\Instagram\Services\Instagram\InstagramOAuthException;
use Inavii\Instagram\Services\Instagram\Integration;
use Inavii\Instagram\Services\Instagram\MessageNotProvidedException;
use Inavii\Instagram\Services\Instagram\Request;

class PrivatePosts implements Posts
{
    use RequestsPosts;

    private $request;
    private $integration;

    public function __construct()
    {
        $this->request = new Request();
        $this->integration = new Integration();
    }

    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    private function requestMedia(string $accessToken, int $limit): array
    {
        return $this->getMedia(
            $this->request->buildUrl('https://graph.instagram.com/v16.0/me/media', [
                'access_token' => $accessToken,
                'limit' => $limit,
                'fields' => (new FieldsBuilder(false))->getAllFieldsAsString(),
            ])
        );
    }
}