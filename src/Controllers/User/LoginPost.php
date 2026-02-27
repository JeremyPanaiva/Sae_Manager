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

        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Strict input typing
        $emailRaw = $_POST['uname'] ?? '';
        $email = is_string($emailRaw) ? $emailRaw : '';

        $mdpRaw = $_POST['psw'] ?? '';
        $mdp = is_string($mdpRaw) ? $mdpRaw : '';

        $Logger = new Log();

        // --- 2. RATE LIMITING CHECK ---
        $lockoutKey  = 'login_lockout_until_' . md5($email);
        $attemptsKey = 'login_attempts_' . md5($email);

        // FIX PHPSTAN (cast.int): extraire la valeur mixed de session dans une variable typée avant le cast
        $rawLockoutUntil = $_SESSION[$lockoutKey] ?? 0;
        $lockoutUntil    = is_numeric($rawLockoutUntil) ? (int)$rawLockoutUntil : 0;

        if ($lockoutUntil > 0 && time() < $lockoutUntil) {
            $remainingSeconds = $lockoutUntil - time();
            $remainingMinutes = (int)ceil($remainingSeconds / 60);

            $Logger->create(null, 'ECHEC_CONNEXION', 'users', 0, "Tentative sur compte bloqué : $email");

            $view = new LoginView([new ValidationException(
                "Accès bloqué pendant 15 minutes. Veuillez réessayer dans $remainingMinutes minute(s)."
            )]);
            echo $view->render();
            return;
        }

        // --- EXISTING VALIDATION LOGIC ---
        $User = new User();
        $validationExceptions = [];

        // 3. Validate Inputs
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

            // 4. Retrieve User Data
            try {
                $userData = $User->findByEmail($email);
            } catch (DataBaseException $dbEx) {
                $validationExceptions[] = new ValidationException($dbEx->getMessage());
                $Logger->create(null, 'ERREUR_SYSTEME', 'database', 0, "Erreur DB : " . $dbEx->getMessage());
                throw new ArrayException($validationExceptions);
            }

            // --- SECURITY CHECKS ---

            // CASE A: User Not Found
            if (!$userData) {
                $Logger->create(null, 'ECHEC_CONNEXION', 'users', 0, "Unknown User: $email");
                $validationExceptions[] = new ValidationException("Email not found: " . $email);
                throw new ArrayException($validationExceptions);
            }

            // --- DATA NORMALIZATION ---
            $rawId        = $userData['id'] ?? 0;
            $userId       = is_numeric($rawId) ? (int)$rawId : 0;

            $rawVerified  = $userData['is_verified'] ?? 1;
            $isVerified   = is_numeric($rawVerified) ? (int)$rawVerified : 1;

            $rawPass      = $userData['mdp'] ?? '';
            $passwordHash = is_string($rawPass) ? $rawPass : '';

            $rawRole      = $userData['role'] ?? 'etudiant';
            $role         = is_string($rawRole) ? strtolower(trim($rawRole)) : 'etudiant';

            $nomRaw       = $userData['nom'] ?? '';
            $nom          = is_string($nomRaw) ? $nomRaw : '';

            $prenomRaw    = $userData['prenom'] ?? '';
            $prenom       = is_string($prenomRaw) ? $prenomRaw : '';

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

                // FIX PHPSTAN (binaryOp.invalid): extraire et typer explicitement avant l'opération arithmétique
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

            // --- LOGIN SUCCESS ---

            // Réinitialiser le compteur en cas de succès
            unset($_SESSION[$attemptsKey], $_SESSION[$lockoutKey]);

            // Générer un JWT valable 1 heure
            $jwt = JwtService::generate([
                'sub'  => $userId,
                'role' => $role,
                'mail' => $userData['mail'] ?? $email,
            ]);

            // Stocker le JWT en session et en base de données
            $_SESSION['jwt_token'] = $jwt;
            $_SESSION['user'] = [
                'id'     => $userId,
                'nom'    => $nom,
                'prenom' => $prenom,
                'mail'   => $userData['mail'] ?? $email,
                'role'   => $role,
            ];

            // Persister le JWT en BDD pour pouvoir l'invalider côté serveur si besoin
            $User->saveJwtToken($userId, $jwt);

            // Audit: Log success
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
