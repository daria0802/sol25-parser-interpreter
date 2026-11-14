<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class TypeErrorException extends IPPException
{
    public function __construct(string $message = "Invalid operand types")
    {
        parent::__construct($message, ReturnCode::INTERPRET_TYPE_ERROR);
    }
}
