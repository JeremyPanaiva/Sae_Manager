<?php

namespace Shared\Exceptions;

/**
 * Same Password Exception
 *
 * Exception thrown when a user attempts to change their password to the same
 * password they currently have.  Used during password change operations to
 * ensure meaningful password updates.
 *
 * This validation helps enforce better security practices by encouraging users
 * to choose genuinely new passwords rather than reusing their current one.
 *
 * @package Shared\Exceptions
 */
class SamePasswordException extends \RuntimeException
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct("Le nouveau mot de passe doit être différent de l'actuel.");
    }
}
