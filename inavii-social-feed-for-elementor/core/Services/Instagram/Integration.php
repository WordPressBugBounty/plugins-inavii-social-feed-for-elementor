<?php

namespace Inavii\Instagram\Services\Instagram;

use Inavii\Instagram\Utils\Json;

class Integration
{
    private $request;

    public function __construct()
    {
        $this->request = new Request();
    }


    /**
     * @throws InstagramOAuthException
     * @throws MessageNotProvidedException
     */
    public function get(string $url, $params, $fullResponse = false): array
    {
        $response = $this->request->get($url, $params);

        $code = $response->responseCode();

        if (200 === $code) {
            return Json::decode($response->read(), $fullResponse);
        }

        $body = Json::decode($response->read());

        if (isset($body['error']['message'])) {
            throw new InstagramOAuthException($body['error']['message'], $body['error']['code'], $body['error']['type']);
        }

        if ($code === 400) {
            $type = $response->responseMessage() === 'Bad Request' ? 'OAuthException' : $response->responseMessage();
            throw new InstagramOAuthException($response->responseMessage(), $code, $type);
        }

        throw new MessageNotProvidedException("Message not provided");
    }
}
