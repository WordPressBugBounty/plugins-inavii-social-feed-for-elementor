<?php

namespace Inavii\Instagram\Wp;

use WP_REST_Response;

class ApiResponse extends WP_REST_Response
{
    public function response($data, $success = true, $errors = []): WP_REST_Response
    {
        $this->set_data([
            'value'   => $data,
            'success' => $success,
            'errors' => $errors
        ]);

        return $this;
    }
}