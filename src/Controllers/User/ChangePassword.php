<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Views\User\ChangePasswordView;
use Shared\SessionGuard;

/**
 * Change password form controller
 *
 * Handles GET requests to display the password change form for authenticated users.
 * Verifies user authentication before displaying the form.  The form submission
 * is handled by ChangePasswordPost controller.
 *
 * @package Controllers\User
 */
class ChangePassword implements ControllerInterface
{
    /**
     * Change password page route path
     *
     * @var string
     */
    public const PATH = '/user/change-password';

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/user/change-password' and method is GET
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }

    /**
     * Main controller method
     *
     * Verifies user is authenticated and renders the password change form.
     * Redirects to login page if user is not authenticated.
     *
     * @return void
     */
    public function control(): void
    {
        SessionGuard::check();
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verify user is authenticated
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
            header('Location: /login');
            exit;
        }


        // Render password change form
        $view = new ChangePasswordView();
        echo $view->render();
    }
}
