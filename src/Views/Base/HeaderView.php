<?php

namespace Views\Base;

use Controllers\User\Login;
use Controllers\User\Logout;
use Controllers\User\ListUsers;
use Controllers\Dashboard\DashboardController; // 👈 pour le lien dashboard
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

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    private function getActiveClass(string $link): string {
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Si l'utilisateur n'est pas connecté, on ne met jamais "active"
        if (!isset($_SESSION['user']['nom'], $_SESSION['user']['prenom'], $_SESSION['user']['role'])) {
            return '';
        }

        return ($currentPath === $link) ? 'active' : '';
    }


    function templatePath(): string
    {
        return __DIR__ . '/header.html';
    }

    function templateKeys(): array
    {
        $username = 'Nom Prénom';
        $roleDisplay = '';
        $roleClass = 'inconnu';
        $link = Login::PATH;
        $connectionText = 'Se connecter';
        $usersLink = Login::PATH;
        $dashboardLink = Login::PATH; // 👈 Par défaut : redirige vers login si non connecté
        $saeLink = Login::PATH;; // Par défaut

        // Si utilisateur connecté
        if (isset($_SESSION['user']['nom'], $_SESSION['user']['prenom'], $_SESSION['user']['role'])) {
            $username = $_SESSION['user']['nom'] . ' ' . $_SESSION['user']['prenom'];
            $role = strtolower($_SESSION['user']['role']);
            $roleDisplay = ucfirst($role);
            $roleClass = $role;
            $link = Logout::PATH;
            $connectionText = 'Se déconnecter';
            $usersLink = ListUsers::PATH;
            $saeLink = '/sae'; // Tous les rôles peuvent accéder à SAE
            $dashboardLink = DashboardController::PATH; // 👈 vers tableau de bord
        }

        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'sae-manager.alwaysdata.net';
        $path = $currentPath ?: '/';
        $canonical = rtrim($scheme . '://' . $host . $path, '/');
        if ($canonical === '') {
            $canonical = $scheme . '://' . $host . '/';
        }

        return [
            self::USERNAME_KEY => $username,
            self::ROLE_KEY => $roleDisplay,
            'ROLE_CLASS' => $roleClass,
            self::LINK_KEY => $link,
            self::INSCRIPTION_LINK_KEY => '/user/register',
            self::CONNECTION_LINK_KEY => $connectionText,
            self::USERS_LINK_KEY => $usersLink,
            self::DASHBOARD_LINK_KEY => $dashboardLink,
            self::SAE_LINK_KEY => $saeLink,

            // Canonical URL pour la balise <link rel="canonical">
            'CANONICAL_URL' => $canonical,

            // Classes actives dynamiques
            'ACTIVE_DASHBOARD' => $this->getActiveClass($dashboardLink),
            'ACTIVE_SAE'       => $this->getActiveClass($saeLink),
            'ACTIVE_USERS'     => $this->getActiveClass($usersLink),
        ];

    }
}
