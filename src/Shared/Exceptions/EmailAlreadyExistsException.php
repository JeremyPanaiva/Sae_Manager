<?php

namespace Shared\Exceptions;

class EmailAlreadyExistsException extends \RuntimeException
{
public function __construct(string $email)
{
    parent::__construct(sprintf('Email %s already exists', $email));
}
}