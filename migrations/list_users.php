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
    $res = $db->query("SELECT id, mail, session_token FROM users LIMIT 10");
    if (!$res) {
        fwrite(STDERR, "Erreur lors de la requête: " . $db->error . "\n");
        exit(1);
    }

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }

    if (count($rows) === 0) {
        fwrite(STDOUT, "Aucun utilisateur trouvé.\n");
        exit(0);
    }

    foreach ($rows as $r) {
        fwrite(STDOUT, sprintf("id=%s mail=%s session_token=%s\n", $r['id'], $r['mail'], $r['session_token'] ?? 'NULL'));
    }
    exit(0);
} catch (\Throwable $e) {
    fwrite(STDERR, "Erreur: " . $e->getMessage() . "\n");
    exit(1);
}

