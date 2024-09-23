<?php

namespace Inavii\Instagram\Services\Instagram\Token;


use Inavii\Instagram\Services\Instagram\InstagramOAuthException;
use Inavii\Instagram\Services\Instagram\Integration;
use Inavii\Instagram\Services\Instagram\MessageNotProvidedException;

class AccessToken
{

    private $integration;
    private $accessToken;

    public function __construct(string $accessToken)
    {
        $this->integration = new Integration();
        $this->accessToken = $accessToken;
    }

    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    public function refresh(): AccessTokenResponse
    {
        $response = $this->integration->get('https://graph.instagram.com/refresh_access_token', [
            'grant_type'   => 'ig_refresh_token',
            'access_token' => $this->accessToken,
        ]);

        return new AccessTokenResponse($response);
    }
}