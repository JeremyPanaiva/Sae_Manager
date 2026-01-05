<?php
namespace Shared\Exceptions;

class InvalidPasswordException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct("Le mot de passe est incorrect.");
    }
}
