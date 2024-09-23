<?php

namespace Inavii\Instagram\RestApi\EndPoints\Account;

use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\Services\Instagram\Account\BusinessAccountService;
use Inavii\Instagram\Services\Instagram\InstagramOAuthException;
use Inavii\Instagram\Services\Instagram\MessageNotProvidedException;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

class CreateBusinessAccount
{
    private $api;

    public function __construct()
    {
        $this->api = new ApiResponse();
    }

    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    public function create(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_param('data');

        $account = (new BusinessAccountService($params['accessToken'], $params['tokenExpires'] ?? ''))->get($params['userID']);

        return $this->api->response(
            (new CreateAccount(AccountPostType::BUSINESS))->create($account)
        );
    }
}