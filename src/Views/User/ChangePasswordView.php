<?php

namespace Views\User;

use Views\Base\BaseView;

class ChangePasswordView extends BaseView
{
    private const TEMPLATE_PATH = __DIR__ . '/change-password.php';

    public function templatePath(): string
    {
        return self::TEMPLATE_PATH;
    }

    public function renderBody(): string
    {
        ob_start();

        $error = $_GET['error'] ?? null;
        $success = $_GET['success'] ?? null;

        $ERROR_MESSAGE = '';
        if ($error) {
            $msg = match ($error) {
                'wrong_password' => "L'ancien mot de passe est incorrect.",
                'passwords_dont_match' => "Les nouveaux mots de passe ne correspondent pas.",
                'password_too_short' => "Le nouveau mot de passe doit faire au moins 8 caractères.",
                'password_no_uppercase' => "Le mot de passe doit contenir au moins une lettre majuscule.",
                'password_no_lowercase' => "Le mot de passe doit contenir au moins une lettre minuscule.",
                'password_no_digit' => "Le mot de passe doit contenir au moins un chiffre.",
                'same_password' => "Le nouveau mot de passe doit être différent de l'ancien.",
                'database_error' => "Une erreur technique est survenue.",
                default => "Une erreur est survenue."
            };
            $ERROR_MESSAGE = "<div class='alert alert-danger'>{$msg}</div>";
        }

        $SUCCESS_MESSAGE = '';
        if ($success === 'password_updated') {
            $SUCCESS_MESSAGE = "<div class='alert alert-success'>Votre mot de passe a été modifié avec succès.</div>";
        }

        include $this->templatePath();
        return ob_get_clean();
    }
}
