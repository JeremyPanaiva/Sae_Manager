<?php
namespace Shared\Exceptions;

class EmailNotFoundException extends \RuntimeException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('Email %s not found', $email));
    }
}
