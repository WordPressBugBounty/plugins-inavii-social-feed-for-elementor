<?php

namespace Inavii\Instagram\RestApi\EndPoints\Feeds;

use Inavii\Instagram\PostTypes\Feed\FeedPostType;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

class FeedCreate
{
    private $api;
    private $feed;

    public function __construct()
    {
        $this->api = new ApiResponse();
        $this->feed = new FeedPostType();
    }

    public function create(WP_REST_Request $request): WP_REST_Response
    {
        $data = $request->get_params();

        $title = $data['title'];
        $settings = $data['settings'];
        $feedType = $data['feedType'];

        if (empty($title)) {
            return $this->api->response([], false, 'Feed name is required');
        }

        if (empty($settings)) {
            return $this->api->response([], false, 'Settings are required');
        }

        $newPostsID = $this->feed->create($title);
        $this->feed->addOrUpdateSettings($newPostsID, $settings);
        $this->feed->addFeedType($newPostsID, $feedType);

        return $this->api->response([
            'feedId' => $newPostsID,
        ]);
    }
}