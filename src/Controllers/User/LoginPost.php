<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Models\User\Log;
use Shared\Exceptions\ArrayException;
use Shared\Exceptions\ValidationException;
use Shared\Exceptions\DataBaseException;
use Shared\JwtService;
use Shared\CsrfGuard;
use Views\User\LoginView;

/**
 * Class LoginPost
 *
 * Handles the HTTP POST request for user authentication.
 * Incorporates protection against brute-force attacks (rate limiting),
 * JWT token generation for session management, and updates the last
 * connection timestamp (GDPR/CNIL compliance).
 *
 * @package Controllers\User
 */
class LoginPost implements ControllerInterface
{
    /** * @var int Maximum number of failed login attempts allowed before lockout.
     */
    private const MAX_ATTEMPTS = 5;

    /** * @var int Account lockout duration in seconds (900s = 15 minutes).
     */
    private const LOCKOUT_DURATION = 900;

    /**
     * Executes the login logic with rate limiting and session management.
     *
     * This process checks for potential lockouts, validates input data,
     * authenticates the user via the database, updates their last
     * connection timestamp, and generates a JWT token upon success.
     *
     * @return void
     */
    public function control(): void
    {
        // Abort execution if the form was not submitted properly
        if (!isset($_POST['ok'])) {
            return;
        }

        if (!CsrfGuard::validate()) {
            http_response_code(403);
            die('Invalid request (CSRF).');
        }

        $email = isset($_POST['uname']) && is_string($_POST['uname']) ? trim($_POST['uname']) : '';
        $mdp = isset($_POST['psw']) && is_string($_POST['psw']) ? $_POST['psw'] : '';

        $User = new User();
        $Logger = new Log();
        $validationExceptions = [];

        // Create unique session keys based on the email hash for rate limiting
        $emailHash = md5($email);
        $attemptsKey = "login_attempts_" . $emailHash;
        $lockoutKey = "login_lockout_" . $emailHash;

        // 1. Check if the account is currently locked out (Rate Limiting)
        if (isset($_SESSION[$lockoutKey]) && is_numeric($_SESSION[$lockoutKey])) {
            $lockoutTime = (int)$_SESSION[$lockoutKey];
            if (time() < $lockoutTime) {
                $remainingSeconds = $lockoutTime - time();
                $minutes = (int)ceil($remainingSeconds / 60);

                // Display UI error
                $view = new LoginView([
                    new ValidationException("Trop de tentatives. Veuillez réessayer dans environ $minutes minute(s).")
                ]);
                echo $view->render();
                return;
            }
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $Logger->create(null, 'ECHEC_CONNEXION', 'users', 0, "Format d'email invalide : $email");
            $validationExceptions[] = new ValidationException("Le format de l'adresse email est invalide.");
        }

        if (empty($mdp)) {
            $validationExceptions[] = new ValidationException("Le mot de passe ne peut pas être vide.");
        }

        try {
            // Throw a global exception if validation errors are detected
            if (count($validationExceptions) > 0) {
                throw new ArrayException($validationExceptions);
            }

            try {
                $userData = $User->findByEmail($email);
            } catch (DataBaseException $dbEx) {
                $Logger->create(null, 'ERREUR_SYSTEME', 'database', 0, "Erreur BDD : " . $dbEx->getMessage());
                throw new ArrayException([new ValidationException("Erreur système lors de la connexion.")]);
            }

            if (!$userData) {
                $Logger->create(null, 'ECHEC_CONNEXION', 'users', 0, "Email introuvable : $email");
                throw new ArrayException([new ValidationException("Adresse email invalide ou inconnue.")]);
            }

            // Securely extract user data
            $userId       = isset($userData['id']) && is_numeric($userData['id']) ? (int)$userData['id'] : 0;
            $isVerified   = isset($userData['is_verified']) && is_numeric($userData['is_verified']) ?
                (int)$userData['is_verified'] : 1;
            $passwordHash = isset($userData['mdp']) && is_string($userData['mdp']) ? $userData['mdp'] : '';
            $role         = isset($userData['role']) && is_string($userData['role']) ?
                strtolower(trim($userData['role'])) : 'etudiant';
            $nom          = isset($userData['nom']) && is_string($userData['nom']) ? $userData['nom'] : '';
            $prenom       = isset($userData['prenom']) && is_string($userData['prenom']) ? $userData['prenom'] : '';

            if ($isVerified === 0) {
                $Logger->create($userId, 'ECHEC_CONNEXION', 'users', $userId, "Compte non vérifié : $email");
                throw new ArrayException([new ValidationException("Compte non vérifié.
                 Veuillez consulter vos emails.")]);
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
                    "Mauvais mot de passe ($attempts/" . self::MAX_ATTEMPTS . ") : $email"
                );

                // Lockout if the maximum number of attempts is reached
                if ($attempts >= self::MAX_ATTEMPTS) {
                    $_SESSION[$lockoutKey] = time() + self::LOCKOUT_DURATION;
                    unset($_SESSION[$attemptsKey]);

                    throw new ArrayException([
                        new ValidationException("Trop de tentatives échouées. Votre accès
                         est bloqué pendant 15 minutes.")
                    ]);
                }

                $remaining = self::MAX_ATTEMPTS - $attempts;
                throw new ArrayException([
                    new ValidationException("Mauvais mot de passe. Il vous reste $remaining tentative(s).")
                ]);
            }

            unset($_SESSION[$attemptsKey], $_SESSION[$lockoutKey]);

            // Update last connection date for GDPR compliance tracking
            $User->updateLastConnection($userId);

            // Generate JWT token
            $jwt = JwtService::generate([
                'sub'  => $userId,
                'role' => $role,
                'mail' => (isset($userData['mail']) && is_string($userData['mail']) ? $userData['mail'] : $email),
            ]);

            // Initialize session variables
            $_SESSION['jwt_token'] = $jwt;
            $_SESSION['user'] = [
                'id'     => $userId,
                'nom'    => $nom,
                'prenom' => $prenom,
                'mail'   => (isset($userData['mail']) && is_string($userData['mail']) ? $userData['mail'] : $email),
                'role'   => $role,
            ];

            // Save token and log the action
            $User->saveJwtToken($userId, $jwt);
            $Logger->create($userId, 'CONNEXION', 'users', $userId, "Connexion de : $nom $prenom");

            // Redirect to dashboard / home page
            header("Location: /");
            exit();
        } catch (ArrayException $exceptions) {
            // Display errors caught during the process
            $view = new LoginView($exceptions->getExceptions());
            echo $view->render();
            return;
        }
    }

    /**
     * Checks if this controller should handle the current request.
     *
     * @param string $chemin The requested URL path (e.g., "/user/login").
     * @param string $method The HTTP method used (e.g., "POST", "GET").
     * @return bool Returns true if the path is "/user/login" and the method is "POST".
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === "/user/login" && $method === "POST";
    }
}
