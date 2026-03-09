<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Shared\Exceptions\ValidationException;
use Shared\Exceptions\DataBaseException;
use Views\User\LoginView;

/**
 * User login form controller
 *
 * Handles GET requests to display the login form. Processes URL query parameters
 * to display success messages (e.g., after registration, password reset, email verification)
 * or error messages (e.g., invalid verification token, concurrent login detection).
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
     * URL query parameters. Handles various scenarios like successful registration,
     * password reset, session expiration, and concurrent login detection.
     *
     * @return void
     */
    public function control(): void
    {
        $successMessage = '';

        // --- Handle Success Messages ---
        if (isset($_GET['success'])) {
            switch ($_GET['success']) {
                case 'password_reset':
                    $successMessage = "Votre mot de passe a été réinitialisé 
                    avec succès. Vous pouvez maintenant vous connecter.";
                    break;
                case 'account_verified':
                    $successMessage = "Votre compte a été vérifié avec succès. Vous pouvez maintenant vous connecter.";
                    break;
                case 'registered':
                    $successMessage = "Inscription réussie. Veuillez vérifier votre email pour activer votre compte.";
                    break;
                case 'email_changed':
                    $successMessage = "Votre email a été mis à jour. Veuillez 
                    vérifier votre nouvelle adresse pour réactiver votre compte.";
                    break;
            }
        }

        $errors = [];

        // --- Handle Error Messages ---
        if (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'invalid_token':
                    $errors[] = new ValidationException("Le lien de vérification est invalide ou a expiré.");
                    break;
                case 'db_error':
                    $errors[] = new DataBaseException("Une erreur système est survenue lors de la vérification.");
                    break;
                case 'concurrent_login':
                    $errors[] = new ValidationException("Vous avez été déconnecté car
                     une session a été ouverte sur un autre appareil.");
                    break;
            }
        }

        // --- Handle Session Expiration ---
        if (isset($_GET['expired']) && $_GET['expired'] === '1') {
            $errors[] = new ValidationException("Votre session a expiré. Veuillez vous reconnecter.");
        }

        // Render the login view with gathered messages
        $view = new LoginView($errors, $successMessage);
        echo $view->render();
    }

    /**
     * Checks if this controller supports the given route and HTTP method.
     *
     * @param string $chemin The requested route path.
     * @param string $method The HTTP method (GET, POST, etc.).
     * @return bool True if path is '/user/login' and method is GET.
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === self::PATH && $method === "GET";
    }
}
