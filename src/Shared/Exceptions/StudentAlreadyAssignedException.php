<?php
namespace Shared\Exceptions;

use Exception;class StudentAlreadyAssignedException extends \Exception
{
    private string $sae;
    private string $student;

    public function __construct(string $sae, string $student)
    {
        $this->sae = $sae;
        $this->student = $student;
        parent::__construct("L'étudiant « $student » est déjà assigné à la SAE « $sae ».");
    }

    public function getSae(): string
    {
        return $this->sae;
    }

    public function getStudent(): string
    {
        return $this->student;
    }
}
