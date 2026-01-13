<?php

namespace Views\Base;

use Controllers\Legal\MentionsLegalesController;
use Controllers\Legal\PlanDuSiteController;
use Views\AbstractView;

/**
 * Footer View
 *
 * Renders the application footer with legal links (site map and legal notices).
 * Included automatically in all views that extend BaseView.
 *
 * Provides navigation links to:
 * - Plan du site (Site Map)
 * - Mentions légales (Legal Notices)
 *
 * @package Views\Base
 */
class FooterView extends AbstractView
{
    /**
     * Template data key for the legal notices link path
     */
    public const LEGAL_LINK_KEY = 'LEGAL_LINK_KEY';

    /**
     * Template data key for the site map link path
     */
    public const PLAN_LINK_KEY = 'PLAN_LINK_KEY';

    /**
     * Constructor
     *
     * Initializes footer links from controller path constants
     */
    public function __construct()
    {
        $this->data[self::PLAN_LINK_KEY] = PlanDuSiteController::PATH;
        $this->data[self::LEGAL_LINK_KEY] = MentionsLegalesController::PATH;
    }

    /**
     * Returns the path to the footer template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return __DIR__ . '/footer.php';
    }
}
