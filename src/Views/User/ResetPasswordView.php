<?php

namespace Views\User;
use Views\Base\BaseView;

class ResetPasswordView extends BaseView {

    public const TOKEN = "token";
    public const EMAIL = "email";
    public const TOKEN_KEY = 'TOKEN_KEY';
    public const EMAIL_KEY = 'EMAIL_KEY';
    public const ERROR_MESSAGE_KEY = 'ERROR_MESSAGE';
    
    private const KEYS = [
        self::TOKEN_KEY => self::TOKEN,
        self::EMAIL_KEY => self::EMAIL,
        self::ERROR_MESSAGE_KEY => 'ERROR_MESSAGE'
    ];
    private const TEMPLATE_HTML = __DIR__ . '/reset-password.html';

    public function templatePath() : string {
        return self::TEMPLATE_HTML;
    }

    public function templateKeys() : array {
        return self::KEYS;
    }

    public function render(): string
    {
        $this->handleMessages();
        return parent::render();
    }

    private function handleMessages(): void
    {
        $errorMessage = '';

        if (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'missing_fields':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">Tous les champs sont obligatoires.</div>';
                    break;
                case 'passwords_dont_match':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">Les mots de passe ne correspondent pas.</div>';
                    break;
                case 'password_too_short':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">Le mot de passe doit contenir au moins 8 caractères.</div>';
                    break;
                case 'invalid_token':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">Le lien de réinitialisation est invalide ou a expiré.</div>';
                    break;
                case 'database_error':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">Une erreur est survenue. Veuillez réessayer plus tard.</div>';
                    break;
                case 'general_error':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">Une erreur est survenue. Veuillez réessayer plus tard.</div>';
                    break;
            }
        }

        $this->setData([
            'ERROR_MESSAGE' => $errorMessage
        ]);
    }
}
