<?php
namespace Models\Sae;

use Models\Database;

class SaeAvis
{
    /**
     * Ajouter un avis pour une SAE
     */
    public static function add(int $saeId, int $userId, string $message): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO sae_avis (sae_id, user_id, message, date_envoi) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iis", $saeId, $userId, $message);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Récupérer tous les avis d'une SAE
     */
    public static function getBySae(int $saeId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                sa.id,
                sa.message,
                sa.date_envoi,
                u.nom,
                u.prenom,
                u.role
            FROM sae_avis sa
            JOIN users u ON sa. user_id = u.id
            WHERE sa.sae_id = ?
            ORDER BY sa. date_envoi DESC
        ");
        $stmt->bind_param("i", $saeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $avis = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $avis;
    }

    /**
     * Supprimer un avis
     */
    public static function delete(int $avisId): bool
    {
        $db = Database:: getConnection();
        $stmt = $db->prepare("DELETE FROM sae_avis WHERE id = ? ");
        $stmt->bind_param("i", $avisId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}