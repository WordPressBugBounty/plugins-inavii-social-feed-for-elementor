<?php

namespace Inavii\Instagram\RestApi\EndPoints\Account;

use Inavii\Instagram\Cron\Schedule;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Response;

class ReconnectAccount
{
    private $api;
    private $cron;

    public function __construct()
    {
        $this->api = new ApiResponse();
        $this->cron = new Schedule();
    }

    public function reconnect(): WP_REST_Response
    {
        $this->cron->update();

        return $this->api->response('true');
    }
}