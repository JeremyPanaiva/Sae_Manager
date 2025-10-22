<?php

namespace Shared\Exceptions;

class ArrayException extends \RuntimeException
{
    private array $validationException;

    /**
     * @param ValidationException[] $validationException
     */
    public function __construct(array $validationException)
    {
        $this->validationException = $validationException;
        parent::__construct("probleme de validation");


    }

    /**
     * @return ValidationException[]
     */
    public function getExceptions(): array
    {
        return $this->validationException;
    }
}