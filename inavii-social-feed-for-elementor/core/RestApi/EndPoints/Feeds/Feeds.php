<?php

namespace Inavii\Instagram\RestApi\EndPoints\Feeds;

use Inavii\Instagram\PostTypes\Feed\FeedPostType;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Response;

class Feeds {

	private $api;
	private $feed;

	public function __construct() {
		$this->api  = new ApiResponse();
		$this->feed = new FeedPostType();
	}

	public function get(): WP_REST_Response {
		return $this->api->response( $this->feed->posts() );
	}
}