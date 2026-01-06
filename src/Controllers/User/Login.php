<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\UserDTO;
use Views\User\LoginView;

/**
 * User login form controller
 *
 * Handles GET requests to display the login form.  Processes URL query parameters
 * to display success messages (e.g., after registration, password reset, email verification)
 * or error messages (e.g., invalid verification token, database errors).
 *
 * @package Controllers\User
 */
class Login implements ControllerInterface
{
    /**
     * Login page route path
     *
     * @var string
     */
    public const PATH = "/user/login";

    /**
     * Main controller method
     *
     * Displays the login form with appropriate success or error messages based on
     * URL query parameters.  Handles various scenarios like successful registration,
     * password reset, account verification, and email changes.
     *
     * @return void
     */
    function control()
    {
        $successMessage = '';

        // Process success messages from query parameters
        if (isset($_GET['success'])) {
            if ($_GET['success'] === 'password_reset') {
                $successMessage = "Votre mot de passe a été réinitialisé avec succès.  Vous pouvez maintenant vous connecter.";
            } elseif ($_GET['success'] === 'account_verified') {
                $successMessage = "Votre compte a été vérifié avec succès.   Vous pouvez maintenant vous connecter.";
            } elseif ($_GET['success'] === 'registered') {
                $successMessage = "Inscription réussie. Veuillez vérifier votre email pour activer votre compte.";
            } elseif ($_GET['success'] === 'email_changed') {
                $successMessage = "Votre email a été mis à jour. Veuillez vérifier votre nouvelle adresse pour réactiver votre compte.";
            }
        }

        $errors = [];

        // Process error messages from query parameters
        if (isset($_GET['error'])) {
            if ($_GET['error'] === 'invalid_token') {
                $errors[] = new \Shared\Exceptions\ValidationException("Le lien de vérification est invalide ou a expiré.");
            } elseif ($_GET['error'] === 'db_error') {
                $errors[] = new \Shared\Exceptions\DataBaseException("Une erreur est survenue lors de la vérification.");
            }
        }

        // Render login view with messages
        $view = new LoginView($errors, $successMessage);
        echo $view->render();
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $chemin The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/user/login' and method is GET
     */
    static function support(string $chemin, string $method): bool
    {
        return $chemin === self:: PATH && $method === "GET";
    }
}