<?php

namespace Shared;

/**
 * RateLimiter - Protection contre les abus et le flooding.
 * Utilise les sessions pour limiter le nombre de requêtes par IP.
 */
class RateLimiter
{
    /**
     * Vérifie si l'IP actuelle dépasse la limite de requêtes.
     *
     * @param string $action   Nom de l'action (ex: 'contact_form')
     * @param int    $maxAttempts Nombre max de tentatives
     * @param int    $windowSeconds Fenêtre de temps en secondes
     * @return bool True si autorisé, false si bloqué
     */
    public static function check(string $action, int $maxAttempts = 10, int $windowSeconds = 60): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $key = 'rate_limit_' . $action;
        $now = time();

        if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }

        // Nettoyer les anciennes entrées
        $_SESSION[$key] = array_filter(
            $_SESSION[$key],
            fn($timestamp) => ($now - $timestamp) < $windowSeconds
        );

        if (count($_SESSION[$key]) >= $maxAttempts) {
            return false; // Bloqué
        }

        $_SESSION[$key][] = $now;
        return true; // Autorisé
    }
}

