<?php

namespace Inavii\Instagram\RestApi\EndPoints\Account;

use Inavii\Instagram\Wp\ApiResponse;
use Inavii\Instagram\Wp\PostType;
use WP_REST_Response;

class Accounts {

	private $account;
	private $api;

	public function __construct( PostType $account ) {
		$this->account = $account;
		$this->api     = new ApiResponse();
	}

	/**
	 *  Returns all accounts from metadata
	 *
	 * @return WP_REST_Response
	 */
	public function get(): WP_REST_Response {
		return $this->api->response( $this->account->posts() );
	}
}