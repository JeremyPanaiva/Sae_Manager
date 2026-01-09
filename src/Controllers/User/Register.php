<?php

namespace Controllers\User;

use Controllers\ControllerInterface;

/**
 * User registration form controller
 *
 * Handles GET requests to display the user registration form.
 * Allows new users to create an account by providing their information.
 *
 * @package Controllers\User
 */
class Register implements ControllerInterface
{
    /**
     * Main controller method
     *
     * Creates and renders the registration form view.
     *
     * @return void
     */
    public function control()
    {
        $view = new \Views\User\RegisterView();
        echo $view->render();
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $chemin The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/user/register' and method is GET
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === "/user/register" && $method === "GET";
    }
}
