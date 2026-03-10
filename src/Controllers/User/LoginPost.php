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
use Shared\RateLimiter;
use Views\User\LoginView;

/**
 * Class LoginPost
 *
 * Handles the HTTP POST request for user authentication.
 * Brute-force protection is delegated to RateLimiter.
 * JWT token generation handles session management.
 * Updates the last connection timestamp (GDPR/CNIL compliance).
 *
 * @package Controllers\User
 */
class LoginPost implements ControllerInterface
{
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

        if (!CsrfGuard::validate()) {
            http_response_code(403);
            die('Invalid request (CSRF).');
        }

        $email = isset($_POST['uname']) && is_string($_POST['uname']) ? trim($_POST['uname']) : '';
        $mdp   = isset($_POST['psw'])   && is_string($_POST['psw'])   ? $_POST['psw']         : '';

        $User   = new User();
        $Logger = new Log();
        $validationExceptions = [];

        // 1. Check lockout via RateLimiter
        $remainingSeconds = RateLimiter::getLockoutRemainingSeconds($email);
        if ($remainingSeconds > 0) {
            $minutes = (int) ceil($remainingSeconds / 60);
            $view = new LoginView([
                new ValidationException("Trop de tentatives. Veuillez réessayer dans environ $minutes minute(s).")
            ]);
            echo $view->render();
            return;
        }

        // 2. Input validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $Logger->create(null, 'ECHEC_CONNEXION', 'users', 0, "Format d'email invalide : $email");
            $validationExceptions[] = new ValidationException("Le format de l'adresse email est invalide.");
        }

        if (empty($mdp)) {
            $validationExceptions[] = new ValidationException("Le mot de passe ne peut pas être vide.");
        }

        try {
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
            $userId       = isset($userData['id'])          && is_numeric($userData['id'])          ? (int) $userData['id']                  : 0;
            $isVerified   = isset($userData['is_verified']) && is_numeric($userData['is_verified']) ? (int) $userData['is_verified']          : 1;
            $passwordHash = isset($userData['mdp'])         && is_string($userData['mdp'])          ? $userData['mdp']                       : '';
            $role         = isset($userData['role'])        && is_string($userData['role'])         ? strtolower(trim($userData['role']))     : 'etudiant';
            $nom          = isset($userData['nom'])         && is_string($userData['nom'])          ? $userData['nom']                       : '';
            $prenom       = isset($userData['prenom'])      && is_string($userData['prenom'])       ? $userData['prenom']                    : '';

            if ($isVerified === 0) {
                $Logger->create($userId, 'ECHEC_CONNEXION', 'users', $userId, "Compte non vérifié : $email");
                throw new ArrayException([
                    new ValidationException("Compte non vérifié. Veuillez consulter vos emails.")
                ]);
            }

            // 3. Password check — delegate attempt tracking to RateLimiter
            if ($passwordHash === '' || !password_verify($mdp, $passwordHash)) {
                $attempts = RateLimiter::recordFailedLoginAttempt($email);
                $max      = RateLimiter::getLoginMaxAttempts();

                $Logger->create(
                    $userId,
                    'ECHEC_CONNEXION',
                    'users',
                    $userId,
                    "Mauvais mot de passe ($attempts/$max) : $email"
                );

                if ($attempts >= $max) {
                    throw new ArrayException([
                        new ValidationException("Trop de tentatives échouées. Votre accès est bloqué pendant 15 minutes.")
                    ]);
                }

                $remaining = $max - $attempts;
                throw new ArrayException([
                    new ValidationException("Mauvais mot de passe. Il vous reste $remaining tentative(s).")
                ]);
            }

            // 4. Successful login — clear rate limiting data
            RateLimiter::clearLoginAttempts($email);

            $User->updateLastConnection($userId);

            $mail = isset($userData['mail']) && is_string($userData['mail']) ? $userData['mail'] : $email;

            $jwt = JwtService::generate([
                'sub'  => $userId,
                'role' => $role,
                'mail' => $mail,
            ]);

            $_SESSION['jwt_token'] = $jwt;
            $_SESSION['user'] = [
                'id'     => $userId,
                'nom'    => $nom,
                'prenom' => $prenom,
                'mail'   => $mail,
                'role'   => $role,
            ];

            $User->saveJwtToken($userId, $jwt);
            $Logger->create($userId, 'CONNEXION', 'users', $userId, "Connexion de : $nom $prenom");

            header("Location: /");
            exit();

        } catch (ArrayException $exceptions) {
            $view = new LoginView($exceptions->getExceptions());
            echo $view->render();
        }
    }

    /**
     * Checks if this controller should handle the current request.
     *
     * @param string $chemin The requested URL path.
     * @param string $method The HTTP method.
     * @return bool True if path is '/user/login' and method is 'POST'.
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === "/user/login" && $method === "POST";
    }
}