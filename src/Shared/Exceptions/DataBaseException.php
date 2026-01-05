<?php

namespace Shared\Exceptions;

use RuntimeException;

class DataBaseException extends RuntimeException
{
    public function __construct(string $message = "Impossible de se connecter à la base de données veuillez contacter sae-manager@alwaysdata.net")
    {
        parent::__construct($message);
    }
}
