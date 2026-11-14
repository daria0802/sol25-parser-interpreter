<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class ArityException extends IPPException
{
    public function __construct(string $message = "Selector arity mismatch with block")
    {
        parent::__construct($message, ReturnCode::PARSE_ARITY_ERROR);
    }
}
