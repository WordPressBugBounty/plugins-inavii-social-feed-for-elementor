<?php

namespace Inavii\Instagram\Services\Instagram;

use Throwable;

class MessageNotProvidedException extends \Exception
{
    private $type;
    
    public function __construct($message = "", $code = 0, $type = '', Throwable $previous = null)
    {
        $this->type = $type;
        parent::__construct($message, $code, $previous);
    }

    public function getType()
    {
        return $this->type;
    }
}