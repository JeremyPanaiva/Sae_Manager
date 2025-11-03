<?php

namespace Models\Sae;

use Models\Database;

class SaeAvis
{
    public static function add(int $saeAttributionId, string $emetteur, string $message): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO sae_avis (sae_attribution_id, emetteur, message, date_envoi) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iss", $saeAttributionId, $emetteur, $message);
        $stmt->execute();
        $stmt->close();
    }

    public static function getBySaeAttribution(int $saeAttributionId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT emetteur, message, date_envoi 
            FROM sae_avis 
            WHERE sae_attribution_id = ?
            ORDER BY date_envoi DESC
        ");
        $stmt->bind_param("i", $saeAttributionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $avis = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $avis;
    }
}
