<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Models\Log;
use Shared\Exceptions\ArrayException;
use Shared\Exceptions\ValidationException;
use Shared\Exceptions\DataBaseException;
use Views\User\LoginView;

/**
 * Class LoginPost
 *
 * Handles the POST request for user authentication.
 * It validates credentials, checks account status (verified/active),
 * manages session creation, and records audit logs for security.
 *
 * @package Controllers\User
 */
class LoginPost implements ControllerInterface
{
    /**
     * Executes the login logic.
     *
     * 1. Validates form inputs.
     * 2. Retrieves user by email.
     * 3. Checks specific failure scenarios (Unknown email, Unverified account, Wrong password).
     * 4. Logs failures using the Log model.
     * 5. On success, initializes the session and logs the connection event.
     *
     * @return void
     */
    public function control()
    {
        // 1. Check if form is submitted
        if (!isset($_POST['ok'])) {
            return;
        }

        $email = $_POST['uname'] ?? '';
        $mdp = $_POST['psw'] ?? '';

        // Model Instantiation
        $User = new User();
        $Logger = new Log(); // Audit Logger

        $validationExceptions = [];

        // 2. Input Validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validationExceptions[] = new ValidationException("Invalid Email Format.");
        }
        if (empty($mdp)) {
            $validationExceptions[] = new ValidationException("Password cannot be empty.");
        }

        try {
            if (count($validationExceptions) > 0) {
                throw new ArrayException($validationExceptions);
            }

            // 3. Retrieve User Data
            try {
                $userData = $User->findByEmail($email);
            } catch (DataBaseException $dbEx) {
                $validationExceptions[] = new ValidationException($dbEx->getMessage());
                throw new ArrayException($validationExceptions);
            }

            // --- SECURITY CHECKS ---

            // CASE A: User Not Found (Unknown Email)
            if (!$userData) {
                // Audit: Log failed attempt with NULL user_id
                $Logger->create(null, 'ECHEC_CONNEXION', 'users', 0, "Unknown User: $email");

                $validationExceptions[] = new ValidationException("Email not found: " . $email);
                throw new ArrayException($validationExceptions);
            }

            // CASE B: Account Not Verified
            $isVerified = (int)($userData['is_verified'] ?? 1);
            if ($isVerified === 0) {
                $userId = (int)$userData['id'];

                // Audit: Log attempt on unverified account
                $Logger->create($userId, 'ECHEC_CONNEXION', 'users', $userId, "Unverified Account: $email");

                $validationExceptions[] = new ValidationException("Account not verified. Please check your emails.");
                throw new ArrayException($validationExceptions);
            }

            // CASE C: Incorrect Password
            $passwordHash = $userData['mdp'] ?? '';
            if ($passwordHash === '' || !password_verify($mdp, $passwordHash)) {
                $userId = (int)$userData['id'];

                // Audit: Log security failure (Critical)
                $Logger->create($userId, 'ECHEC_CONNEXION', 'users', $userId, "Wrong Password for: $email");

                $validationExceptions[] = new ValidationException("Incorrect Password.");
                throw new ArrayException($validationExceptions);
            }

            // --- LOGIN SUCCESS ---

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $role = strtolower(trim($userData['role'] ?? 'etudiant'));

            // Set Session Data
            $_SESSION['user'] = [
                'id' => $userData['id'],
                'nom' => $userData['nom'],
                'prenom' => $userData['prenom'],
                'mail' => $userData['mail'] ?? $email,
                'role' => $role
            ];

            // Audit: Log successful connection
            $userId = (int)$userData['id'];
            $fullName = ($userData['nom'] ?? '') . ' ' . ($userData['prenom'] ?? '');

            $Logger->create($userId, 'CONNEXION', 'users', $userId, "Login Success: $fullName");

            // Redirect to Dashboard
            header("Location: /");
            exit();

        } catch (ArrayException $exceptions) {
            // Render View with Errors
            $view = new LoginView($exceptions->getExceptions());
            echo $view->render();
            return;
        }
    }

    /**
     * Router Support Check
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === "/user/login" && $method === "POST";
    }
}
