<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\Database;

/**
 * User logout controller
 *
 * Handles user logout by logging the event, destroying the session,
 * and redirecting the user to the home page.
 *
 * @package Controllers\User
 */
class Logout implements ControllerInterface
{
    /**
     * Logout route path
     *
     * @var string
     */
    public const PATH = "/user/logout";

    /**
     * Main controller method
     *
     * Logs the logout action (including user name), destroys the user session,
     * and redirects to the home page.
     *
     * @return void
     */
    public function control(): void
    {
        // 1. AUDIT LOGGING: LOGOUT EVENT
        // Must be done BEFORE destroying the session to know who is logging out.
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Check if user is logged in and ID is valid before logging
            if (
                isset($_SESSION['user']) &&
                is_array($_SESSION['user']) &&
                isset($_SESSION['user']['id']) &&
                is_numeric($_SESSION['user']['id'])
            ) {
                $db = Database::getConnection();

                $userId = (int) $_SESSION['user']['id'];

                // Add Name and Surname to log details
                $nom = isset($_SESSION['user']['nom']) ? $_SESSION['user']['nom'] : '';
                $prenom = isset($_SESSION['user']['prenom']) ? $_SESSION['user']['prenom'] : '';

                $details = "Déconnexion de: $nom $prenom";

                $stmt = $db->prepare(
                    "INSERT INTO logs (user_id, action, table_concernee, element_id, details) 
                     VALUES (?, 'DECONNEXION', 'users', ?, ?)"
                );

                if ($stmt) {
                    $stmt->bind_param('iis', $userId, $userId, $details);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        } catch (\Throwable $e) {
            // Silently fail logging to ensure logout still happens
            error_log("Logout Audit Log Error: " . $e->getMessage());
        }

        // 2. Clear all session variables
        $_SESSION = [];

        // 3. Destroy the session
        session_destroy();

        // 4. Redirect to home page
        header("Location: /");
        exit();
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $chemin The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/user/logout' and method is GET
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === self::PATH && $method === "GET";
    }
}
