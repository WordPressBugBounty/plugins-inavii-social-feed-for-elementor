<?php

namespace Inavii\Instagram\Services\Instagram;

class Request
{

    public function buildUrl(string $url, $params): string
    {
        return add_query_arg($params, $url);
    }

    public function get(string $url, array $params = []): Response
    {
        $prepare_url = $this->buildUrl($url, $params);

        $response = \wp_remote_get($prepare_url, ['timeout' => 180]);

        return new Response($response);
    }
}
