<?php
// Dev helper moved into migrations/ for safety (NOT public). Keep this file for local testing only.
// WARNING: This file must NOT be accessible from the web in production.

require_once __DIR__ . '/../Autoloader.php';
\Autoloader::register();

use Models\Database;

// Params: ?user_id=123&token=abcd
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
$token = $_GET['token'] ?? null;

if ($userId === null) {
    echo "Usage: migrations/dev_simulate_login.php?user_id=<id>&token=<token>\n";
    exit;
}

try {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, nom, prenom, mail FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    $stmt->close();
} catch (\Throwable $e) {
    echo "Erreur DB: " . htmlspecialchars($e->getMessage());
    exit;
}

if (! $user) {
    echo "Utilisateur introuvable (id=$userId)\n";
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session values
$_SESSION['user'] = [
    'id' => $user['id'],
    'nom' => $user['nom'] ?? '',
    'prenom' => $user['prenom'] ?? '',
    'mail' => $user['mail'] ?? '',
    'role' => 'etudiant'
];

if ($token !== null) {
    $_SESSION['session_token'] = $token;
}

echo "Session simulée pour user id={$user['id']} (mail=" . htmlspecialchars($user['mail']) . ")<br>";
echo "session_token set in session: " . htmlspecialchars($_SESSION['session_token'] ?? 'NULL') . "<br>";
echo "<a href='/'>Aller à la page d'accueil</a><br>";

echo "<p>Après avoir modifié le token en base (simulate second login), rechargez la page d'accueil dans ce navigateur pour voir s'il y a redirection vers /user/login?session_expired=1</p>";

