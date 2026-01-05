<?php

namespace Shared\Exceptions;

class EmailAlreadyExistsException extends \RuntimeException
{
public function __construct(string $email)
{
    parent::__construct(sprintf('L\'adresse mail %s existe déjà.', $email));
}
}