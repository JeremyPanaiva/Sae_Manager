<?php
namespace Models\Sae;

use Models\Database;
use Shared\Exceptions\SaeAlreadyAssignedException;
use Shared\Exceptions\StudentAlreadyAssignedException;

class SaeAttribution
{
    /**
     * Assigne des étudiants à une SAE pour un responsable
     */
    public static function assignStudentsToSae(int $saeId, array $studentIds, int $responsableId): void
    {
        $db = Database::getConnection();

        // Vérifie si la SAE est déjà attribuée à un autre responsable
        self::checkIfSaeAlreadyAssignedToAnotherResponsable($saeId, $responsableId);

        // Récupérer la date de rendu existante
        $stmt = $db->prepare("SELECT date_rendu FROM sae_attributions WHERE sae_id = ? AND responsable_id = ? LIMIT 1");
        $stmt->bind_param("ii", $saeId, $responsableId);
        $stmt->execute();
        $result = $stmt->get_result();
        $dateRendu = $result->fetch_assoc()['date_rendu'] ?? date('Y-m-d');
        $stmt->close();

        foreach ($studentIds as $studentId) {
            if (self::isStudentAssignedToSae($saeId, $studentId)) {
                // Récupérer en une seule requête le nom de l'étudiant et le titre de la SAE
                $stmt = $db->prepare("
            SELECT s.titre AS sae_titre, u.nom, u.prenom
            FROM sae_attributions sa
            JOIN sae s ON sa.sae_id = s.id
            JOIN users u ON sa.student_id = u.id
            WHERE sa.sae_id = ? AND sa.student_id = ?
            LIMIT 1
        ");
                $stmt->bind_param("ii", $saeId, $studentId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                $saeTitre = $row['sae_titre'] ?? 'N/A';
                $studentNom = trim(($row['nom'] ?? '') . ' ' . ($row['prenom'] ?? 'N/A'));

                throw new StudentAlreadyAssignedException($saeTitre, $studentNom);
            }

            // Ajouter l'étudiant
            $stmtInsert = $db->prepare("
        INSERT INTO sae_attributions (sae_id, student_id, responsable_id, date_rendu)
        VALUES (?, ?, ?, ?)
    ");
            $stmtInsert->bind_param("iiis", $saeId, $studentId, $responsableId, $dateRendu);
            $stmtInsert->execute();
            $stmtInsert->close();
        }


    }

    /**
     * Vérifie si la SAE est déjà attribuée à un autre responsable
     */
    public static function checkIfSaeAlreadyAssignedToAnotherResponsable(int $saeId, int $responsableId): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT s.titre, sa.responsable_id
            FROM sae_attributions sa
            JOIN sae s ON sa.sae_id = s.id
            WHERE sa.sae_id = ? AND sa.responsable_id != ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $saeId, $responsableId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $otherResponsableId = $row['responsable_id'];
            $stmtResp = $db->prepare("SELECT nom, prenom FROM users WHERE id = ?");
            $stmtResp->bind_param("i", $otherResponsableId);
            $stmtResp->execute();
            $resp = $stmtResp->get_result()->fetch_assoc();
            $stmtResp->close();

            $fullName = trim(($resp['nom'] ?? 'N/A') . ' ' . ($resp['prenom'] ?? ''));
            $stmt->close();
            throw new SaeAlreadyAssignedException($row['titre'], $fullName);
        }

        $stmt->close();
    }

    /**
     * Vérifie si un étudiant est déjà assigné à une SAE
     */
    public static function isStudentAssignedToSae(int $saeId, int $studentId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id FROM sae_attributions WHERE sae_id = ? AND student_id = ? LIMIT 1");
        $stmt->bind_param("ii", $saeId, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $assigned = (bool) $result->fetch_assoc();
        $stmt->close();
        return $assigned;
    }

    /* -------------------- Fonctions pour le dashboard -------------------- */

    public static function getSaeForStudent(int $studentId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                sa.id AS sae_attribution_id,
                s.id AS sae_id,
                s.titre AS sae_titre,
                s.description AS sae_description,
                u_resp.nom AS responsable_nom,
                u_resp.prenom AS responsable_prenom,
                u_resp.mail AS responsable_mail,
                u_client.nom AS client_nom,
                u_client.prenom AS client_prenom,
                u_client.mail AS client_mail,
                sa.date_rendu
            FROM sae s
            JOIN sae_attributions sa ON s.id = sa.sae_id
            LEFT JOIN users u_resp ON sa.responsable_id = u_resp.id
            LEFT JOIN users u_client ON s.client_id = u_client.id
            WHERE sa.student_id = ?
        ");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $saes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $saes;
    }

    public static function getStudentsBySae(int $saeId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.nom, u.prenom
            FROM users u
            JOIN sae_attributions sa ON sa.student_id = u.id
            WHERE sa.sae_id = ?
        ");
        $stmt->bind_param("i", $saeId);
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $students;
    }

    public static function getSaeForResponsable(int $responsableId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                MIN(sa.id) AS sae_attribution_id,
                s.id AS sae_id,
                s.titre AS sae_titre,
                s.description AS sae_description,
                sa.date_rendu,
                GROUP_CONCAT(CONCAT(u.nom,' ',u.prenom) SEPARATOR ', ') AS etudiants
            FROM sae_attributions sa
            JOIN sae s ON s.id = sa.sae_id
            JOIN users u ON u.id = sa.student_id
            WHERE sa.responsable_id = ?
            GROUP BY sa.sae_id, s.titre, s.description, sa.date_rendu
        ");
        $stmt->bind_param("i", $responsableId);
        $stmt->execute();
        $saes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $saes;
    }

    /**
     * Nouvelle fonction pour récupérer toutes les attributions d'une SAE (côté client)
     */
    public static function getAttributionsBySae(int $saeId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT sa.id, sa.student_id, sa.responsable_id, sa.date_rendu, s.client_id
            FROM sae_attributions sa
            JOIN sae s ON sa.sae_id = s.id
            WHERE sa.sae_id = ?
        ");
        $stmt->bind_param("i", $saeId);
        $stmt->execute();
        $attributions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $attributions;
    }

    public static function getAvisBySaeAttribution(int $saeAttributionId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM sae_avis WHERE sae_attribution_id = ?");
        $stmt->bind_param("i", $saeAttributionId);
        $stmt->execute();
        $avis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $avis;
    }

    public static function updateDateRendu(int $saeId, int $responsableId, string $newDate): void
    {
        $db = \Models\Database::getConnection();

        $stmt = $db->prepare("
        UPDATE sae_attributions
        SET date_rendu = ?
        WHERE sae_id = ? AND responsable_id = ?
    ");
        $stmt->bind_param("sii", $newDate, $saeId, $responsableId);
        $stmt->execute();
        $stmt->close();
    }

}
