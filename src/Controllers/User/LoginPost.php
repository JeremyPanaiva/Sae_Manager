<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Models\User\Log;
use Shared\Exceptions\ArrayException;
use Shared\Exceptions\ValidationException;
use Shared\Exceptions\DataBaseException;
use Shared\JwtService;
use Views\User\LoginView;

/**
 * Class LoginPost
 *
 * Handles the POST request for user authentication.
 * Includes rate limiting to prevent brute-force attacks
 * and JWT generation for automatic session expiration after 1 hour.
 *
 * @package Controllers\User
 */
class LoginPost implements ControllerInterface
{
    /**
     * Maximum number of failed login attempts before lockout.
     */
    private const MAX_ATTEMPTS = 5;

    /**
     * Lockout duration in seconds (15 minutes).
     */
    private const LOCKOUT_DURATION = 900;

    /**
     * Executes the login logic with rate limiting and JWT session management.
     *
     * 1. Sanitizes inputs.
     * 2. Checks rate limiting (lockout).
     * 3. Retrieves user data.
     * 4. Safely extracts and casts database values.
     * 5. Validates business rules (Account verified, Password correct).
     * 6. Generates a JWT token valid for 1 hour.
     * 7. Logs the result.
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
            $Logger->create(null, 'ECHEC_CONNEXION', 'users', 0, "Format Email Invalid " . ($email ?: 'empty'));

            $validationExceptions[] = new ValidationException("Invalid Email Format.");
        }
        if (empty($mdp)) {
            $Logger->create(null, 'ECHEC_CONNEXION', 'users', 0, "Mot de passe vide pour : $email");
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
                $Logger->create(null, 'ERREUR_SYSTEME', 'database', 0, "Erreur DB : " . $dbEx->getMessage());
                throw new ArrayException($validationExceptions);
            }

            // --- SECURITY CHECKS ---

            // CASE A: User Not Found
            // FIX PHPSTAN: We removed "!is_array($userData)" because PHPStan knows
            // that if $userData is not false, it is definitely an array.
            if (!$userData) {
                $Logger->create(null, 'ECHEC_CONNEXION', 'users', 0, "Unknown User: $email");

                $validationExceptions[] = new ValidationException("Email not found: " . $email);
                throw new ArrayException($validationExceptions);
            }

            // --- DATA NORMALIZATION ---
            // We verify the array keys exist before using them to satisfy strict typing.

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

                $validationExceptions[] = new ValidationException("Account not verified. Please check your emails.");
                throw new ArrayException($validationExceptions);
            }

            // CASE C: Incorrect Password
            if ($passwordHash === '' || !password_verify($mdp, $passwordHash)) {
                $Logger->create($userId, 'ECHEC_CONNEXION', 'users', $userId, "Wrong Password for: $email");

                $rawAttempts  = $_SESSION[$attemptsKey] ?? 0;
                $prevAttempts = is_numeric($rawAttempts) ? (int)$rawAttempts : 0;
                $attempts     = $prevAttempts + 1;
                $_SESSION[$attemptsKey] = $attempts;

                $remaining = self::MAX_ATTEMPTS - $attempts;

                if ($attempts >= self::MAX_ATTEMPTS) {
                    // Déclencher le blocage
                    $_SESSION[$lockoutKey] = time() + self::LOCKOUT_DURATION;
                    unset($_SESSION[$attemptsKey]);
                    $Logger->create($userId, 'ECHEC_CONNEXION', 'users', $userId, "Compte bloqué 
                    après $attempts tentatives : $email");

                    $validationExceptions[] = new ValidationException(
                        "Trop de tentatives échouées. Votre accès est bloqué pendant 15 minutes."
                    );
                } else {
                    $validationExceptions[] = new ValidationException(
                        "Mot de passe incorrect. Il vous reste $remaining tentative(s) avant blocage."
                    );
                }

                throw new ArrayException($validationExceptions);
            }

            unset($_SESSION[$attemptsKey], $_SESSION[$lockoutKey]);

            $jwt = JwtService::generate([
                'sub'  => $userId,
                'role' => $role,
                'mail' => $userData['mail'] ?? $email,
            ]);

            $_SESSION['jwt_token'] = $jwt;
            $_SESSION['user'] = [
                'id' => $userId,
                'nom' => $nom,
                'prenom' => $prenom,
                'mail'   => $userData['mail'] ?? $email,
                'role'   => $role,
            ];

            $User->saveJwtToken($userId, $jwt);

            $fullName = $nom . ' ' . $prenom;
            $Logger->create($userId, 'CONNEXION', 'users', $userId, "Connexion de : $fullName");

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
