<?php

namespace Views\User;

use Views\Base\BaseView;
use Views\Base\ErrorsView;

class ConnectionView extends BaseView
{
    // Champs du formulaire
    public const USERNAME_KEY = 'USERNAME_KEY';
    public const PASSWORD_KEY = 'PASSWORD_KEY';
    public const ERRORS_KEY   = 'ERRORS_KEY';

    // Chemin du template HTML
    private const TEMPLATE_HTML = __DIR__ . '/connection.html';

    public function __construct(
        private array $errors = []
    ) {
    }

    public function templatePath(): string
    {
        return self::TEMPLATE_HTML;
    }

    public function templateKeys(): array
    {
        return [
            self::USERNAME_KEY => 'uname',
            self::PASSWORD_KEY => 'psw',
            self::ERRORS_KEY   => (new ErrorsView($this->errors))->renderBody(),
        ];
    }
}
