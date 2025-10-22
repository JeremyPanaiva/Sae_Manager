<?php
namespace Models;

class Database {
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn === null) {

            $servername = self::parseEnvVar("DB_HOST");
            $username = self::parseEnvVar("DB_USER");
            $password = self::parseEnvVar("DB_PASSWORD");
            $dbname = self::parseEnvVar("DB_NAME");


            self::$conn = new \mysqli($servername, $username, $password, $dbname);

            if (self::$conn->connect_error) {
                die("Connexion échouée : " . self::$conn->connect_error);
            }
        }
        return self::$conn;
    }
    static function parseEnvVar(string $envVar)
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
        $env = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            $env[$key] = $value;
        }

        return $env[$envVar] ?? $val;
    }
}
