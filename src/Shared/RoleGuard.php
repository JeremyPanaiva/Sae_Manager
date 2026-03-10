<?php

namespace Shared;

/**
 * RoleGuard — Centralised role-based access control.
 *
 * Checks whether the currently authenticated session user has the required role.
 * On failure, sends an HTTP redirect or a 403 response and terminates execution.
 *
 * @package Shared
 */
class RoleGuard
{
    /**
     * Asserts that the session user's role matches the expected role.
     * Redirects to the given URL and exits if the check fails.
     *
     * @param string $requiredRole Expected role (e.g. 'client', 'responsable', 'etudiant').
     * @param string $redirectUrl  URL to redirect to on failure (default: '/login').
     * @return void
     */
    public static function requireRole(string $requiredRole, string $redirectUrl = '/login'): void
    {
        if (
            !isset($_SESSION['user']) ||
            !is_array($_SESSION['user']) ||
            !isset($_SESSION['user']['role']) ||
            !is_string($_SESSION['user']['role']) ||
            strtolower($_SESSION['user']['role']) !== strtolower($requiredRole)
        ) {
            header('Location: ' . $redirectUrl);
            exit();
        }
    }

    /**
     * Asserts that the session user's role matches the expected role.
     * Sends HTTP 403 Forbidden and exits if the check fails.
     *
     * @param string $requiredRole Expected role (e.g. 'client', 'responsable').
     * @return void
     */
    public static function requireRoleOrForbid(string $requiredRole): void
    {
        if (
            !isset($_SESSION['user']) ||
            !is_array($_SESSION['user']) ||
            !isset($_SESSION['user']['role']) ||
            !is_string($_SESSION['user']['role']) ||
            strtolower($_SESSION['user']['role']) !== strtolower($requiredRole)
        ) {
            header('HTTP/1.1 403 Forbidden');
            echo 'Accès refusé';
            exit();
        }
    }

    /**
     * Returns the current session user's role, or an empty string if not set.
     *
     * @return string
     */
    public static function getCurrentRole(): string
    {
        if (
            isset($_SESSION['user']) &&
            is_array($_SESSION['user']) &&
            isset($_SESSION['user']['role']) &&
            is_string($_SESSION['user']['role'])
        ) {
            return strtolower($_SESSION['user']['role']);
        }
        return '';
    }
}