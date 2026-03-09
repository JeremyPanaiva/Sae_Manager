<?php

namespace Shared;

use Models\User\Log;
use Models\User\User;

/**
 * SessionGuard
 *
 * Middleware-like guard that validates the JWT stored in the session on every
 * protected request. It also ensures session uniqueness by comparing the
 * current token with the one stored in the database (preventing concurrent logins).
 *
 * @package Shared
 */
class SessionGuard
{
    /**
     * Validates the current session JWT and checks for concurrent login.
     *
     * If valid and unique → does nothing.
     * If expired, invalid, or overridden by a newer login → destroys the session
     * and redirects the user.
     *
     * @param bool $redirectOnFail Whether to redirect on failure (default: true).
     * @return bool True if session is valid and unique, false otherwise.
     */
    public static function check(bool $redirectOnFail = true): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Basic session existence check
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
            if ($redirectOnFail) {
                header('Location: /user/login');
                exit;
            }
            return false;
        }

        $rawToken = $_SESSION['jwt_token'] ?? null;

        // 2. Token presence check
        if (!is_string($rawToken) || $rawToken === '') {
            self::expireSession($redirectOnFail);
            return false;
        }

        // 3. Cryptographic JWT validation (Signature & Expiry)
        $payload = JwtService::validate($rawToken);

        if ($payload === null) {
            self::expireSession($redirectOnFail);
            return false;
        }

        // 4. Concurrent Session Check (Single Device Enforcement)
        $rawId = $_SESSION['user']['id'] ?? 0;
        $userId = is_numeric($rawId) ? (int)$rawId : 0;

        if ($userId > 0) {
            $userModel = new User();
            $storedToken = $userModel->getStoredJwtToken($userId);

            /**
             * If the token in the database doesn't match the session token,
             * it means the user has logged in from a more recent device/browser.
             */
            if ($storedToken !== null && $storedToken !== $rawToken) {
                self::handleConcurrentLogout($userId, $redirectOnFail);
                return false;
            }
        }

        return true;
    }

    /**
     * Handles logout specifically when a concurrent session is detected.
     *
     * @param int $userId The user ID to log.
     * @param bool $redirect Whether to redirect to the login page.
     * @return void
     */
    private static function handleConcurrentLogout(int $userId, bool $redirect): void
    {
        $logger = new Log();
        $logger->create(
            $userId,
            'SESSION_CONCURRENTE',
            'users',
            $userId,
            "Session revoked: Connection detected on another device."
        );

        $_SESSION = [];
        session_destroy();

        if ($redirect) {
            header('Location: /user/login?error=concurrent_login');
            exit;
        }
    }

    /**
     * Destroys the session and redirects to login with an expiration notice.
     *
     * @param bool $redirect Whether to redirect after destroying the session.
     * @return void
     */
    private static function expireSession(bool $redirect): void
    {
        $userSession = $_SESSION['user'] ?? null;

        if (is_array($userSession)) {
            $rawId = $userSession['id'] ?? 0;
            $userId = is_numeric($rawId) ? (int)$rawId : 0;

            if ($userId > 0) {
                $nom = isset($userSession['nom']) && is_string($userSession['nom']) ? $userSession['nom'] : '';
                $prenom = isset($userSession['prenom']) &&
                is_string($userSession['prenom']) ? $userSession['prenom'] : '';

                $logger = new Log();
                $logger->create(
                    $userId,
                    'DECONNEXION',
                    'users',
                    $userId,
                    "Session expired for: $nom $prenom"
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
