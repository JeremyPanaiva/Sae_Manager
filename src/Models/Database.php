<?php

namespace Models;

use Shared\Exceptions\DataBaseException;
use Models\User\Log;

/**
 * Class Database
 *
 * Singleton database connection manager using MySQLi.
 * * Features:
 * - Environment variable parsing (supports .env files).
 * - Singleton pattern to prevent multiple simultaneous connections.
 * - Context Injection: Automatically passes the current User ID, IP address,
 * and System Info to MySQL session variables (@current_user_id, etc.)
 * so that SQL Triggers can accurately populate the audit logs.
 *
 * @package Models
 */
class Database
{
    /**
     * Singleton instance of the database connection.
     *
     * @var \mysqli|null
     */
    private static $conn = null;

    /**
     * Retrieves the active database connection instance.
     *
     * Initializes the connection if it does not exist, and injects PHP
     * context (Session, IP, User Agent) into MySQL variables for triggers.
     *
     * @return \mysqli The active database connection object.
     * @throws DataBaseException If the connection fails to establish.
     */
    public static function getConnection(): \mysqli
    {
        // 1. SINGLETON INITIALIZATION
        if (self::$conn === null) {
            $hostRaw = self::parseEnvVar("DB_HOST");
            $userRaw = self::parseEnvVar("DB_USER");
            $passRaw = self::parseEnvVar("DB_PASSWORD");
            $dbRaw   = self::parseEnvVar("DB_NAME");

            // Type Sanitization for mysqli constructor
            $host = ($hostRaw === false) ? null : $hostRaw;
            $user = ($userRaw === false) ? null : $userRaw;
            $pass = ($passRaw === false) ? null : $passRaw;
            $db   = ($dbRaw   === false) ? null : $dbRaw;

            try {
                // Enable strict error reporting for MySQLi
                mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);
                self::$conn = new \mysqli($host, $user, $pass, $db);
                self::$conn->set_charset('utf8mb4');
            } catch (\mysqli_sql_exception $e) {
                throw new DataBaseException(
                    "Unable to connect to the database. " .
                    "Please contact sae-manager@alwaysdata.net for assistance."
                );
            }
        }

        // 2. CONTEXT INJECTION FOR SQL TRIGGERS
        // We inject PHP data into MySQL so triggers know WHO did WHAT and from WHERE.

        try {
            // A. Inject current User ID from Session
            if (session_status() === PHP_SESSION_ACTIVE) {
                $userIdToLog = null;

                if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['id'])) {
                    $userIdToLog = $_SESSION['user']['id'];
                } elseif (isset($_SESSION['user_id'])) {
                    $userIdToLog = $_SESSION['user_id'];
                }

                if ($userIdToLog !== null && is_scalar($userIdToLog)) {
                    $uid = (int) $userIdToLog;
                    self::$conn->query("SET @current_user_id = $uid");
                }
            }

            // B. Inject Client IP and System Information
            $ip = Log::getIpAddress();
            $systemInfo = Log::getSystemInfo();

            // Handle IP Address (Set to NULL if local/undetected to let triggers handle it)
            // '::1' is the IPv6 equivalent of 127.0.0.1
            if (empty($ip) || $ip === '0.0.0.0' || $ip === '127.0.0.1' || $ip === '::1') {
                self::$conn->query("SET @current_user_ip = NULL");
            } else {
                $safeIp = self::$conn->real_escape_string($ip);
                self::$conn->query("SET @current_user_ip = '$safeIp'");
            }

            // Handle System Information (Set to fallback text if unknown)
            if (empty($systemInfo) || strpos($systemInfo, 'Unknown OS') !== false) {
                self::$conn->query("SET @current_user_agent = 'Action interne DB'");
            } else {
                $safeSystemInfo = self::$conn->real_escape_string($systemInfo);
                self::$conn->query("SET @current_user_agent = '$safeSystemInfo'");
            }
        } catch (\Throwable $e) {
            // Failsafe: If injection fails, the site must not crash. We just log the error.
            error_log("Trigger Context Injection Failed: " . $e->getMessage());
        }

        return self::$conn;
    }

    /**
     * Parses an environment variable from system or .env file.
     *
     * Checks system environment variables first, then falls back to the .env file
     * if the variable is not set. Supports quoted values and ignores comments.
     *
     * @param string $envVar The environment variable name to retrieve.
     * @return string|false The variable value, or false if not found.
     */
    public static function parseEnvVar(string $envVar)
    {
        $val = getenv($envVar);
        if ($val !== false && $val !== '') {
            return $val;
        }

        $envPath = __DIR__ . '/../../.env';
        if (!file_exists($envPath)) {
            return $val;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return $val;
        }

        $env = [];
        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove surrounding quotes if present
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }
            $env[$key] = $value;
        }

        return $env[$envVar] ?? $val;
    }

    /**
     * Checks if the database connection is alive.
     *
     * Verifies the database connection is established and responsive.
     * Useful for health checks (e.g., API status endpoints).
     *
     * @throws DataBaseException If connection is not available or not responding.
     * @return void
     */
    public static function checkConnection(): void
    {
        try {
            $db = self::getConnection();
            if (!$db->ping()) {
                throw new DataBaseException(
                    "Unable to connect to the database, " .
                    "please contact sae-manager@alwaysdata.net"
                );
            }
        } catch (\Exception $e) {
            throw new DataBaseException(
                "Unable to connect to the database, " .
                "please contact sae-manager@alwaysdata.net"
            );
        }
    }
}
