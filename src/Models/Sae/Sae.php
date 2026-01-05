<?php

namespace Models\Sae;

use Models\Database;
use Shared\Exceptions\DataBaseException;

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
        try {
            self::checkDatabaseConnection();

            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO sae (titre, description, client_id, date_creation) VALUES (?, ?, ?, NOW())");
            if (!$stmt) {
                throw new \Exception("Erreur prepare: " . $db->error);
            }

            $stmt->bind_param("ssi", $titre, $description, $clientId);

            if (!$stmt->execute()) {
                throw new \Exception("Erreur execute: " . $stmt->error);
            }

            $saeId = $db->insert_id;
            $stmt->close();

            return $saeId;
        } catch (\Exception $e) {
            throw new \Shared\Exceptions\DataBaseException(
                "Impossible de créer la SAE : " . $e->getMessage()
            );
        }
    }

    public static function delete(int $clientId, int $saeId): bool
    {
        try {
            self::checkDatabaseConnection();

            $db = Database::getConnection();

            $stmt = $db->prepare("DELETE FROM sae WHERE id = ? AND client_id = ?");
            if (!$stmt) {
                throw new \Exception("Erreur prepare: " . $db->error);
            }

            $stmt->bind_param("ii", $saeId, $clientId);

            if (!$stmt->execute()) {
                throw new \Exception("Erreur execute: " . $stmt->error);
            }

            $stmt->close();

            return true;
        } catch (\Exception $e) {
            throw new \Shared\Exceptions\DataBaseException(
                "Impossible de supprimer la SAE : " . $e->getMessage()
            );
        }
    }


    public static function getAllProposed(): array
    {
        $db = \Models\Database::getConnection();

        $stmt = $db->prepare("
        SELECT 
            s.id,
            s.titre,
            s.description,
            s.client_id,
            s.date_creation
        FROM sae s
    ");

        if (!$stmt->execute()) {
            throw new \Shared\Exceptions\DataBaseException(
                "Impossible de récupérer les SAE."
            );
        }

        $saes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $saes;
    }


    public static function getByClient(int $clientId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
        SELECT id, titre, description, date_creation
        FROM sae
        WHERE client_id = ?  
        ORDER BY date_creation DESC
    ");
        $stmt->bind_param("i", $clientId);
        $stmt->execute();
        $saes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $saes;
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

    /**
     * 🔧 MODIFIÉ : Récupère une SAE par son ID AVEC les informations du client
     */
    public static function getById(int $saeId): ?array
    {
        $db = \Models\Database::getConnection();

        // 🆕 JOIN avec la table users pour récupérer les infos du client
        $stmt = $db->prepare("
            SELECT 
                s.id,
                s.titre,
                s.description,
                s.client_id,
                s.date_creation,
                u.nom AS client_nom,
                u.prenom AS client_prenom,
                u.mail AS client_mail
            FROM sae s
            LEFT JOIN users u ON s.client_id = u.id
            WHERE s.id = ?
        ");
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


    public static function update(int $clientId, int $saeId, string $titre, string $description): bool
    {
        try {
            self::checkDatabaseConnection();

            $db = Database::getConnection();
            $stmt = $db->prepare("
            UPDATE sae 
            SET titre = ?, description = ?
            WHERE id = ? AND client_id = ?
        ");
            if (!$stmt) {
                throw new \Exception("Erreur prepare: " . $db->error);
            }

            $stmt->bind_param("ssii", $titre, $description, $saeId, $clientId);

            if (!$stmt->execute()) {
                throw new \Exception("Erreur execute: " . $stmt->error);
            }

            $stmt->close();
            return true;

        } catch (\Exception $e) {
            throw new DataBaseException("Impossible de modifier la SAE : " . $e->getMessage());
        }
    }

    public static function checkDatabaseConnection(): void
    {
        try {
            $db = Database::getConnection();
            // simple ping pour tester la connexion
            if (!$db->ping()) {
                throw new DataBaseException("Unable to connect to the database");
            }
        } catch (\Exception $e) {
            throw new DataBaseException("Unable to connect to the database");
        }
    }

    /**
     * Récupère uniquement les SAE attribuées d'un client
     */
    public static function getAssignedSaeByClient(int $clientId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
        SELECT DISTINCT s. id, s.titre, s. description, s.date_creation
        FROM sae s
        INNER JOIN sae_attributions sa ON s.id = sa.sae_id
        WHERE s. client_id = ? 
        ORDER BY s.date_creation DESC
    ");
        $stmt->bind_param("i", $clientId);
        $stmt->execute();
        $saes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $saes;
    }

}