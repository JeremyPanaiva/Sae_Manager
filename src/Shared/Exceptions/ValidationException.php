<?php

namespace Shared\Exceptions;

/**
 * Validation Exception
 *
 * Generic exception for validation errors.  Used throughout the application to
 * indicate that user input or data failed validation rules.  Can be collected
 * into an ArrayException to handle multiple validation errors simultaneously.
 *
 * Common use cases:
 * - Form field validation (email format, password strength, required fields)
 * - Business rule validation (date ranges, numeric limits)
 * - Data integrity checks
 *
 * @package Shared\Exceptions
 */
class ValidationException extends \RuntimeException
{
    /**
     * Constructor
     *
     * @param string $message Descriptive validation error message
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
