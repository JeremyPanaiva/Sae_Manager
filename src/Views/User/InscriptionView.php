<?php

namespace Views\User;

use Views\Base\BaseView;
use Views\Base\ErrorsView;
use Views\Base\ErrorView;

class InscriptionView extends BaseView {

    // Champs du formulaire
    public const NOM_KEY = 'NOM_KEY';
    public const PRENOM_KEY = 'PRENOM_KEY';
    public const MAIL_KEY = 'MAIL_KEY';
    public const PASSWORD_KEY = 'PASSWORD_KEY';

    public const ERRORS_KEY = 'ERRORS_KEY';

    // Chemin du template
    private const TEMPLATE_HTML = __DIR__ . '/inscription.html';

    function __construct(
        private array $errors = [],
    ) {

    }
    public function templatePath(): string {
        return self::TEMPLATE_HTML;
    }

    public function templateKeys(): array {
        return [
            self::NOM_KEY => 'nom',
            self::PRENOM_KEY => 'prenom',
            self::MAIL_KEY => 'mail',
            self::PASSWORD_KEY => 'mdp',
            self::ERRORS_KEY => (new ErrorsView($this->errors))->renderBody(),
        ];
    }
}
