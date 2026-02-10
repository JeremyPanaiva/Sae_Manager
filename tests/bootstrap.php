<?php

// Bootstrap pour les tests
require_once __DIR__ . '/../Autoloader.php';

if (!class_exists('Autoloader')) {
    throw new RuntimeException('Autoloader class not found');
}

Autoloader::register();

// Charger les variables d'environnement de test
$envTestPath = __DIR__ . '/../.env.test';

if (file_exists($envTestPath)) {
    $lines = file($envTestPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines !== false) {
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

// Démarrer une session pour les tests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
