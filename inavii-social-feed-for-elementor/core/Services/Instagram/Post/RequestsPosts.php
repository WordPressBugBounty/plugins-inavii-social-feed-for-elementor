<?php

namespace Inavii\Instagram\Services\Instagram\Post;

use Inavii\Instagram\Services\Instagram\InstagramOAuthException;
use Inavii\Instagram\Services\Instagram\MessageNotProvidedException;
use Inavii\Instagram\Wp\AppGlobalSettings;

trait RequestsPosts
{
    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    public function posts(string $accessToken, int $limit = 50): array
    {
        return $this->requestMedia($accessToken, $limit);
    }

    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    private function getMedia($url): array
    {
        $settings = new AppGlobalSettings();
        $postsCount =  $settings->getNumberOfPostsImported();
        $media = [];
        $count = 0;

        while ($url && $count < $postsCount) {

            $response = $this->integration->get($url, [], true);

            $media[] = $response["data"];
            $count += count($response["data"]);

            $url = $response["paging"]["next"] ?? false;
        }

        $result = array_merge([], ...$media);

        return array_slice($result, 0, $postsCount);
    }
}
