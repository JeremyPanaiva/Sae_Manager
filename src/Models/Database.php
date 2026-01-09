<?php

namespace Models;

use Shared\Exceptions\DataBaseException;

/**
 * Database connection manager
 *
 * Provides a singleton database connection using mysqli.   Handles connection
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
     * Gets the database connection instance
     *
     * Creates a new connection if one doesn't exist (singleton pattern).
     * Sets character encoding to utf8mb4 for proper Unicode support.
     *
     * @return \mysqli The database connection
     * @throws DataBaseException If connection fails
     */
    public static function getConnection()
    {
        if (self::$conn === null) {
            $servername = self::parseEnvVar("DB_HOST");
            $username = self::parseEnvVar("DB_USER");
            $password = self::parseEnvVar("DB_PASSWORD");
            $dbname = self:: parseEnvVar("DB_NAME");

            try {
                // Enable strict error reporting - all errors become exceptions
                mysqli_report(MYSQLI_REPORT_STRICT);
                self::$conn = new \mysqli($servername, $username, $password, $dbname);
                self::$conn->set_charset('utf8mb4');
            } catch (\mysqli_sql_exception $e) {
                // Throw custom exception with user-friendly message
                throw new DataBaseException(
                    "Unable to connect to the database " .
                    "please contact sae-manager@alwaysdata. net"
                );
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
            $db = self::getConnection();
            if (!$db->ping()) {
                throw new DataBaseException(
                    "Unable to connect to the database " .
                    "please contact sae-manager@alwaysdata. net"
                );
            }
        } catch (\Exception $e) {
            throw new DataBaseException(
                "Unable to connect to the database " .
                "please contact sae-manager@alwaysdata. net"
            );
        }
    }
}
