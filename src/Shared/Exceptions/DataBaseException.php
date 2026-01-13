<?php

namespace Shared\Exceptions;

use RuntimeException;

/**
 * Database Exception
 *
 * Custom exception for database-related errors.  Provides a user-friendly default
 * error message with contact information while allowing specific error messages
 * to be passed when needed.
 *
 * This exception is thrown when:
 * - Database connection fails
 * - SQL queries fail
 * - Database operations encounter errors
 * - Connection health checks fail
 *
 * @package Shared\Exceptions
 */
class DataBaseException extends RuntimeException
{
    /**
     * Constructor
     *
     * @param string $message Error message (defaults to user-friendly connection error message)
     */
    public function __construct(string $message = "Impossible de se connecter à la base de données veuillez contacter 
    sae-manager@alwaysdata.net")
    {
        parent::__construct($message);
    }
}
