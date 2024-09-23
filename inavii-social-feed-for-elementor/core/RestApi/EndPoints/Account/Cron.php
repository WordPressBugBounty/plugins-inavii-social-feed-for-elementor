<?php

namespace Inavii\Instagram\RestApi\EndPoints\Account;

use Inavii\Instagram\Cron\Schedule;
use Inavii\Instagram\Services\Instagram\InstagramOAuthException;
use Inavii\Instagram\Services\Instagram\MessageNotProvidedException;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

class Cron
{

    private $api;

    public function __construct()
    {
        $this->api = new ApiResponse();
    }

    public function run(WP_REST_Request $request): WP_REST_Response
    {
        $apiData = $request->get_params();
        $source = $apiData['source'] ?? [];

        $cron = new Schedule();

        try {
            if (empty($source)) {
                $cron->updateSingleAccount($apiData['accountId']);
            } else {
                $cron->updateManually($source);
            }
        } catch (InstagramOAuthException|MessageNotProvidedException $e) {
            return $this->api->response([], false, $e->getMessage());
        }

        return $this->api->response([]);
    }
}