<?php


namespace Inavii\Instagram\Services\Instagram\Post;

use Inavii\Instagram\Services\Instagram\InstagramOAuthException;
use Inavii\Instagram\Services\Instagram\Integration;
use Inavii\Instagram\Services\Instagram\MessageNotProvidedException;
use Inavii\Instagram\Services\Instagram\Request;

class BusinessPosts implements Posts
{

    use RequestsPosts;

    private $userId;
    private $request;
    private $integration;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
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
            $this->request->buildUrl("https://graph.facebook.com/v16.0/$this->userId/media", [
                'access_token' => $accessToken,
                'limit' => $limit,
                'fields' => (new FieldsBuilder(true))->getAllFieldsAsString(),
            ])
        );
    }
}
