<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;

class InvalidStructureException extends IPPException
{
    public function __construct(string $message = "Invalid source structure")
    {
        parent::__construct($message, ReturnCode::INVALID_SOURCE_STRUCTURE_ERROR);
    }
}
