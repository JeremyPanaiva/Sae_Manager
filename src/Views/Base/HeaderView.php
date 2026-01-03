<?php

namespace Views\Base;

use Controllers\User\Login;
use Controllers\User\Logout;
use Controllers\User\ListUsers;
use Controllers\Dashboard\DashboardController;
use Views\AbstractView;

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

        // Déconnecté: cacher le menu et le bloc nom/prénom
        $navStyle = 'display:none;';
        $userMetaStyle = 'display:none;';

        if (isset($_SESSION['user']['nom'], $_SESSION['user']['prenom'], $_SESSION['user']['role'])) {
            $role = strtolower($_SESSION['user']['role']);
            $username = $_SESSION['user']['nom'] . ' ' . $_SESSION['user']['prenom'];
            $roleDisplay = ucfirst($role);
            $roleClass = $role;

            $link = Logout::PATH;
            $connectionText = 'Se déconnecter';
            $usersLink = ListUsers::PATH;
            $saeLink = '/sae';
            $dashboardLink = DashboardController::PATH;

            // Connecté: afficher le menu et le bloc nom/prénom
            $navStyle = '';
            $userMetaStyle = '';
        }

        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
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
        $this->data[self::CONNECTION_LINK_KEY] = $connectionText;
        $this->data[self::INSCRIPTION_LINK_KEY] = $inscriptionLink;
        $this->data['INSCRIPTION_STYLE_KEY'] = $inscriptionStyle;
        $this->data[self::USERS_LINK_KEY] = $usersLink;
        $this->data[self::DASHBOARD_LINK_KEY] = $dashboardLink;
        $this->data[self::SAE_LINK_KEY] = $saeLink;
        $this->data['CANONICAL_URL'] = $canonical;

        $this->data[self::NAV_STYLE_KEY] = $navStyle;
        $this->data[self::USER_META_STYLE_KEY] = $userMetaStyle;

        $this->data['ACTIVE_DASHBOARD'] = $this->getActiveClass($dashboardLink);
        $this->data['ACTIVE_SAE'] = $this->getActiveClass($saeLink);
        $this->data['ACTIVE_USERS'] = $this->getActiveClass($usersLink);
    }

    function templatePath(): string
    {
        return __DIR__ . '/header.php';
    }

    private function getActiveClass(string $link): string
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (!isset($_SESSION['user']['nom'], $_SESSION['user']['prenom'], $_SESSION['user']['role'])) {
            return '';
        }
        return ($currentPath === $link) ? 'active' : '';
    }

}