<?php

namespace Inavii\Instagram\RestApi\EndPoints\Feeds;

use Inavii\Instagram\PostTypes\Feed\FeedPostType;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

class Feed
{
    private $api;
    private $feed;

    public function __construct()
    {
        $this->api  = new ApiResponse();
        $this->feed = new FeedPostType();
    }

    public function get(WP_REST_Request $request): WP_REST_Response
    {
        $feedID = $request->get_param('id');

        return $this->api->response($this->feed->getForApi($feedID));
    }
}