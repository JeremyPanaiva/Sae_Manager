<?php

namespace Views\User;

use Views\Base\BaseView;
use Views\Base\ErrorsView;
use Views\Base\ErrorView;

class InscriptionView extends BaseView
{

    // Champs du formulaire (gardés pour référence)
    public const NOM_KEY = 'NOM_KEY';
    public const PRENOM_KEY = 'PRENOM_KEY';
    public const MAIL_KEY = 'MAIL_KEY';
    public const PASSWORD_KEY = 'PASSWORD_KEY';
    public const ERRORS_KEY = 'ERRORS_KEY';

    // Chemin du template
    private const TEMPLATE_PATH = __DIR__ . '/inscription.php';

    function __construct(
        private array $errors = [],
    ) {

    }
    public function templatePath(): string
    {
        return self::TEMPLATE_PATH;
    }

    public function templateKeys(): array
    {
        return []; // Pas utilisé
    }

    public function renderBody(): string
    {
        ob_start();
        $ERRORS_KEY = (new ErrorsView($this->errors))->renderBody();
        // Variables pour repeupler le formulaire en cas d'erreur (à implémenter si les données sont dispos dans $this->data)
        $nom = $this->data['nom'] ?? '';
        $prenom = $this->data['prenom'] ?? '';
        $mail = $this->data['mail'] ?? '';

        include $this->templatePath();
        return ob_get_clean();
    }
}
