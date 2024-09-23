<?php

namespace Inavii\Instagram\Services\Instagram\Token;

class AccessTokenResponse
{

    private $igResponse;

    public function __construct(array $igResponse)
    {
        $this->igResponse = $igResponse;
    }

    public function newAccessToken(): string
    {
        return $this->igResponse['access_token'];
    }

    public function tokenExpires(): int
    {
        return (int) $this->igResponse['expires_in'];
    }
}
