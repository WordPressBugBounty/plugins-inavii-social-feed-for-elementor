<?php

namespace Inavii\Instagram\RestApi\EndPoints\Front;

use Inavii\Instagram\FeedsManager\GetAccountsBySource;
use Inavii\Instagram\Includes\Integration\Views\Views;
use Inavii\Instagram\PostTypes\Feed\FeedPostType;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

class LoadMore
{
    private $api;
    private $feed;
    private $feedId;

    public function __construct()
    {
        $this->api = new ApiResponse();
        $this->feed = new FeedPostType();
    }

    public function get(WP_REST_Request $request): WP_REST_Response
    {
        $widgetData = $request->get_param('settings');

        if (empty($widgetData)) {
            return $this->apiResponse(false, 'No widget data');
        }

        $feedId = $this->sanitizeInt($widgetData['feed_id'] ?? '');
        $postCount = $this->sanitizeInt($widgetData['posts_count'] ?? '');
        $feedOffset = $this->sanitizeInt($widgetData['feed_offset'] ?? 0);
        $this->feedId = $feedId;

        $posts = $this->feed->get($feedId, $postCount, $feedOffset);

        if (empty($posts->getPosts())) {
            return $this->noPostsResponse();
        }

        return $this->postsResponse($widgetData, $posts);
    }

    private function noPostsResponse(): WP_REST_Response
    {
        $html = Views::renderAjaxMessage('<span>No posts</span> to display');
        return $this->apiResponse(true, $html);
    }

    private function postsResponse(array $widgetData, $posts): WP_REST_Response
    {
        $html = Views::renderFeedItems(array_merge($widgetData, ['items' => $posts->getPosts()]));

        if ($widgetData['enable_photo_linking'] === 'popup') {
            $popupHtml =  Views::renderPopup(array_merge($widgetData, ['items' => $posts->getPosts()]));
        }else{
            $popupHtml = Views::renderLightbox(array_merge($widgetData, ['items' => $posts->getPosts()]));
        }

        return $this->apiResponse(true, [
            'html' => $html,
            'popupHtml' => $popupHtml,
            'total' => $posts->getTotal(),
        ]);
    }

    private function sanitizeInt($value): int
    {
        return (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    private function apiResponse(bool $success, $data = []): WP_REST_Response
    {
        return $this->api->response([
            'success' => $success,
            'data' => $data,
        ]);
    }
}