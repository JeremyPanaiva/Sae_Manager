<?php
// Simple migration runner for SAE Manager
// Usage: php migrations/run_migrations.php

require_once __DIR__ . '/../Autoloader.php';
\Autoloader::register();

use Models\Database;

$files = [
    __DIR__ . '/001_add_session_token.sql',
];

try {
    $db = Database::getConnection();
} catch (\Throwable $e) {
    fwrite(STDERR, "Erreur : impossible de se connecter à la base de données. Vérifiez vos variables d'environnement ou le fichier .env.\n");
    fwrite(STDERR, "Détails: " . $e->getMessage() . "\n");
    exit(1);
}

foreach ($files as $file) {
    if (!file_exists($file)) {
        fwrite(STDERR, "Fichier de migration non trouvé: $file\n");
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "Impossible de lire le fichier: $file\n");
        continue;
    }

    // Execute as multi_query to support multiple statements
    if ($db->multi_query($sql)) {
        // Consume all results
        do {
            if ($result = $db->store_result()) {
                $result->free();
            }
        } while ($db->more_results() && $db->next_result());

        fwrite(STDOUT, "Migration appliquée: $file\n");
    } else {
        // If the query failed, print error but continue
        fwrite(STDERR, "Erreur lors de l'exécution de la migration $file: " . $db->error . "\n");
    }
}

fwrite(STDOUT, "Migrations terminées. Vérifiez la table 'users' pour la colonne 'session_token'.\n");
exit(0);

