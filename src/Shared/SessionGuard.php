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
        $userData = $_SESSION['user'];
        $rawId = $userData['id'] ?? 0;
        $userId = is_numeric($rawId) ? (int)$rawId : 0;

        if ($userId > 0) {
            $userModel = new User();
            $storedToken = $userModel->getStoredJwtToken($userId);

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
        $userData = $_SESSION['user'] ?? [];
        $userArray = is_array($userData) ? $userData : [];

        // Fix: Use is_string check to satisfy "Cannot cast mixed to string"
        $rawNom = $userArray['nom'] ?? '';
        $nom = is_string($rawNom) ? $rawNom : '';

        $rawPrenom = $userArray['prenom'] ?? '';
        $prenom = is_string($rawPrenom) ? $rawPrenom : 'Utilisateur';

        $logger = new Log();
        $logger->create(
            $userId,
            'SESSION_CONCURRENTE',
            'users',
            $userId,
            "Déconnexion : " . $nom . " " . $prenom . " (Connexion détectée sur un autre appareil)"
        );

        self::clearSession();

        if ($redirect) {
            header('Location: /user/login?error=concurrent_login');
            exit;
        }
    }

    /**
     * Destroys the session and logs the expiration event.
     *
     * @param bool $redirect Whether to redirect after destroying the session.
     * @return void
     */
    private static function expireSession(bool $redirect): void
    {
        $userData = $_SESSION['user'] ?? null;

        if (is_array($userData)) {
            $rawId = $userData['id'] ?? 0;
            $userId = is_numeric($rawId) ? (int)$rawId : 0;

            $rawNom = $userData['nom'] ?? '';
            $nom = is_string($rawNom) ? $rawNom : '';

            $rawPrenom = $userData['prenom'] ?? '';
            $prenom = is_string($rawPrenom) ? $rawPrenom : 'Utilisateur';

            if ($userId > 0) {
                $logger = new Log();
                $logger->create(
                    $userId,
                    'SESSION_EXPIREE',
                    'users',
                    $userId,
                    "Système : Session de " . $nom . " " . $prenom . " expirée automatiquement."
                );
            }
        }

        self::clearSession();

        if ($redirect) {
            header('Location: /user/login?expired=1');
            exit;
        }
    }

    /**
     * Properly clears and destroys the session.
     *
     * @return void
     */
    private static function clearSession(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
