<?php

namespace Controllers\Legal;

use Controllers\ControllerInterface;
use Views\Legal\LegalNoticeView;

/**
 * Legal notice controller
 *
 * Handles the display of the legal notice page (mentions légales).
 * This page typically contains legal information about the website,
 * its owner, hosting provider, and applicable regulations.
 *
 * @package Controllers\Legal
 */
class MentionsLegalesController implements ControllerInterface
{
    /**
     * Legal notice page route path
     *
     * @var string
     */
    public const PATH = '/mentions-legales';

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/mentions-legales' and method is GET
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }

    /**
     * Main controller method
     *
     * Creates and renders the legal notice page view.
     *
     * @return void
     */
    public function control(): void
    {
        $view = new LegalNoticeView();
        echo $view->render();
    }
}
