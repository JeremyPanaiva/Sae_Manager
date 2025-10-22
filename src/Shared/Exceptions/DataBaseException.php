<?php

namespace Shared\Exceptions;

use RuntimeException;

class DataBaseException extends RuntimeException
{
    public function __construct(string $message = "Unable to connect to database.")
    {
        parent::__construct($message);
    }
}
