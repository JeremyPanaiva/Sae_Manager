<?php

namespace Models;

use Shared\Exceptions\DataBaseException;

/**
 * Database connection manager
 *
 * Provides a singleton database connection using mysqli.  Handles connection
 * initialization, configuration loading from environment variables or . env file,
 * and connection health checks.
 *
 * Configuration is loaded from environment variables or a .env file in the following order:
 * 1. System environment variables (getenv)
 * 2. .env file in project root
 *
 * Required environment variables:
 * - DB_HOST: Database host
 * - DB_USER: Database username
 * - DB_PASSWORD:  Database password
 * - DB_NAME: Database name
 *
 * @package Models
 */
class Database
{
    /**
     * Singleton database connection instance
     *
     * @var \mysqli|null
     */
    private static $conn = null;

    /**
     * Retrieves the singleton database connection instance.
     *
     * This method initializes the MySQLi connection if it doesn't exist (Lazy Loading).
     * It enforces the 'utf8mb4' character set for full Unicode support.
     *
     * Feature: Logging Context Injection
     * Every time this method is called, it checks if a user is logged in.
     * If so, it injects the user ID into a MySQL session variable (@current_user_id).
     * This allows SQL triggers to automatically log the 'user_id' responsible for changes.
     *
     * @return \mysqli The active database connection object.
     * @throws DataBaseException If the connection fails to establish.
     */
    public static function getConnection(): \mysqli
    {
        // 1. Singleton Pattern: Initialize connection only if it doesn't exist
        if (self::$conn === null) {

            // Retrieve credentials (parseEnvVar returns string|false)
            $hostRaw = self::parseEnvVar("DB_HOST");
            $userRaw = self::parseEnvVar("DB_USER");
            $passRaw = self::parseEnvVar("DB_PASSWORD");
            $dbRaw   = self::parseEnvVar("DB_NAME");

            // Type Sanitization:
            // mysqli constructor expects ?string (string or null), but parseEnvVar returns false on failure.
            // We explicitly convert 'false' to 'null' to avoid type errors.
            $host = ($hostRaw === false) ? null : $hostRaw;
            $user = ($userRaw === false) ? null : $userRaw;
            $pass = ($passRaw === false) ? null : $passRaw;
            $db   = ($dbRaw   === false) ? null : $dbRaw;

            try {
                // Enable strict error reporting: MySQL errors will throw exceptions
                mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

                // Initialize the MySQLi connection
                self::$conn = new \mysqli($host, $user, $pass, $db);
                self::$conn->set_charset('utf8mb4');

            } catch (\mysqli_sql_exception $e) {
                // Wrap native MySQL exception into a custom application exception
                throw new DataBaseException(
                    "Unable to connect to the database. " .
                    "Please contact sae-manager@alwaysdata.net for assistance."
                );
            }
        }

        // 2. Context Injection for Audit Logs (CRITICAL FIX)
        // This block is now OUTSIDE the singleton check.
        // It runs every time getConnection() is called, ensuring the DB always knows
        // who is currently performing the action, even if the connection was already open.
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            $sessionVal = $_SESSION['user_id'];

            // PHPStan safety check: ensure value is scalar (int/string) before casting
            if (is_scalar($sessionVal)) {
                $userId = (int) $sessionVal;
                // Update the SQL variable '@current_user_id' for the current request
                self::$conn->query("SET @current_user_id = $userId");
            }
        }

        return self::$conn;
    }

    /**
     * Parses an environment variable from system or .env file
     *
     * Checks system environment variables first, then falls back to .env file
     * if the variable is not set.  Supports quoted values and ignores comments.
     *
     * @param string $envVar The environment variable name to retrieve
     * @return string|false The variable value, or false if not found
     */
    public static function parseEnvVar(string $envVar)
    {
        // Try system environment variable first
        $val = getenv($envVar);
        if ($val !== false && $val !== '') {
            return $val;
        }

        // Try .env file
        $envPath = __DIR__ . '/../../.env';
        if (! file_exists($envPath)) {
            return $val;
        }

        // Parse .env file
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Check if file() succeeded
        if ($lines === false) {
            return $val;
        }

        $env = [];
        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }

            // Parse key=value pairs
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes from values
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
     * Checks if the database connection is alive
     *
     * Verifies the database connection is established and responsive.
     * Useful for health checks and connection validation.
     *
     * @throws DataBaseException If connection is not available or not responding
     */
    public static function checkConnection(): void
    {
        try {
            $db = self:: getConnection();
            if (!$db->ping()) {
                throw new DataBaseException(
                    "Unable to connect to the database " .
                    "please contact sae-manager@alwaysdata.net"
                );
            }
        } catch (\Exception $e) {
            throw new DataBaseException(
                "Unable to connect to the database " .
                "please contact sae-manager@alwaysdata.net"
            );
        }
    }
}
