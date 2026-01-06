<?php

namespace Shared\Exceptions;

/**
 * Array Exception
 *
 * A container exception that holds multiple validation exceptions.   Used to collect
 * and transport multiple validation errors that occur during a single operation,
 * allowing all errors to be displayed to the user at once rather than one at a time.
 *
 * This is particularly useful for form validation where multiple fields may have
 * validation errors simultaneously.
 *
 * @package Shared\Exceptions
 */
class ArrayException extends \RuntimeException
{
    /**
     * Array of validation exceptions
     *
     * @var array
     */
    private array $validationException;

    /**
     * Constructor
     *
     * @param ValidationException[] $validationException Array of validation exceptions to collect
     */
    public function __construct(array $validationException)
    {
        $this->validationException = $validationException;
        parent::__construct("Probleme de validation");
    }

    /**
     * Gets all collected validation exceptions
     *
     * @return ValidationException[] Array of validation exceptions
     */
    public function getExceptions(): array
    {
        return $this->validationException;
    }
}