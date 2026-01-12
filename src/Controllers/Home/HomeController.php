<?php

namespace Controllers\Home;

use Controllers\ControllerInterface;
use Models\User\User;
use Views\Home\HomeView;

/**
 * Home controller
 *
 * Handles the display of the application's home page.
 * This is the landing page accessible at the root path.
 *
 * @package Controllers\Home
 */
class HomeController implements ControllerInterface
{
    /**
     * Main controller method
     *
     * Creates and renders the home page view.
     *
     * @return void
     */
    public function control()
    {
        $view = new HomeView();
        echo $view->render();
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $chemin The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is root "/" and method is GET
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === "/" && $method === "GET";
    }
}
