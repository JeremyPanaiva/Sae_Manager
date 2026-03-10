<?php

namespace Shared;

/**
 * RateLimiter — Protection against abuse and request flooding.
 *
 * Uses sessions to limit the number of requests per action.
 * Also provides login-specific lockout management (per-email brute-force protection).
 *
 * @package Shared
 */
class RateLimiter
{
    /** @var int Maximum failed login attempts before lockout. */
    private const LOGIN_MAX_ATTEMPTS = 5;

    /** @var int Lockout duration in seconds (15 minutes). */
    private const LOGIN_LOCKOUT_DURATION = 900;

    /**
     * Checks if the current request exceeds the rate limit for a given action.
     *
     * @param string $action        Action name (e.g. 'contact_form').
     * @param int    $maxAttempts   Maximum number of attempts allowed.
     * @param int    $windowSeconds Time window in seconds.
     * @return bool True if the request is allowed, false if blocked.
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


    /**
     * Checks whether the given email is currently locked out.
     * Returns the remaining lockout seconds, or 0 if not locked out.
     *
     * @param string $email The user's email address.
     * @return int Remaining lockout seconds, or 0 if not locked out.
     */
    public static function getLockoutRemainingSeconds(string $email): int
    {
        $lockoutKey = self::lockoutKey($email);

        if (isset($_SESSION[$lockoutKey]) && is_numeric($_SESSION[$lockoutKey])) {
            $lockoutTime = (int) $_SESSION[$lockoutKey];
            if (time() < $lockoutTime) {
                return $lockoutTime - time();
            }
        }

        return 0;
    }

    /**
     * Records a failed login attempt for the given email.
     * Triggers a lockout if the maximum number of attempts is reached.
     *
     * @param string $email The user's email address.
     * @return int Number of attempts recorded so far.
     */
    public static function recordFailedLoginAttempt(string $email): int
    {
        $attemptsKey = self::attemptsKey($email);
        $lockoutKey  = self::lockoutKey($email);

        $current  = isset($_SESSION[$attemptsKey]) && is_numeric($_SESSION[$attemptsKey])
            ? (int) $_SESSION[$attemptsKey]
            : 0;

        $attempts = $current + 1;
        $_SESSION[$attemptsKey] = $attempts;

        if ($attempts >= self::LOGIN_MAX_ATTEMPTS) {
            $_SESSION[$lockoutKey] = time() + self::LOGIN_LOCKOUT_DURATION;
            unset($_SESSION[$attemptsKey]);
        }

        return $attempts;
    }

    /**
     * Returns the maximum number of allowed login attempts.
     *
     * @return int
     */
    public static function getLoginMaxAttempts(): int
    {
        return self::LOGIN_MAX_ATTEMPTS;
    }

    /**
     * Clears all login attempt and lockout data for the given email.
     * Should be called after a successful login.
     *
     * @param string $email The user's email address.
     * @return void
     */
    public static function clearLoginAttempts(string $email): void
    {
        unset($_SESSION[self::attemptsKey($email)], $_SESSION[self::lockoutKey($email)]);
    }


    /**
     * Returns the session key used to store failed attempt count for an email.
     *
     * @param string $email
     * @return string
     */
    private static function attemptsKey(string $email): string
    {
        return 'login_attempts_' . md5($email);
    }

    /**
     * Returns the session key used to store the lockout expiry timestamp for an email.
     *
     * @param string $email
     * @return string
     */
    private static function lockoutKey(string $email): string
    {
        return 'login_lockout_' . md5($email);
    }
}