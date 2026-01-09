<?php

namespace Shared\Exceptions;

/**
 * SAE Attribuée Exception
 *
 * Exception thrown when attempting to delete a SAE (Situation d'Apprentissage et
 * d'Évaluation) that has already been assigned to one or more students.
 *
 * Business rule: A SAE cannot be deleted once it has been assigned to students
 * to preserve academic records and prevent data loss.  This exception enforces
 * data integrity by preventing deletion of SAE with active student assignments.
 *
 * @package Shared\Exceptions
 */
class SaeAttribueException extends \RuntimeException
{
    /**
     * Constructor
     *
     * @param string $saeTitre The title of the SAE that cannot be deleted
     */
    public function __construct(string $saeTitre)
    {
        parent::__construct("Impossible de supprimer la SAE « $saeTitre » : elle a déjà été attribuée à un ou plusieurs étudiant(s).");
    }
}
