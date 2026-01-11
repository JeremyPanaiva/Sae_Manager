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
    public function control()
    {
        // Check if form was submitted
        if (!isset($_POST['ok'])) {
            return;
        }

        // Extract form data
        $email = $_POST['uname'] ?? '';
        $mdp = $_POST['psw'] ?? '';

        $User = new User();
        /** @var array<ValidationException> $validationExceptions */
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
                // Convert database exception to validation exception
                $validationExceptions[] = new ValidationException($dbEx->getMessage());
                throw new ArrayException($validationExceptions);
            }

            // Email not found in database
            if (!$userData) {
                $validationExceptions[] = new ValidationException("Email non trouvé: " . $email);
                throw new ArrayException($validationExceptions);
            }

            // Check if account is verified
            $isVerifiedRaw = $userData['is_verified'] ?? 1;
            $isVerified = is_numeric($isVerifiedRaw) ? (int)$isVerifiedRaw : 1;
            if ($isVerified === 0) {
                $validationExceptions[] = new ValidationException(
                    "Votre compte n'est pas vérifié. Veuillez cliquer sur le lien reçu par email."
                );
                throw new ArrayException($validationExceptions);
            }

            // Verify password
            $passwordHash = isset($userData['mdp']) && is_string($userData['mdp']) ? $userData['mdp'] : '';
            if ($passwordHash === '' || !password_verify($mdp, $passwordHash)) {
                $validationExceptions[] = new ValidationException("Mot de passe incorrect.");
                throw new ArrayException($validationExceptions);
            }

            // Login successful - create session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Extract and normalize user role
            $roleRaw = $userData['role'] ?? 'etudiant';
            $role = is_string($roleRaw) ? strtolower(trim($roleRaw)) : 'etudiant';

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
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === "/user/login" && $method === "POST";
    }
}
