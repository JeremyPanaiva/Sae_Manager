<?php

namespace Models\Sae;

use Models\Database;

class Sae
{
    /**
     * Crée une nouvelle SAE et retourne son ID
     *
     * @param int $clientId
     * @param string $titre
     * @param string $description
     * @return int L'ID de la SAE créée
     */
    public static function create(int $clientId, string $titre, string $description): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO sae (titre, description, client_id, date_creation) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("ssi", $titre, $description, $clientId);
        $stmt->execute();
        $saeId = $db->insert_id;
        $stmt->close();

        return $saeId;
    }

    public static function getAllProposed(): array
    {
        $db = Database::getConnection();
        $result = $db->query("SELECT * FROM sae");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public static function getByClient(int $clientId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM sae WHERE client_id = ?");
        $stmt->bind_param('i', $clientId);
        $stmt->execute();

        $result = $stmt->get_result();
        $saes = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
        return $saes;
    }

    public static function delete(int $clientId, int $saeId): bool
    {
        $mysqli = \Models\Database::getConnection(); // mysqli

        // Supprime uniquement de la table SAE
        $stmt = $mysqli->prepare("DELETE FROM sae WHERE id = ? AND client_id = ?");
        if (!$stmt) {
            throw new \Exception("Erreur prepare: " . $mysqli->error);
        }

        $stmt->bind_param("ii", $saeId, $clientId);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public static function isAttribuee(int $saeId): bool
    {
        $db = \Models\Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM sae_attributions WHERE sae_id = ?");
        $stmt->bind_param("i", $saeId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['count'] > 0;
    }

    public static function getById(int $saeId): ?array
    {
        $db = \Models\Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM sae WHERE id = ?");
        $stmt->bind_param("i", $saeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $sae = $result->fetch_assoc();
        $stmt->close();

        return $sae ?: null; // Retourne null si pas trouvé
    }

    public static function getAll(): array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT 
                s.id,
                s.titre,
                s.description,
                s.date_creation,
                u.nom AS client_nom,
                u.prenom AS client_prenom,
                u.mail AS client_mail
            FROM sae s
            JOIN users u ON s.client_id = u.id
            ORDER BY s.date_creation DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $saes = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $saes;
    }
}