<?php

namespace Inavii\Instagram\Utils;

class Json
{
    public static function decode(string $json, $fullResponse = false)
    {
        $result = \json_decode($json, true);

        if (\json_last_error() === JSON_ERROR_NONE) {

            if ($fullResponse) {
                return $result;
            }

            if (isset($result['data']) && is_array($result['data'])) {
                return $result['data'];
            }

            return $result;
        }
    }
}
