<?php

namespace Shared\Exceptions;

use Exception;

/**
 * Invalid Email Exception
 *
 * Exception thrown when an email address fails validation.  Used during user
 * registration, login, and profile updates to ensure email addresses are in
 * a valid format before attempting database operations.
 *
 * Stores the invalid email address for debugging and error reporting purposes.
 *
 * @package Shared\Exceptions
 */
class InvalidEmailException extends Exception
{
    /**
     * The invalid email address
     *
     * @var string
     */
    private string $email;

    /**
     * Constructor
     *
     * @param string $email The invalid email address
     */
    public function __construct(string $email)
    {
        $this->email = $email;
        parent::__construct("L'adresse email \"$email\" n'est pas valide.");
    }

    /**
     * Gets the invalid email address
     *
     * @return string The email address that failed validation
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}