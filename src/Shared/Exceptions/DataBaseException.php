<?php

namespace Shared\Exceptions;

use RuntimeException;

class DataBaseException extends RuntimeException
{
    public function __construct(string $message = "Unable to connect to the database please contact sae-manager@alwaysdata.net")
    {
        parent::__construct($message);
    }
}
