<?php
// Usage: php migrations/set_session_token.php <user_id> [token]
// If token not provided, a secure random token is generated and printed.

require_once __DIR__ . '/../Autoloader.php';
\Autoloader::register();

use Models\Database;

if ($argc < 2) {
    fwrite(STDERR, "Usage: php migrations/set_session_token.php <user_id> [token]\n");
    exit(2);
}

$userId = (int) $argv[1];
$token = $argv[2] ?? null;
if (empty($token)) {
    try {
        $token = bin2hex(random_bytes(32));
    } catch (\Throwable $e) {
        $token = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

try {
    $db = Database::getConnection();
} catch (\Throwable $e) {
    fwrite(STDERR, "Erreur de connexion à la base: " . $e->getMessage() . "\n");
    exit(1);
}

try {
    $stmt = $db->prepare("UPDATE users SET session_token = ? WHERE id = ?");
    if (!$stmt) {
        fwrite(STDERR, "Erreur de préparation: " . $db->error . "\n");
        exit(1);
    }
    $stmt->bind_param('si', $token, $userId);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        fwrite(STDOUT, "Aucun utilisateur mis à jour (id=$userId). Le token a peut-être été enregistré identique.\n");
    } else {
        fwrite(STDOUT, "Token enregistré pour user id=$userId\n");
    }
    $stmt->close();
    fwrite(STDOUT, "session_token=$token\n");
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, "Erreur lors de l'exécution: " . $e->getMessage() . "\n");
    exit(1);
}

