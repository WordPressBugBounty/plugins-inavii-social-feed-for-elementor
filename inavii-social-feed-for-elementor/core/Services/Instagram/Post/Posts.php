<?php

namespace Inavii\Instagram\Services\Instagram\Post;

interface Posts
{
    public function posts(string $accessToken, int $limit);
}