<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\Log;
use Models\User\User;

/**
 * Class Logout
 *
 * Handles user disconnection.
 * Clears the JWT token from the database, ensures the logout event is recorded
 * in the audit logs before destroying the session and redirecting the user.
 *
 * @package Controllers\User
 */
class Logout implements ControllerInterface
{
    public const PATH = "/user/logout";

    /**
     * Executes the logout logic.
     *
     * 1. Checks if a user is currently logged in using strict type checks.
     * 2. Logs the 'DECONNEXION' event via the Log model.
     * 3. Invalidates the JWT token in the database.
     * 4. Destroys the PHP session.
     * 5. Redirects to the homepage.
     *
     * @return void
     */
    public function control(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Safe Session Access (Fixes: "Cannot access offset on mixed")
        // We extract the user array into a variable and verify it IS an array.
        $userSession = $_SESSION['user'] ?? null;

        if (is_array($userSession)) {
            // 2. Safe ID Extraction (Fixes: "Cannot cast mixed to int")
            $rawId = $userSession['id'] ?? 0;

            // Only proceed if we have a valid numeric ID
            if (is_numeric($rawId)) {
                $userId = (int)$rawId;

                // 3. Safe String Extraction (Fixes: "Part of encapsed string cannot be cast")
                $nomRaw = $userSession['nom'] ?? '';
                $nom = is_string($nomRaw) ? $nomRaw : '';

                $prenomRaw = $userSession['prenom'] ?? '';
                $prenom = is_string($prenomRaw) ? $prenomRaw : '';

                // Audit: Log disconnection
                $Logger = new Log();
                $Logger->create(
                    $userId,
                    'DECONNEXION',
                    'users',
                    $userId,
                    "Déconnexion de : $nom $prenom"
                );

                // 4. Invalidate JWT token in database
                $userModel = new User();
                $userModel->saveJwtToken($userId, '');
            }
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
