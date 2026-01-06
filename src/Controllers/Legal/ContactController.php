<?php

namespace Controllers\Legal;

use Controllers\ControllerInterface;
use Views\Legal\ContactView;

/**
 * Contact controller
 *
 * Handles the display of the contact page where users can find contact information
 * or submit contact forms.
 *
 * @package Controllers\Legal
 */
class ContactController implements ControllerInterface
{
    /**
     * Contact page route path
     *
     * @var string
     */
    public const PATH = '/contact';

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/contact' and method is GET
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self:: PATH && $method === 'GET';
    }

    /**
     * Main controller method
     *
     * Creates and renders the contact page view.
     *
     * @return void
     */
    public function control(): void
    {
        $view = new ContactView();
        echo $view->render();
    }
}