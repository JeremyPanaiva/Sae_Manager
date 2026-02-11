<?php
require_once __DIR__ . '/../Autoloader.php';
\Autoloader::register();

use Models\Database;

try {
    $db = Database::getConnection();
} catch (\Throwable $e) {
    fwrite(STDERR, "Erreur de connexion à la base: " . $e->getMessage() . "\n");
    exit(1);
}

try {
    $res = $db->query("SHOW COLUMNS FROM users LIKE 'session_token'");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        fwrite(STDOUT, "Column 'session_token' exists in users table.\n");
        fwrite(STDOUT, print_r($row, true) . "\n");
        exit(0);
    } else {
        fwrite(STDOUT, "Column 'session_token' NOT found in users table.\n");
        exit(2);
    }
} catch (\Throwable $e) {
    fwrite(STDERR, "Erreur lors de l'exécution de la requête: " . $e->getMessage() . "\n");
    exit(1);
}

