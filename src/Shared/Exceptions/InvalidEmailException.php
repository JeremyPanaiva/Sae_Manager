<?php

namespace Shared\Exceptions;

use Exception;

class InvalidEmailException extends Exception
{
    private string $email;

    public function __construct(string $email)
    {
        $this->email = $email;
        parent::__construct("L'adresse email \"$email\" n'est pas valide.");
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
