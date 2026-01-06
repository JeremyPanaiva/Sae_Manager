<?php

namespace Models\Sae;

use Models\Database;

/**
 * SAE Avis (Feedback) model
 *
 * Manages feedback and comments on SAE (Situation d'Apprentissage et d'Évaluation).
 * Allows users (students, supervisors, clients) to post feedback, view feedback
 * history, and delete feedback entries.
 *
 * @package Models\Sae
 */
class SaeAvis
{
    /**
     * Adds feedback for a SAE
     *
     * Creates a new feedback entry associated with a SAE and the user who posted it.
     * Automatically sets the submission timestamp to the current date and time.
     *
     * @param int $saeId The ID of the SAE to add feedback to
     * @param int $userId The ID of the user posting the feedback
     * @param string $message The feedback message content
     * @return bool True if feedback was successfully added, false otherwise
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
     * Retrieves all feedback for a specific SAE
     *
     * Returns feedback entries with user information (name, role) ordered by
     * submission date (most recent first).
     *
     * @param int $saeId The ID of the SAE
     * @return array Array of feedback entries with id, message, date_envoi, nom, prenom, role
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
     * Deletes a feedback entry
     *
     * Permanently removes a feedback entry from the database.
     *
     * @param int $avisId The ID of the feedback entry to delete
     * @return bool True if deletion was successful, false otherwise
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