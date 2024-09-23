<?php

namespace Inavii\Instagram\Services\Instagram;

class Response
{

    private $response;

    public function __construct($response)
    {
        $this->response = $response;
    }

    private function isRequestError(): bool
    {
        return is_wp_error($this->response);
    }

    public function read(): string
    {
        return wp_remote_retrieve_body($this->response);
    }

    public function responseCode(): int
    {
        return $this->isRequestError() ? 400 : wp_remote_retrieve_response_code($this->response);
    }

    public function responseMessage(): string
    {
        return wp_remote_retrieve_response_message($this->response) ?? '';
    }
}