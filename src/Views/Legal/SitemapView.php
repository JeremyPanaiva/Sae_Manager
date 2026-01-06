<?php

namespace Views\Legal;

use Views\Base\BaseView;

/**
 * Sitemap View
 *
 * Renders the sitemap page (Plan du Site).
 * Displays a hierarchical structure of all accessible pages in the application,
 * helping users navigate and understand the site organization.
 *
 * The sitemap typically includes:
 * - Main navigation pages
 * - User account pages
 * - Legal pages
 * - Other accessible routes
 *
 * @package Views\Legal
 */
class SitemapView extends BaseView
{
    /**
     * Path to the sitemap template file
     */
    private const TEMPLATE_HTML = __DIR__ . '/sitemap.php';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns the path to the sitemap template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return self::TEMPLATE_HTML;
    }
}