<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class MissingMainException extends IPPException
{
    public function __construct(string $message = "Missing class Main or method run")
    {
        parent::__construct($message, ReturnCode::PARSE_MAIN_ERROR);
    }
}
