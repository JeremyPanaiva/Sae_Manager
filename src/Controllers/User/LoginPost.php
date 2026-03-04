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
     * Executes the login logic with rate limiting and JWT session management.
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

        $emailHash = md5($email);
        $attemptsKey = "login_attempts_" . $emailHash;
        $lockoutKey = "login_lockout_" . $emailHash;

        if (isset($_SESSION[$lockoutKey]) && is_numeric($_SESSION[$lockoutKey])) {
            $lockoutTime = (int)$_SESSION[$lockoutKey];
            if (time() < $lockoutTime) {
                $remainingSeconds = $lockoutTime - time();
                $minutes = (int)ceil($remainingSeconds / 60);

                $view = new LoginView([
                    new ValidationException("Too many attempts. Please try again in approximately $minutes minutes.")
                ]);
                echo $view->render();
                return;
            }
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $Logger->create(null, 'ECHEC_CONNEXION', 'users', 0, "Invalid Email Format: $email");
            $validationExceptions[] = new ValidationException("Invalid email format.");
        }

        if (empty($mdp)) {
            $validationExceptions[] = new ValidationException("Password cannot be empty.");
        }

        try {
            if (count($validationExceptions) > 0) {
                throw new ArrayException($validationExceptions);
            }

            try {
                $userData = $User->findByEmail($email);
            } catch (DataBaseException $dbEx) {
                $Logger->create(null, 'ERREUR_SYSTEME', 'database', 0, "DB Error: " . $dbEx->getMessage());
                throw new ArrayException([new ValidationException("System error during login.")]);
            }

            if (!$userData) {
                $Logger->create(null, 'ECHEC_CONNEXION', 'users', 0, "Unknown user: $email");
                throw new ArrayException([new ValidationException("Invalid credentials.")]);
            }

            $userId       = isset($userData['id']) && is_numeric($userData['id']) ? (int)$userData['id'] : 0;
            $isVerified   = isset($userData['is_verified']) && is_numeric($userData['is_verified']) ? (int)$userData['is_verified'] : 1;
            $passwordHash = isset($userData['mdp']) && is_string($userData['mdp']) ? $userData['mdp'] : '';
            $role         = isset($userData['role']) && is_string($userData['role']) ?
                strtolower(trim($userData['role'])) : 'etudiant';
            $nom          = isset($userData['nom']) && is_string($userData['nom']) ? $userData['nom'] : '';
            $prenom       = isset($userData['prenom']) && is_string($userData['prenom']) ? $userData['prenom'] : '';

            if ($isVerified === 0) {
                $Logger->create($userId, 'ECHEC_CONNEXION', 'users', $userId, "Unverified account: $email");
                throw new ArrayException([new ValidationException("Account not verified. Please check your emails.")]);
            }

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

                if ($attempts >= self::MAX_ATTEMPTS) {
                    $_SESSION[$lockoutKey] = time() + self::LOCKOUT_DURATION;
                    unset($_SESSION[$attemptsKey]);

                    throw new ArrayException([
                        new ValidationException("Too many failed attempts. Your access is blocked for 15 minutes.")
                    ]);
                }

                $remaining = self::MAX_ATTEMPTS - $attempts;
                throw new ArrayException([
                    new ValidationException("Invalid credentials. $remaining attempt(s) remaining.")
                ]);
            }

            unset($_SESSION[$attemptsKey], $_SESSION[$lockoutKey]);

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
