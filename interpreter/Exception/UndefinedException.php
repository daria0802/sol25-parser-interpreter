<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class UndefinedException extends IPPException
{
    public function __construct(string $message = "Use of undefined identifier or method")
    {
        parent::__construct($message, ReturnCode::PARSE_UNDEF_ERROR);
    }
}