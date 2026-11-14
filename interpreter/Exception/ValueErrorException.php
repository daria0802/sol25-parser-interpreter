<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class ValueErrorException extends IPPException
{
    public function __construct(string $message = "Invalid operand value")
    {
        parent::__construct($message, ReturnCode::INTERPRET_VALUE_ERROR);
    }
}
