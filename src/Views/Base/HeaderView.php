<?php

namespace Views\Base;

use Controllers\User\Login;
use Controllers\User\Logout;
use Controllers\User\ListUsers;
use Controllers\Dashboard\DashboardController;
use Views\AbstractView;

/**
 * Header View
 *
 * Renders the application header with dynamic content based on user authentication state.
 * Handles navigation visibility, user information display, and active menu highlighting.
 *
 * Behavior:
 * - Logged out:  Hides navigation menu and user info, shows login/register links
 * - Logged in: Shows navigation menu, user info with role badge, and logout link
 *
 * Also generates the canonical URL for SEO purposes based on the current request.
 *
 * @package Views\Base
 */
class HeaderView extends AbstractView
{
    /**
     * Template data key for username display
     */
    public const USERNAME_KEY = 'USERNAME_KEY';

    /**
     * Template data key for login/logout link URL
     */
    public const LINK_KEY = 'LINK_KEY';

    /**
     * Template data key for registration link URL
     */
    public const INSCRIPTION_LINK_KEY = 'INSCRIPTION_LINK_KEY';

    /**
     * Template data key for login/logout link text
     */
    public const CONNECTION_LINK_KEY = 'CONNECTION_LINK_KEY';

    /**
     * Template data key for users page link URL
     */
    public const USERS_LINK_KEY = 'USERS_LINK_KEY';

    /**
     * Template data key for user role display text
     */
    public const ROLE_KEY = 'ROLE_KEY';

    /**
     * Template data key for dashboard link URL
     */
    public const DASHBOARD_LINK_KEY = 'DASHBOARD_LINK_KEY';

    /**
     * Template data key for SAE page link URL
     */
    public const SAE_LINK_KEY = 'SAE_LINK_KEY';

    /**
     * Template data key for navigation menu inline style
     */
    public const NAV_STYLE_KEY = 'NAV_STYLE';

    /**
     * Template data key for user metadata section inline style
     */
    public const USER_META_STYLE_KEY = 'USER_META_STYLE';

    /**
     * Constructor
     *
     * Initializes header data based on user session state.  Sets up navigation links,
     * user information, visibility styles, and canonical URL for the current page.
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $username = 'Nom Prénom';
        $roleDisplay = '';
        $roleClass = 'inconnu';
        $link = Login::PATH;
        $connectionText = 'Se connecter';
        $usersLink = Login::PATH;
        $dashboardLink = Login::PATH;
        $saeLink = Login::PATH;

        $navStyle = 'display:none;';
        $userMetaStyle = 'display: none;';

        if (isset($_SESSION['user']['nom'], $_SESSION['user']['prenom'], $_SESSION['user']['role'])) {
            $role = strtolower($_SESSION['user']['role']);
            $username = $_SESSION['user']['nom'] . ' ' .  $_SESSION['user']['prenom'];
            $roleDisplay = ucfirst($role);
            $roleClass = $role;

            $link = Logout::PATH;
            $connectionText = 'Se déconnecter';
            $usersLink = ListUsers::PATH;
            $saeLink = '/sae';
            $dashboardLink = DashboardController:: PATH;

            $navStyle = '';
            $userMetaStyle = '';
            $profileBtnStyle = '';
        } else {
            $profileBtnStyle = 'display:none;';
        }

        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'sae-manager.alwaysdata.net';
        $path = $currentPath ?: '/';
        $canonical = rtrim($scheme . '://' . $host . $path, '/');
        if ($canonical === '') {
            $canonical = $scheme . '://' . $host . '/';
        }

        $inscriptionLink = '/user/register';
        $inscriptionStyle = '';
        if (isset($_SESSION['user']['nom'], $_SESSION['user']['prenom'], $_SESSION['user']['role'])) {
            $inscriptionLink = '';
            $inscriptionStyle = 'display:none;';
        }

        $this->data[self::USERNAME_KEY] = $username;
        $this->data[self::ROLE_KEY] = $roleDisplay;
        $this->data['ROLE_CLASS'] = $roleClass;
        $this->data[self::LINK_KEY] = $link;
        $this->data[self:: CONNECTION_LINK_KEY] = $connectionText;
        $this->data[self::INSCRIPTION_LINK_KEY] = $inscriptionLink;
        $this->data['INSCRIPTION_STYLE_KEY'] = $inscriptionStyle;
        $this->data[self::USERS_LINK_KEY] = $usersLink;
        $this->data[self:: DASHBOARD_LINK_KEY] = $dashboardLink;
        $this->data[self::SAE_LINK_KEY] = $saeLink;
        $this->data['CANONICAL_URL'] = $canonical;

        $this->data[self::NAV_STYLE_KEY] = $navStyle;
        $this->data[self::USER_META_STYLE_KEY] = $userMetaStyle;
        $this->data['PROFILE_BTN_STYLE'] = $profileBtnStyle;

        $this->data['ACTIVE_DASHBOARD'] = $this->getActiveClass($dashboardLink);
        $this->data['ACTIVE_SAE'] = $this->getActiveClass($saeLink);
        $this->data['ACTIVE_USERS'] = $this->getActiveClass($usersLink);
    }

    /**
     * Returns the path to the header template file
     *
     * @return string Absolute path to the template file
     */
    function templatePath(): string
    {
        return __DIR__ . '/header.php';
    }

    /**
     * Determines if a navigation link should have the 'active' CSS class
     *
     * Compares the current page path with the link path.  Only returns 'active'
     * if the user is logged in and the paths match.
     *
     * @param string $link The navigation link path to check
     * @return string 'active' if the link matches current page, empty string otherwise
     */
    private function getActiveClass(string $link): string
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (!isset($_SESSION['user']['nom'], $_SESSION['user']['prenom'], $_SESSION['user']['role'])) {
            return '';
        }
        return ($currentPath === $link) ? 'active' : '';
    }
}