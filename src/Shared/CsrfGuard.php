<?php

namespace Shared;

/**
 * CsrfGuard - Protection against Cross-Site Request Forgery attacks
 *
 * Generates and validates CSRF tokens for each POST form.
 * Compliant with OWASP CSRF Prevention Cheat Sheet.
 */
class CsrfGuard
{
    /**
     * Generates a CSRF token and stores it in the session.
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();

        return $token;
    }

    /**
     * Returns the hidden HTML field to include in each form.
     */
    public static function getHiddenField(): string
    {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validates the submitted CSRF token (max duration: 1 hour).
     */
    public static function validate(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $submittedToken = $_POST['csrf_token'] ?? '';
        $sessionToken = isset($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token'])
            ? $_SESSION['csrf_token']
            : '';
        $tokenTime = isset($_SESSION['csrf_token_time']) && is_int($_SESSION['csrf_token_time'])
            ? $_SESSION['csrf_token_time']
            : 0;

        if (time() - $tokenTime > 3600) {
            return false;
        }

        return is_string($submittedToken)
            && $submittedToken !== ''
            && hash_equals($sessionToken, $submittedToken);
    }
}
