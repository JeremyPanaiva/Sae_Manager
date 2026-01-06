<?php

namespace Shared\Exceptions;

/**
 * Email Not Found Exception
 *
 * Exception thrown when attempting to authenticate or perform operations with
 * an email address that does not exist in the system.  Used during login attempts
 * and other operations that require an existing user account.
 *
 * Note: For security reasons, this exception should be handled carefully to avoid
 * revealing whether an email exists in the system (to prevent email enumeration attacks).
 * The login controller wraps this in a generic ArrayException for display to users.
 *
 * @package Shared\Exceptions
 */
class EmailNotFoundException extends \RuntimeException
{
    /**
     * Constructor
     *
     * @param string $email The email address that was not found
     */
    public function __construct(string $email)
    {
        parent::__construct(sprintf('L\'adresse email %s est introuvable. ', $email));
    }
}