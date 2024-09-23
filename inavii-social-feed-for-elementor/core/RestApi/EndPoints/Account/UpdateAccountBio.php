<?php

namespace Inavii\Instagram\RestApi\EndPoints\Account;

use Inavii\Instagram\PostTypes\Account\AccountPostType;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

class UpdateAccountBio
{
    private $account;
    private $api;

    public function __construct(AccountPostType $account)
    {
        $this->account = $account;
        $this->api = new ApiResponse();
    }

    public function update(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_params();

        $biographyOverwritten = isset($params['biographyOverwritten']) ? sanitize_text_field($params['biographyOverwritten']) : '';

        $this->account->updateBiography__premium_only($params['id'], $biographyOverwritten);

        return $this->api->response(true);
    }
}