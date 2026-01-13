<?php

namespace Shared\Exceptions;

use Exception;

/**
 * Unauthorized SAE Unassignment Exception
 *
 * Exception thrown when a supervisor attempts to unassign a student from a SAE
 * (Situation d'Apprentissage et d'Évaluation) that was assigned by a different
 * supervisor.
 *
 * Business rule: Only the supervisor who assigned a student to a SAE can remove
 * that student from the SAE.  This prevents supervisors from interfering with
 * each other's assignments and maintains clear responsibility for student work.
 *
 * @package Shared\Exceptions
 */
class UnauthorizedSaeUnassignmentException extends Exception
{
    /**
     * The title of the SAE
     *
     * @var string
     */
    private string $saeTitre;

    /**
     * The name of the student
     *
     * @var string
     */
    private string $studentName;

    /**
     * The name of the supervisor who actually assigned the student
     *
     * @var string
     */
    private string $actualResponsable;

    /**
     * Constructor
     *
     * @param string $saeTitre The title of the SAE
     * @param string $studentName The name of the student
     * @param string $actualResponsable The name of the supervisor who assigned the student
     */
    public function __construct(string $saeTitre, string $studentName, string $actualResponsable = 'N/A')
    {
        $this->saeTitre = $saeTitre;
        $this->studentName = $studentName;
        $this->actualResponsable = $actualResponsable;

        parent::__construct(
            "Impossible de retirer l'étudiant « $studentName » de la SAE « $saeTitre » :  " .
            "cette SAE a été attribuée par $actualResponsable. " .
            "Seul le responsable ayant effectué l'attribution peut retirer des étudiants."
        );
    }

    /**
     * Gets the SAE title
     *
     * @return string The title of the SAE
     */
    public function getSaeTitre(): string
    {
        return $this->saeTitre;
    }

    /**
     * Gets the student name
     *
     * @return string The name of the student
     */
    public function getStudentName(): string
    {
        return $this->studentName;
    }

    /**
     * Gets the actual supervisor name
     *
     * @return string The name of the supervisor who assigned the student
     */
    public function getActualResponsable(): string
    {
        return $this->actualResponsable;
    }
}
