<?php
namespace Models\Sae;

use Models\Database;
use Shared\Exceptions\DataBaseException;

class SaeAvis
{
    public static function add(int $saeAttributionId, int $userId, string $message): void
    {
        self::checkDatabaseConnection();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            INSERT INTO sae_avis (sae_attribution_id, user_id, message, date_envoi)
            VALUES (?, ?, ?, NOW())
        ");
        if (!$stmt) {
            throw new \Exception("Erreur SQL: " . $db->error);
        }
        $stmt->bind_param("iis", $saeAttributionId, $userId, $message);
        $stmt->execute();
        $stmt->close();
    }

    public static function getBySaeAttribution(int $saeAttributionId): array
    {
        self::checkDatabaseConnection();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT a.id, a.message, a.date_envoi, a.user_id, u.nom, u.prenom, u.role
            FROM sae_avis a
            JOIN users u ON a.user_id = u.id
            WHERE a.sae_attribution_id = ?
            ORDER BY a.date_envoi DESC
        ");
        if (!$stmt) {
            throw new \Exception("Erreur SQL: " . $db->error);
        }
        $stmt->bind_param("i", $saeAttributionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $avis = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $avis;
    }

    public static function delete(int $avisId): void
    {
        self::checkDatabaseConnection();
        $db = Database::getConnection();

        $stmt = $db->prepare("DELETE FROM sae_avis WHERE id = ?");
        if (!$stmt) {
            throw new \Exception("Erreur SQL: " . $db->error);
        }
        $stmt->bind_param("i", $avisId);
        $stmt->execute();
        $stmt->close();
    }

    public static function checkDatabaseConnection(): void
    {
        try {
            $db = Database::getConnection();
            if (!$db->ping()) {
                throw new DataBaseException("Impossible de se connecter à la base de données");
            }
        } catch (\Exception $e) {
            throw new DataBaseException("Impossible de se connecter à la base de données");
        }
    }
}
