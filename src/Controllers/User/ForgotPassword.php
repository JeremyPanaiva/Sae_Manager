<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Views\User\ForgotPasswordView;

/**
 * Forgot password form controller
 *
 * Handles GET requests to display the forgot password form.  Allows users to
 * request a password reset by entering their email address.  The form submission
 * is handled by ForgotPasswordPost controller.
 *
 * @package Controllers\User
 */
class ForgotPassword implements ControllerInterface
{
    /**
     * Forgot password page route path
     *
     * @var string
     */
    public const PATH = "/user/forgot-password";

    /**
     * Main controller method
     *
     * Creates and renders the forgot password form view.
     *
     * @return void
     */
    public function control()
    {
        $view = new ForgotPasswordView();
        echo $view->render();
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * Supports both standard path and legacy query parameter format.
     *
     * @param string $chemin The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path matches forgot password route and method is GET
     */
    static function support(string $chemin, string $method): bool
    {
        return ($chemin === self::PATH ||
                (isset($_GET['page']) && $_GET['page'] === 'forgot-password'))
            && $method === "GET";
    }
}