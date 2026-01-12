<?php

namespace Controllers\Legal;

use Controllers\ControllerInterface;
use Views\Legal\SitemapView;

/**
 * Site map controller
 *
 * Handles the display of the site map page (plan du site).
 * This page provides an HTML overview of the website's structure and navigation,
 * helping users find content and improving SEO.
 *
 * @package Controllers\Legal
 */
class PlanDuSiteController implements ControllerInterface
{
    /**
     * Site map page route path
     *
     * @var string
     */
    public const PATH = '/plan-du-site';

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/plan-du-site' and method is GET
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }

    /**
     * Main controller method
     *
     * Creates and renders the site map page view.
     *
     * @return void
     */
    public function control(): void
    {
        $view = new SitemapView();
        echo $view->render();
    }
}
