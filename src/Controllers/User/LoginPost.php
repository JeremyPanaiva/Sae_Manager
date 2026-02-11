<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Models\User\Log;
use Shared\Exceptions\ArrayException;
use Shared\Exceptions\ValidationException;
use Shared\Exceptions\DataBaseException;
use Views\User\LoginView;

/**
 * Class LoginPost
 *
 * Handles the POST request for user authentication.
 * It strictly validates input types to satisfy static analysis (PHPStan)
 * and delegates audit logging to the Log model.
 *
 * @package Controllers\User
 */
class LoginPost implements ControllerInterface
{
    /**
     * Executes the login logic.
     *
     * 1. Sanitizes inputs.
     * 2. Retrieves user data.
     * 3. Safely extracts and casts database values.
     * 4. Validates business rules (Account verified, Password correct).
     * 5. Logs the result.
     *
     * @return void
     */
    public function control()
    {
        // 1. Check form submission
        if (!isset($_POST['ok'])) {
            return;
        }

        // Strict input typing
        $emailRaw = $_POST['uname'] ?? '';
        $email = is_string($emailRaw) ? $emailRaw : '';

        $mdpRaw = $_POST['psw'] ?? '';
        $mdp = is_string($mdpRaw) ? $mdpRaw : '';

        $User = new User();
        $Logger = new Log();

        $validationExceptions = [];

        // 2. Validate Inputs
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validationExceptions[] = new ValidationException("Format d'email invalide.");
        }
        if (empty($mdp)) {
            $validationExceptions[] = new ValidationException("Le mot de passe ne peut pas être vide.");
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

            if (!$userData) {
                $Logger->create(null, 'ECHEC_CONNEXION', 'users', 0, "Unknown User: $email");

                $validationExceptions[] = new ValidationException("Adresse mail introuvable: " . $email);
                throw new ArrayException($validationExceptions);
            }

            // Safe ID extraction
            $rawId = $userData['id'] ?? 0;
            $userId = is_numeric($rawId) ? (int)$rawId : 0;

            // Safe Verified Status extraction
            $rawVerified = $userData['is_verified'] ?? 1;
            $isVerified = is_numeric($rawVerified) ? (int)$rawVerified : 1;

            // Safe Password Hash extraction
            $rawPass = $userData['mdp'] ?? '';
            $passwordHash = is_string($rawPass) ? $rawPass : '';

            // Safe Role extraction
            $rawRole = $userData['role'] ?? 'etudiant';
            $role = is_string($rawRole) ? strtolower(trim($rawRole)) : 'etudiant';

            // Safe Name extraction
            $nomRaw = $userData['nom'] ?? '';
            $nom = is_string($nomRaw) ? $nomRaw : '';

            $prenomRaw = $userData['prenom'] ?? '';
            $prenom = is_string($prenomRaw) ? $prenomRaw : '';

            // --- LOGIC EXECUTION ---

            // CASE B: Account Not Verified
            if ($isVerified === 0) {
                $Logger->create($userId, 'ECHEC_CONNEXION', 'users', $userId, "Unverified Account: $email");

                $validationExceptions[] = new ValidationException("Compte non vérifié. Veuillez consulter vos e-mails.");
                throw new ArrayException($validationExceptions);
            }

            // CASE C: Incorrect Password
            if ($passwordHash === '' || !password_verify($mdp, $passwordHash)) {
                $Logger->create($userId, 'ECHEC_CONNEXION', 'users', $userId, "Wrong Password for: $email");

                $validationExceptions[] = new ValidationException("Mot de passe incorrect.");
                throw new ArrayException($validationExceptions);
            }

            // --- LOGIN SUCCESS ---

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['user'] = [
                'id' => $userId,
                'nom' => $nom,
                'prenom' => $prenom,
                'mail' => $userData['mail'] ?? $email,
                'role' => $role
            ];

            // Audit: Log success
            $fullName = $nom . ' ' . $prenom;

            $Logger->create($userId, 'CONNEXION', 'users', $userId, "Connexion de  : $fullName");

            header("Location: /");
            exit();
        } catch (ArrayException $exceptions) {
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
