<?php

namespace Shared\Exceptions;

/**
 * Email Already Exists Exception
 *
 * Exception thrown when attempting to register or update a user with an email
 * address that is already registered in the system.  Used to enforce unique
 * email constraints and provide user-friendly error messages during registration
 * and profile updates.
 *
 * @package Shared\Exceptions
 */
class EmailAlreadyExistsException extends \RuntimeException
{
    /**
     * Constructor
     *
     * @param string $email The email address that already exists
     */
    public function __construct(string $email)
    {
        parent::__construct(sprintf('L\'adresse mail %s existe déjà. ', $email));
    }
}