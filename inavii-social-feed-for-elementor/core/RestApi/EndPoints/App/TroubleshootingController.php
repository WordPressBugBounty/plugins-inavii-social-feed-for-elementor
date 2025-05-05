<?php

namespace Inavii\Instagram\RestApi\EndPoints\App;

use Inavii\Instagram\Admin\Troubleshooting;
use Inavii\Instagram\Cron\CronLogger;
use Inavii\Instagram\Wp\ApiResponse;

class TroubleshootingController {

	private $api;
	private $appGlobalSettings;

	public function __construct()
	{
		$this->api = new ApiResponse();
	}

	public function checkCronStatus(): \WP_REST_Response
	{
		return $this->api->response( Troubleshooting::checkCronStatus());
	}

	public function fix(): \WP_REST_Response
	{
		return $this->api->response( Troubleshooting::fixCronIssues());
	}

	public function cronRun():  \WP_REST_Response {
		wp_remote_post(rest_url('inavii/v1/troubleshooting/cron/run/async'), [
			'timeout'  => 0.01,
			'blocking' => false,
			'headers'  => ['Content-Type' => 'application/json'],
			'body'     => json_encode(['trigger' => true]),
		]);

		return $this->api->response([]);
	}

	public function cronRunAsync() {
		CronLogger::clear();
		Troubleshooting::runCronNow();
	}

	public function cronLogger():  \WP_REST_Response
	{
		return $this->api->response( Troubleshooting::cronLogger());
	}
}