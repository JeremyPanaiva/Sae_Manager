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
 *
 * @package Views\Base
 */
class HeaderView extends AbstractView
{
    public const USERNAME_KEY = 'USERNAME_KEY';
    public const LINK_KEY = 'LINK_KEY';
    public const INSCRIPTION_LINK_KEY = 'INSCRIPTION_LINK_KEY';
    public const CONNECTION_LINK_KEY = 'CONNECTION_LINK_KEY';
    public const USERS_LINK_KEY = 'USERS_LINK_KEY';
    public const ROLE_KEY = 'ROLE_KEY';
    public const DASHBOARD_LINK_KEY = 'DASHBOARD_LINK_KEY';
    public const SAE_LINK_KEY = 'SAE_LINK_KEY';
    public const NAV_STYLE_KEY = 'NAV_STYLE';
    public const USER_META_STYLE_KEY = 'USER_META_STYLE';

    /**
     * Constructor
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
        $saeLink = Login:: PATH;

        $navStyle = 'display:none;';
        $userMetaStyle = 'display: none;';
        $profileBtnStyle = 'display:none;';

        if (
            isset($_SESSION['user']) &&
            is_array($_SESSION['user']) &&
            isset($_SESSION['user']['nom']) &&
            isset($_SESSION['user']['prenom']) &&
            isset($_SESSION['user']['role'])
        ) {
            $nom = is_string($_SESSION['user']['nom']) ? $_SESSION['user']['nom'] :  '';
            $prenom = is_string($_SESSION['user']['prenom']) ? $_SESSION['user']['prenom'] : '';
            $roleRaw = is_string($_SESSION['user']['role']) ? $_SESSION['user']['role'] : '';
            $role = strtolower($roleRaw);

            $username = $nom . ' ' . $prenom;
            $roleDisplay = ucfirst($role);
            $roleClass = $role;

            $link = Logout::PATH;
            $connectionText = 'Se déconnecter';
            $usersLink = ListUsers::PATH;
            $saeLink = '/sae';
            $dashboardLink = DashboardController::PATH;

            $navStyle = '';
            $userMetaStyle = '';
            $profileBtnStyle = '';
        }

        $requestUriRaw = $_SERVER['REQUEST_URI'] ?? '/';
        $requestUri = is_string($requestUriRaw) ? $requestUriRaw : '/';
        $parsedUrl = parse_url($requestUri);
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $hostRaw = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $host = is_string($hostRaw) ? $hostRaw : 'localhost';

        if (isset($parsedUrl['path'])) {
            $canonicalUrl = $scheme . $host .  $parsedUrl['path'];
        } else {
            $canonicalUrl = $scheme . $host . '/';
        }

        $inscriptionStyle = '';

        if (
            isset($_SESSION['user']) &&
            is_array($_SESSION['user']) &&
            isset($_SESSION['user']['nom']) &&
            isset($_SESSION['user']['prenom']) &&
            isset($_SESSION['user']['role'])
        ) {
            $inscriptionStyle = 'display: none;';
        }

        $this->data = [
            'USERNAME_KEY' => $username,
            'ROLE_KEY' => $roleDisplay,
            'ROLE_CLASS' => $roleClass,
            'LINK_KEY' => $link,
            'CONNECTION_LINK_KEY' => $connectionText,
            'INSCRIPTION_LINK_KEY' => '/user/register',
            'INSCRIPTION_STYLE_KEY' => $inscriptionStyle,
            'USERS_LINK_KEY' => $usersLink,
            'DASHBOARD_LINK_KEY' => $dashboardLink,
            'SAE_LINK_KEY' => $saeLink,
            'NAV_STYLE' => $navStyle,
            'USER_META_STYLE' => $userMetaStyle,
            'PROFILE_BTN_STYLE' => $profileBtnStyle,
            'CANONICAL_URL' => $canonicalUrl,
        ];
    }

    /**
     * Returns the path to the header template file
     *
     * @return string
     */
    public function templatePath(): string
    {
        return __DIR__ . '/header.php';
    }
}