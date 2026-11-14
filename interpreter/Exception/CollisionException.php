<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class CollisionException extends IPPException
{
    public function __construct(string $message = "Variable name collides with parameter")
    {
        parent::__construct($message, ReturnCode::PARSE_COLLISION_ERROR);
    }
}
