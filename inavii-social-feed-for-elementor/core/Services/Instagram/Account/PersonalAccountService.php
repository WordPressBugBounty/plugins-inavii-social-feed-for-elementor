<?php

namespace Inavii\Instagram\Services\Instagram\Account;

use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\Services\Instagram\InstagramOAuthException;
use Inavii\Instagram\Services\Instagram\Integration;
use Inavii\Instagram\Services\Instagram\MessageNotProvidedException;

class PersonalAccountService implements Account
{
    private $accessToken;
    private $integration;
    private $tokenExpires;

    public function __construct(string $accessToken, string $tokenExpires)
    {
        $this->accessToken = $accessToken;
        $this->integration = new Integration();
        $this->tokenExpires = $tokenExpires;
    }

    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    public function get(): InstagramAccount
    {
        $response = $this->integration->get('https://graph.instagram.com/v22.0/me', [
            'fields' => 'id,username,media_count,account_type,profile_picture_url,biography',
            'access_token' => $this->accessToken,
        ]);

        return new InstagramAccount(array_merge($response, [
            'accessToken' => $this->accessToken,
            'tokenExpires' => $this->tokenExpires,
            'account_type' => AccountPostType::BUSINESS_BASIC
        ]));
    }
}
