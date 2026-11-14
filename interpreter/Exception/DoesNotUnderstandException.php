<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class DoesNotUnderstandException extends IPPException
{
    public function __construct(string $message = "Instance does not understand the message")
    {
        parent::__construct($message, ReturnCode::INTERPRET_DNU_ERROR);
    }
}
