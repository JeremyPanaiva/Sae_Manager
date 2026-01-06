<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Views\Home\HomeView;

/**
 * User logout controller
 *
 * Handles user logout by destroying the session and redirecting to the home page.
 * Clears all session data to ensure the user is fully logged out.
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
     * Destroys the user session and redirects to the home page.
     * Note: The view rendering after header redirect is unreachable code
     * and could be removed.
     *
     * @return void
     */
    public function control(): void
    {
        // Clear all session variables
        $_SESSION = [];

        // Destroy the session
        session_destroy();

        // Redirect to home page
        header("Location: /");

        // Note: This code is unreachable due to the header redirect above
        // Consider removing if not needed
        $view = new HomeView();
        echo $view->render();
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