<?php

namespace Shared\Exceptions;

use Exception;

/**
 * Student Already Assigned Exception
 *
 * Exception thrown when attempting to assign a student to a SAE (Situation
 * d'Apprentissage et d'Évaluation) they are already assigned to.
 *
 * Business rule: A student cannot be assigned to the same SAE multiple times
 * to prevent duplicate assignments and ensure data integrity.  This exception
 * enforces that constraint during the student assignment process.
 *
 * @package Shared\Exceptions
 */
class StudentAlreadyAssignedException extends Exception
{
    /**
     * The title of the SAE
     *
     * @var string
     */
    private string $sae;

    /**
     * The name of the student already assigned
     *
     * @var string
     */
    private string $student;

    /**
     * Constructor
     *
     * @param string $sae The title of the SAE
     * @param string $student The name of the student already assigned
     */
    public function __construct(string $sae, string $student)
    {
        $this->sae = $sae;
        $this->student = $student;
        parent::__construct("L'étudiant « $student » est déjà assigné à la SAE « $sae ».");
    }

    /**
     * Gets the SAE title
     *
     * @return string The title of the SAE
     */
    public function getSae(): string
    {
        return $this->sae;
    }

    /**
     * Gets the student name
     *
     * @return string The name of the student
     */
    public function getStudent(): string
    {
        return $this->student;
    }
}