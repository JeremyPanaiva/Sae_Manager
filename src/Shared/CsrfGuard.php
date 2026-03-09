<?php

namespace Shared;

/**
 * CsrfGuard - Protection contre les attaques Cross-Site Request Forgery
 *
 * Génère et valide des tokens CSRF pour chaque formulaire POST.
 * Conforme à OWASP CSRF Prevention Cheat Sheet.
 */
class CsrfGuard
{
    /**
     * Génère un token CSRF et le stocke en session.
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
     * Retourne le champ HTML hidden à inclure dans chaque formulaire.
     */
    public static function getHiddenField(): string
    {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Valide le token CSRF soumis (durée max : 1h).
     */
    public static function validate(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $submittedToken = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $tokenTime = $_SESSION['csrf_token_time'] ?? 0;

        // Token expiré après 1 heure
        if (time() - (int)$tokenTime > 3600) {
            return false;
        }

        return is_string($submittedToken)
            && $submittedToken !== ''
            && hash_equals($sessionToken, $submittedToken);
    }
}

