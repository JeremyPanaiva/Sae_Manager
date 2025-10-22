<?php
namespace Shared\Exceptions;

class SaeAttribueException extends \RuntimeException
{
    public function __construct(string $saeTitre)
    {
        parent::__construct("Impossible de supprimer la SAE « $saeTitre » : elle a déjà été attribuée à un ou plusieurs étudiant(s).");
    }
}
