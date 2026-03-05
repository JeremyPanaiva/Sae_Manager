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
 * and JWT generation for session management.
 *
 * @package Controllers\User
 */
class LoginPost implements ControllerInterface
{
    /** @var int Maximum number of failed login attempts before lockout */
    private const MAX_ATTEMPTS = 5;

    /** @var int Lockout duration in seconds (15 minutes) */
    private const LOCKOUT_DURATION = 900;

    /**
     * Executes the login logic with rate limiting and session management.
     *
     * @return void
     */
    public function control(): void
    {
        if (!isset($_POST['ok'])) {
            return;
        }

        $email = isset($_POST['uname']) && is_string($_POST['uname']) ? trim($_POST['uname']) : '';
        $mdp = isset($_POST['psw']) && is_string($_POST['psw']) ? $_POST['psw'] : '';

        $User = new User();
        $Logger = new Log();
        $validationExceptions = [];

        // Create unique session keys based on the email hash
        $emailHash = md5($email);
        $attemptsKey = "login_attempts_" . $emailHash;
        $lockoutKey = "login_lockout_" . $emailHash;

        // 1. Check if the account is currently locked out
        if (isset($_SESSION[$lockoutKey]) && is_numeric($_SESSION[$lockoutKey])) {
            $lockoutTime = (int)$_SESSION[$lockoutKey];
            if (time() < $lockoutTime) {
                $remainingSeconds = $lockoutTime - time();
                $minutes = (int)ceil($remainingSeconds / 60);

                // UI Error in French
                $view = new LoginView([
                    new ValidationException("Trop de tentatives. Veuillez réessayer dans environ $minutes minute(s).")
                ]);
                echo $view->render();
                return;
            }
        }

        // 2. Validate field formats
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $Logger->create(null, 'ECHEC_CONNEXION', 'users', 0, "Invalid email format: $email");
            $validationExceptions[] = new ValidationException("Le format de l'adresse email est invalide.");
        }

        if (empty($mdp)) {
            $validationExceptions[] = new ValidationException("Le mot de passe ne peut pas être vide.");
        }

        try {
            if (count($validationExceptions) > 0) {
                throw new ArrayException($validationExceptions);
            }

            // 3. Retrieve user from database
            try {
                $userData = $User->findByEmail($email);
            } catch (DataBaseException $dbEx) {
                $Logger->create(null, 'ERREUR_SYSTEME', 'database', 0, "DB Error: " . $dbEx->getMessage());
                throw new ArrayException([new ValidationException("Erreur système lors de la connexion.")]);
            }

            // 4. Email does not exist in the database
            if (!$userData) {
                $Logger->create(null, 'ECHEC_CONNEXION', 'users', 0, "Unknown user: $email");
                throw new ArrayException([new ValidationException("Adresse email invalide ou inconnue.")]);
            }

            $userId       = isset($userData['id']) && is_numeric($userData['id']) ? (int)$userData['id'] : 0;
            $isVerified   = isset($userData['is_verified']) && is_numeric($userData['is_verified']) ?
                (int)$userData['is_verified'] : 1;
            $passwordHash = isset($userData['mdp']) && is_string($userData['mdp']) ? $userData['mdp'] : '';
            $role         = isset($userData['role']) && is_string($userData['role']) ?
                strtolower(trim($userData['role'])) : 'etudiant';
            $nom          = isset($userData['nom']) && is_string($userData['nom']) ? $userData['nom'] : '';
            $prenom       = isset($userData['prenom']) && is_string($userData['prenom']) ? $userData['prenom'] : '';

            // 5. Check if the account is verified
            if ($isVerified === 0) {
                $Logger->create($userId, 'ECHEC_CONNEXION', 'users', $userId, "Unverified account: $email");
                throw new ArrayException([new ValidationException("Compte 
                non vérifié. Veuillez consulter vos emails.")]);
            }

            // 6. Incorrect password
            if ($passwordHash === '' || !password_verify($mdp, $passwordHash)) {
                $sessionAttempts = isset($_SESSION[$attemptsKey]) &&
                is_numeric($_SESSION[$attemptsKey]) ? (int)$_SESSION[$attemptsKey] : 0;
                $attempts = $sessionAttempts + 1;
                $_SESSION[$attemptsKey] = $attempts;

                $Logger->create(
                    $userId,
                    'ECHEC_CONNEXION',
                    'users',
                    $userId,
                    "Wrong password ($attempts/" . self::MAX_ATTEMPTS . "): $email"
                );

                // If max attempts reached, lock the account
                if ($attempts >= self::MAX_ATTEMPTS) {
                    $_SESSION[$lockoutKey] = time() + self::LOCKOUT_DURATION;
                    unset($_SESSION[$attemptsKey]);

                    throw new ArrayException([
                        new ValidationException("Trop de tentatives échouées. 
                        Votre accès est bloqué pendant 15 minutes.")
                    ]);
                }

                // Otherwise, warn the user
                $remaining = self::MAX_ATTEMPTS - $attempts;
                throw new ArrayException([
                    new ValidationException("Mauvais mot de passe. Il vous reste $remaining tentative(s).")
                ]);
            }

            // 7. Success: reset error counters
            unset($_SESSION[$attemptsKey], $_SESSION[$lockoutKey]);

            // Generate JWT and setup session
            $jwt = JwtService::generate([
                'sub'  => $userId,
                'role' => $role,
                'mail' => (isset($userData['mail']) && is_string($userData['mail']) ? $userData['mail'] : $email),
            ]);

            $_SESSION['jwt_token'] = $jwt;
            $_SESSION['user'] = [
                'id'     => $userId,
                'nom'    => $nom,
                'prenom' => $prenom,
                'mail'   => (isset($userData['mail']) && is_string($userData['mail']) ? $userData['mail'] : $email),
                'role'   => $role,
            ];

            $User->saveJwtToken($userId, $jwt);

            $Logger->create($userId, 'CONNEXION', 'users', $userId, "Successful login: $nom $prenom");

            header("Location: /");
            exit();
        } catch (ArrayException $exceptions) {
            $view = new LoginView($exceptions->getExceptions());
            echo $view->render();
            return;
        }
    }

    /**
     * Checks if this controller supports the requested route.
     *
     * @param string $chemin The requested URL path.
     * @param string $method The HTTP method (POST, GET, etc.).
     * @return bool
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === "/user/login" && $method === "POST";
    }
}
