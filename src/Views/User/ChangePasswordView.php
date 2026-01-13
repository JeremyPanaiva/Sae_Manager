<?php

namespace Views\User;

use Views\Base\BaseView;

/**
 * Change Password View
 *
 * Renders the password change form for authenticated users.
 * Displays validation errors and success messages based on form submission results.
 *
 * Handles various password validation states:
 * - Wrong current password
 * - Password mismatch
 * - Password complexity requirements (length, uppercase, lowercase, digit)
 * - Same password as current
 * - Database errors
 *
 * Messages are passed via URL query parameters and rendered as styled alerts.
 *
 * @package Views\User
 */
class ChangePasswordView extends BaseView
{
    /**
     * Path to the change password template file
     */
    private const TEMPLATE_PATH = __DIR__ . '/change-password.php';

    /**
     * Returns the path to the change password template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return self::TEMPLATE_PATH;
    }

    /**
     * Renders the password change form with status messages
     *
     * Checks URL query parameters for error/success states and generates
     * appropriate alert messages to display to the user.
     *
     * Error states:
     * - wrong_password: Current password is incorrect
     * - passwords_dont_match: New passwords don't match
     * - password_too_short: Password must be at least 8 characters
     * - password_no_uppercase: Password must contain uppercase letter
     * - password_no_lowercase: Password must contain lowercase letter
     * - password_no_digit: Password must contain digit
     * - same_password: New password must differ from old
     * - database_error: Technical error occurred
     *
     * Success states:
     * - password_updated: Password successfully changed
     *
     * @return string Rendered HTML output with messages
     */
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
        return (string) ob_get_clean();
    }
}
