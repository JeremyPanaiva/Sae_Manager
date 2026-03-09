<?php

namespace Shared;

/**
 * RateLimiter - Protection against abuse and request flooding.
 * Uses sessions to limit the number of requests per IP.
 */
class RateLimiter
{
    /**
     * Checks if the current IP exceeds the request limit.
     *
     * @param string $action   Action name (e.g. 'contact_form')
     * @param int    $maxAttempts Maximum number of attempts
     * @param int    $windowSeconds Time window in seconds
     * @return bool True if allowed, false if blocked
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

        $_SESSION[$key] = array_filter(
            $_SESSION[$key],
            fn($timestamp) => is_int($timestamp) && ($now - $timestamp) < $windowSeconds
        );

        if (count($_SESSION[$key]) >= $maxAttempts) {
            return false;
        }

        $_SESSION[$key][] = $now;
        return true;
    }
}
