<?php
namespace Shared\Exceptions;

use Exception;

class UnauthorizedSaeUnassignmentException extends Exception
{
    private string $saeTitre;
    private string $studentName;
    private string $actualResponsable;

    public function __construct(string $saeTitre, string $studentName, string $actualResponsable = 'N/A')
    {
        $this->saeTitre = $saeTitre;
        $this->studentName = $studentName;
        $this->actualResponsable = $actualResponsable;

        parent::__construct(
            "Impossible de retirer l'étudiant « $studentName » de la SAE « $saeTitre » : " .
            "cette SAE a été attribuée par $actualResponsable. " .
            "Seul le responsable ayant effectué l'attribution peut retirer des étudiants."
        );
    }

    public function getSaeTitre(): string
    {
        return $this->saeTitre;
    }

    public function getStudentName(): string
    {
        return $this->studentName;
    }

    public function getActualResponsable(): string
    {
        return $this->actualResponsable;
    }
}