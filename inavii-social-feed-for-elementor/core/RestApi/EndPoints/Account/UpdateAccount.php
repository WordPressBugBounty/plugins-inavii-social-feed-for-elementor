<?php

namespace Inavii\Instagram\RestApi\EndPoints\Account;

use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

class UpdateAccount {

    private $account;
    private $api;

    public function __construct( AccountPostType $account ) {
        $this->account = $account;
        $this->api     = new ApiResponse();
    }

    public function update(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_params();

        $this->account->updateAvatar($params['id'], $params['avatarOverwritten']);

        return $this->api->response( true );
    }
}