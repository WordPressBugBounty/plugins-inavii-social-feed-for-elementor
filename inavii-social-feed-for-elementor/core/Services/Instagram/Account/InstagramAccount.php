<?php

namespace Inavii\Instagram\Services\Instagram\Account;

use Inavii\Instagram\PostTypes\Account\AccountPostType;

class InstagramAccount
{
    private $instagramData;

    public function __construct(array $instagramData)
    {
        $this->instagramData = $instagramData;
    }

    public function id(): string
    {
        return $this->instagramData['id'];
    }

    public function name(): string
    {
        return $this->instagramData['name'] ?? '';
    }

    public function userName(): string
    {
        return $this->instagramData['username'] ?? '';
    }

    public function accountType(): string
    {
        return $this->instagramData['account_type'] ?? AccountPostType::BUSINESS;
    }

    public function avatar(): string
    {
        return $this->instagramData['profile_picture_url'] ?? '';
    }

    public function mediaCount(): int
    {
        return $this->instagramData['media_count'] ?? 0;
    }

    public function accessToken(): string
    {
        return $this->instagramData['accessToken'];
    }

    public function tokenExpires(): string
    {
        return $this->instagramData['tokenExpires'] ?? '';
    }

    public function biography(): string
    {
        return $this->instagramData['biography'] ?? '';
    }
}