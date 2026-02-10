<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Models\Database;
use Shared\Exceptions\ArrayException;
use Shared\Exceptions\ValidationException;
use Shared\Exceptions\DataBaseException;
use Views\User\LoginView;

/**
 * User login submission controller
 *
 * Handles POST requests from the login form. Validates credentials, checks account
 * verification status, creates a user session, and logs login events (success AND failures).
 *
 * @package Controllers\User
 */
class LoginPost implements ControllerInterface
{
    /**
     * Main controller method
     *
     * Validates login credentials (email and password), verifies account is activated,
     * creates a session with user information on successful authentication, and
     * records the login action in the database logs.
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

            // CAS 1 : Email not found in database (Unknown user)
            if (!$userData) {
                // APPEL DE LA FONCTION logFailure ICI
                $this->logFailure(null, "Echec connexion (Email inconnu) : $email");

                $validationExceptions[] = new ValidationException("Email non trouvé: " . $email);
                throw new ArrayException($validationExceptions);
            }

            // Check if account is verified
            $isVerifiedRaw = $userData['is_verified'] ?? 1;
            $isVerified = is_numeric($isVerifiedRaw) ? (int)$isVerifiedRaw : 1;

            // CAS 2 : Account not verified
            if ($isVerified === 0) {
                $rawId = $userData['id'] ?? 0;
                $userId = is_numeric($rawId) ? (int)$rawId : 0;

                // APPEL DE LA FONCTION logFailure ICI
                $this->logFailure($userId, "Echec connexion (Non vérifié) : $email");

                $validationExceptions[] = new ValidationException(
                    "Votre compte n'est pas vérifié. Veuillez cliquer sur le lien reçu par email."
                );
                throw new ArrayException($validationExceptions);
            }

            // Verify password
            $passwordHash = isset($userData['mdp']) && is_string($userData['mdp']) ? $userData['mdp'] : '';

            // CAS 3 : Wrong password
            if ($passwordHash === '' || !password_verify($mdp, $passwordHash)) {
                $rawId = $userData['id'] ?? 0;
                $userId = is_numeric($rawId) ? (int)$rawId : 0;

                // APPEL DE LA FONCTION logFailure ICI
                $this->logFailure($userId, "Echec connexion (Mauvais MDP) : $email");

                $validationExceptions[] = new ValidationException("Mot de passe incorrect.");
                throw new ArrayException($validationExceptions);
            }

            // --- SUCCESS : Login successful ---
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

            // --- AUDIT LOGGING: LOGIN SUCCESS ---
            try {
                $db = Database::getConnection();

                // Safe cast for PHPStan
                $rawId = $userData['id'] ?? 0;
                $userId = is_numeric($rawId) ? (int)$rawId : 0;

                // Add Name and Surname to log details (Safe cast for PHPStan)
                $rawNom = $userData['nom'] ?? '';
                $nom = is_string($rawNom) ? $rawNom : '';

                $rawPrenom = $userData['prenom'] ?? '';
                $prenom = is_string($rawPrenom) ? $rawPrenom : '';

                $details = "Connexion réussie : $nom $prenom";

                $stmt = $db->prepare(
                    "INSERT INTO logs (user_id, action, table_concernee, element_id, details) 
                     VALUES (?, 'CONNEXION', 'users', ?, ?)"
                );

                if ($stmt) {
                    $stmt->bind_param('iis', $userId, $userId, $details);
                    $stmt->execute();
                    $stmt->close();
                }
            } catch (\Throwable $e) {
                // Silently fail logging to avoid blocking the user login process
                error_log("Login Audit Log Error: " . $e->getMessage());
            }
            // --- END AUDIT LOGGING ---

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
     * Logs a failed login attempt to the database.
     *
     * @param int|null $userId The user ID if known, or null if unknown email.
     * @param string $details The details of the failure.
     * @return void
     */
    private function logFailure(?int $userId, string $details): void
    {
        try {
            $db = Database::getConnection();

            // If user ID is null (unknown email), we store 0 or use NULL logic depending on DB schema.
            // Here we use 0 for element_id if unknown.
            $safeUserId = $userId;
            $elementId = $userId ?? 0;

            $stmt = $db->prepare(
                "INSERT INTO logs (user_id, action, table_concernee, element_id, details) 
                 VALUES (?, 'ECHEC_CONNEXION', 'users', ?, ?)"
            );

            if ($stmt) {
                // 'i' for integer, 's' for string. MySQLi handles null for 'i' correctly if nullable.
                $stmt->bind_param('iis', $safeUserId, $elementId, $details);
                $stmt->execute();
                $stmt->close();
            }
        } catch (\Throwable $e) {
            // Fail silently
            error_log("Login Failure Log Error: " . $e->getMessage());
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
