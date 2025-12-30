<?php

namespace Views\User;

use Views\Base\BaseView;
use Views\Base\ErrorsView;

class ConnectionView extends BaseView
{
    // Champs du formulaire (non utilisés directement avec le nouveau template PHP mais gardés pour ref future si besoin)
    public const USERNAME_KEY = 'USERNAME_KEY';
    public const PASSWORD_KEY = 'PASSWORD_KEY';
    public const ERRORS_KEY = 'ERRORS_KEY';
    public const SUCCESS_MESSAGE_KEY = 'SUCCESS_MESSAGE_KEY';

    // Chemin du template PHP
    private const TEMPLATE_PATH = __DIR__ . '/connection.php';

    public function __construct(
        private array $errors = [],
        private string $successMessage = ''
    ) {
    }

    public function templatePath(): string
    {
        return self::TEMPLATE_PATH;
    }

    public function templateKeys(): array
    {
        return []; // Pas utilisé avec le template PHP
    }

    public function renderBody(): string
    {
        ob_start();
        $SUCCESS_MESSAGE_KEY = $this->successMessage ? '<div style="color: green; margin: 10px 0; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">' . $this->successMessage . '</div>' : '';
        $ERRORS_KEY = (new ErrorsView($this->errors))->renderBody();
        // Valeurs par défaut pour les champs si réaffichage (à implémenter si besoin, pour l'instant vide comme avant)
        $uname = '';

        include $this->templatePath();
        return ob_get_clean();
    }
}
