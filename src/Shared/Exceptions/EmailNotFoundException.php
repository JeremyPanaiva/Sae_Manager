<?php
namespace Shared\Exceptions;

class EmailNotFoundException extends \RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('L\'adresse email %s est introuvable.', $email));
    }
}
