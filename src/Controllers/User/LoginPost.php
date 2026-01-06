<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Shared\Exceptions\ArrayException;
use Shared\Exceptions\ValidationException;
use Shared\Exceptions\EmailNotFoundException;
use Shared\Exceptions\InvalidPasswordException;
use Shared\Exceptions\DataBaseException;
use Views\User\LoginView;

/**
 * User login submission controller
 *
 * Handles POST requests from the login form.   Validates credentials, checks account
 * verification status, and creates a user session on successful authentication.
 * Displays validation errors if login fails.
 *
 * @package Controllers\User
 */
class LoginPost implements ControllerInterface
{
    /**
     * Main controller method
     *
     * Validates login credentials (email and password), verifies account is activated,
     * and creates a session with user information on successful authentication.
     * Displays error messages for invalid credentials, unverified accounts, or database errors.
     *
     * @return void
     */
    function control()
    {
        // Check if form was submitted
        if (!isset($_POST['ok']))
            return;

        // Extract form data
        $email = $_POST['uname'] ?? '';
        $mdp = $_POST['psw'] ?? '';

        $User = new User();
        $validationExceptions = [];

        // Validate email format
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validationExceptions[] = new ValidationException("Email invalide.");
        }

        // Validate password is not empty
        if (empty($mdp)) {
            $validationExceptions[] = new ValidationException("Le mot de passe ne peut pas être vide.");
        }

        try {
            // If local validation errors exist, throw exception
            if (count($validationExceptions) > 0) {
                throw new ArrayException($validationExceptions);
            }

            // Retrieve user data from database
            try {
                $userData = $User->findByEmail($email);
            } catch (DataBaseException $dbEx) {
                // Wrap database exception in ArrayException
                throw new ArrayException([$dbEx]);
            }

            // Email not found in database
            if (!$userData) {
                throw new ArrayException([new EmailNotFoundException($email)]);
            }

            // Check if account is verified
            if (isset($userData['is_verified']) && (int) $userData['is_verified'] === 0) {
                throw new ArrayException([new ValidationException("Votre compte n'est pas vérifié.  Veuillez cliquer sur le lien reçu par email.")]);
            }

            // Verify password
            if (!password_verify($mdp, $userData['mdp'])) {
                throw new ArrayException([new InvalidPasswordException()]);
            }

            // Login successful - create session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Extract and normalize user role
            $role = isset($userData['role']) ? strtolower(trim($userData['role'])) : 'etudiant';

            // Store user information in session
            $_SESSION['user'] = [
                'id' => $userData['id'],
                'nom' => $userData['nom'],
                'prenom' => $userData['prenom'],
                'mail' => $userData['mail'] ?? $email,
                'role' => $role
            ];

            // Redirect to home page
            header("Location: /");
            exit();

        } catch (ArrayException $exceptions) {
            // Display login form with error messages
            $view = new LoginView($exceptions->getExceptions());
            echo $view->render();
            return;
        }
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $chemin The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/user/login' and method is POST
     */
    static function support(string $chemin, string $method): bool
    {
        return $chemin === "/user/login" && $method === "POST";
    }
}