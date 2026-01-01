<?php
namespace Shared\Exceptions;

class SamePasswordException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct("Le nouveau mot de passe doit être différent de l'actuel.");
    }
}
