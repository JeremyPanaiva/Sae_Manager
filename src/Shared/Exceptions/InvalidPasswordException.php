<?php

namespace Shared\Exceptions;

/**
 * Invalid Password Exception
 *
 * Exception thrown when a password fails authentication validation.  Used during
 * login attempts when the provided password does not match the stored hash.
 *
 * Note: For security reasons, this exception should be handled carefully in
 * combination with EmailNotFoundException to avoid revealing whether a user
 * account exists in the system.   Both exceptions are typically wrapped in a
 * generic ArrayException with a message like "Email ou mot de passe incorrect"
 * to prevent user enumeration attacks.
 *
 * @package Shared\Exceptions
 */
class InvalidPasswordException extends \RuntimeException
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct("Le mot de passe est incorrect.");
    }
}
