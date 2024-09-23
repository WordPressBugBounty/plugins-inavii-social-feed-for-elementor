<?php

namespace Inavii\Instagram\RestApi\EndPoints\Feeds;

use Inavii\Instagram\PostTypes\Feed\FeedPostType;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

class FeedUpdate
{
    private $api;
    private $feed;

    public function __construct()
    {
        $this->api = new ApiResponse();
        $this->feed = new FeedPostType();
    }

    public function update(WP_REST_Request $request): WP_REST_Response
    {
        $data = $request->get_params();

        $postId = $data['postId'];
        $settings = $data['settings'];

        if (empty($postId)) {
            return $this->api->response([], false, 'Post ID is required');
        }

        $this->feed->addOrUpdateSettings($postId, $settings);

        return $this->api->response(['message' => 'Feed updated']);
    }
}