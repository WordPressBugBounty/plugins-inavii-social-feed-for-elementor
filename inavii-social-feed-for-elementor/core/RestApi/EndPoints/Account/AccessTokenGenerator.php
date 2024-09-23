<?php

namespace Inavii\Instagram\RestApi\EndPoints\Account;

use Inavii\Instagram\Services\Instagram\Account\BusinessAccountService;
use Inavii\Instagram\Services\Instagram\Account\PersonalAccountService;
use Inavii\Instagram\Services\Instagram\MessageNotProvidedException;
use Inavii\Instagram\Wp\ApiResponse;
use WP_REST_Request;
use WP_REST_Response;

class AccessTokenGenerator
{
    private $api;

    public function __construct()
    {
        $this->api = new ApiResponse();
    }

    public function get(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_params();
        $accessToken = htmlspecialchars($params['accessToken'] ?? '', ENT_QUOTES, 'UTF-8');
        $accessToken = preg_replace('/\s+/', '', $accessToken);
        $tokenExpires = htmlspecialchars($params['tokenExpires'] ?? '', ENT_QUOTES, 'UTF-8');

        if (empty($accessToken)) {
            return $this->createErrorResponse("The access token is required.", 400);
        }

        try {
            if ($this->isBusinessToken($accessToken)) {
                return $this->processBusinessAccount($params, $accessToken, $tokenExpires);
            } else {
                return $this->processPersonalAccount($accessToken, $tokenExpires);
            }
            //TODO
        } catch (MessageNotProvidedException|\Exception $e) {
            $message = $this->isBusinessToken($accessToken) ? 'The access token or user ID is invalid.' : 'The access token is invalid.';
            return $this->createErrorResponse($message, 400);
        }
    }

    private function processBusinessAccount($params, $accessToken, $tokenExpires): WP_REST_Response
    {
        $userId = htmlspecialchars($params['userId'] ?? null, ENT_QUOTES, 'UTF-8');

        if (empty($userId)) {
            return $this->createErrorResponse("The user ID is required for business accounts.", 400);
        }

        $accountService = (new BusinessAccountService($accessToken, $tokenExpires))->get($params['userId']);
        return $this->createAccountResponse('business', $accountService);
    }

    private function processPersonalAccount($accessToken, $tokenExpires): WP_REST_Response
    {
        $accountService = (new PersonalAccountService($accessToken, $tokenExpires))->get();
        return $this->createAccountResponse('personal', $accountService);
    }

    private function createAccountResponse($accountType, $accountService): WP_REST_Response
    {
        return $this->api->response((new CreateAccount($accountType))->create($accountService));
    }

    private function isBusinessToken($accessToken): bool
    {
        return stripos($accessToken, 'EA') === 0 && strlen($accessToken) > 145;
    }

    private function createErrorResponse($message, $statusCode): WP_REST_Response
    {
        return new WP_REST_Response(['error' => $message], $statusCode);
    }
}
