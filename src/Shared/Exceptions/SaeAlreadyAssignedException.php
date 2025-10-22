<?php
namespace Shared\Exceptions;

use Exception;

class SaeAlreadyAssignedException extends Exception
{
    private string $saeTitre;
    private string $responsable;

    public function __construct(string $saeTitre, string $responsable = 'N/A')
    {
        $this->saeTitre = $saeTitre;
        $this->responsable = $responsable;
        parent::__construct("Impossible d'attribuer la SAE « $saeTitre » : elle a déjà été attribuée par $responsable.");
    }

    public function getSae(): string
    {
        return $this->saeTitre;
    }

    public function getResponsable(): string
    {
        return $this->responsable;
    }
}
