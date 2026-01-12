<?php

namespace Shared\Exceptions;

use Exception;

/**
 * SAE Already Assigned Exception
 *
 * Exception thrown when attempting to assign a SAE (Situation d'Apprentissage et
 * d'Évaluation) that has already been assigned to students by another supervisor.
 *
 * Business rule: A SAE can only be assigned by one supervisor (responsable) to
 * prevent conflicts in student assignments and grading responsibilities.  This
 * exception enforces that constraint.
 *
 * @package Shared\Exceptions
 */
class SaeAlreadyAssignedException extends Exception
{
    /**
     * The title of the SAE that is already assigned
     *
     * @var string
     */
    private string $saeTitre;

    /**
     * The name of the supervisor who already assigned the SAE
     *
     * @var string
     */
    private string $responsable;

    /**
     * Constructor
     *
     * @param string $saeTitre The title of the SAE
     * @param string $responsable The name of the supervisor who already assigned it
     */
    public function __construct(string $saeTitre, string $responsable = 'N/A')
    {
        $this->saeTitre = $saeTitre;
        $this->responsable = $responsable;
        parent::__construct("Impossible d'attribuer la SAE « $saeTitre » : 
        elle a déjà été attribuée par $responsable.");
    }

    /**
     * Gets the SAE title
     *
     * @return string The title of the SAE that is already assigned
     */
    public function getSae(): string
    {
        return $this->saeTitre;
    }

    /**
     * Gets the supervisor name
     *
     * @return string The name of the supervisor who already assigned the SAE
     */
    public function getResponsable(): string
    {
        return $this->responsable;
    }
}
