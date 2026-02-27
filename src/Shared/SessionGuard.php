<?php

namespace Shared;

use Models\User\Log;

/**
 * SessionGuard
 *
 * Middleware-like guard that validates the JWT stored in the session on every
 * protected request. Automatically logs out and redirects if the token is
 * expired or invalid.
 *
 * Usage (add at the top of every protected controller):
 *   SessionGuard::check();
 *
 * @package Shared
 */
class SessionGuard
{
    /**
     * Validates the current session JWT.
     *
     * If valid → does nothing (request continues normally).
     * If expired or invalid → destroys the session and redirects to /user/login?expired=1.
     *
     * @param bool $redirectOnFail Whether to redirect on failure (default: true).
     *                             Set to false to only return the result without redirecting.
     * @return bool True if session is valid, false otherwise.
     */
    public static function check(bool $redirectOnFail = true): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // No active session → not authenticated (but not expired either)
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
            if ($redirectOnFail) {
                header('Location: /user/login');
                exit;
            }
            return false;
        }

        // Extract JWT from session
        $rawToken = $_SESSION['jwt_token'] ?? null;

        // No JWT in session → legacy session without token, force re-login
        if (!is_string($rawToken) || $rawToken === '') {
            self::expireSession($redirectOnFail);
            return false;
        }

        // Validate JWT
        $payload = JwtService::validate($rawToken);

        if ($payload === null) {
            // Token expired or tampered with → auto logout
            self::expireSession($redirectOnFail);
            return false;
        }

        return true;
    }

    /**
     * Destroys the session and redirects to login with an expiration notice.
     *
     * @param bool $redirect Whether to redirect after destroying the session.
     * @return void
     */
    private static function expireSession(bool $redirect): void
    {
        // Log the automatic disconnection if user data is available
        $userSession = $_SESSION['user'] ?? null;
        if (is_array($userSession)) {
            $rawId = $userSession['id'] ?? 0;
            $userId = is_numeric($rawId) ? (int)$rawId : 0;

            if ($userId > 0) {
                $logger = new Log();
                $logger->create(
                    $userId,
                    'DECONNEXION',
                    'users',
                    $userId,
                    'Session expirée automatiquement après 1 heure d\'inactivité'
                );
            }
        }

        $_SESSION = [];
        session_destroy();

        if ($redirect) {
            header('Location: /user/login?expired=1');
            exit;
        }
    }
}
