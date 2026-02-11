<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\Log;

/**
 * Class Logout
 *
 * Handles user disconnection.
 * It ensures the logout event is recorded in the audit logs before
 * destroying the session and redirecting the user.
 *
 * @package Controllers\User
 */
class Logout implements ControllerInterface
{
    public const PATH = "/user/logout";

    /**
     * Executes the logout logic.
     *
     * 1. Checks if a user is currently logged in.
     * 2. Logs the 'DECONNEXION' event via the Log model.
     * 3. Destroys the PHP session.
     * 4. Redirects to the homepage.
     *
     * @return void
     */
    public function control(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Only log if a user was actually connected
        if (isset($_SESSION['user']['id'])) {
            $Logger = new Log();

            $userId = (int) $_SESSION['user']['id'];
            $nom = $_SESSION['user']['nom'] ?? '';
            $prenom = $_SESSION['user']['prenom'] ?? '';

            // Audit: Log disconnection
            $Logger->create($userId, 'DECONNEXION', 'users', $userId, "Logout: $nom $prenom");
        }

        // Destroy Session
        $_SESSION = [];
        session_destroy();

        // Redirect
        header("Location: /");
        exit();
    }

    /**
     * Router Support Check
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === self::PATH && $method === "GET";
    }
}
