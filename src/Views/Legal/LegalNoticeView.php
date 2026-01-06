<?php

namespace Views\Legal;

use Views\Base\BaseView;

/**
 * Legal Notice View
 *
 * Renders the legal notices page (Mentions Légales).
 * Displays legal information about the website, including:
 * - Site publisher information
 * - Hosting provider details
 * - Legal disclaimers and terms
 * - Privacy and data protection notices
 *
 * @package Views\Legal
 */
class LegalNoticeView extends BaseView
{
    /**
     * Path to the legal notice template file
     */
    private const TEMPLATE_HTML = __DIR__ . '/legal-notice.php';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns the path to the legal notice template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return self::TEMPLATE_HTML;
    }
}