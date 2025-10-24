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

    // Modèle SaeAttribution.php

    // Modèle SaeAttribution.php

    public static function getStudentsForSae(int $saeId): array
    {
        // Connexion à la base de données
        $db = Database::getConnection();

        // Requête pour récupérer les étudiants attribués à la SAE
        $query = "SELECT users.id, users.nom, users.prenom 
                  FROM users 
                  INNER JOIN sae_attributions ON sae_attributions.student_id = users.id
                  WHERE sae_attributions.sae_id = ?";

        // Préparation de la requête
        $stmt = $db->prepare($query);

        if ($stmt === false) {
            die('Erreur de préparation de la requête : ' . $db->error);
        }

        // Lier le paramètre sae_id
        $stmt->bind_param('i', $saeId);

        // Exécution de la requête
        if (!$stmt->execute()) {
            die('Erreur lors de l\'exécution de la requête : ' . $stmt->error);
        }

        // Récupération des résultats
        $result = $stmt->get_result();

        // Vérifie si des résultats sont retournés
        if ($result->num_rows === 0) {
            return [];  // Aucun étudiant trouvé pour cette SAE
        }

        // Retourner tous les étudiants associés à la SAE
        return $result->fetch_all(MYSQLI_ASSOC);
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

    // Désassigner un étudiant d'une SAE
    public static function removeFromStudent(int $saeId, int $studentId): void
    {
        // Connexion à la base de données
        $db = Database::getConnection();

        // Requête pour supprimer l'attribution de la SAE pour l'étudiant
        $query = "DELETE FROM sae_attributions WHERE sae_id = ? AND student_id = ?";

        // Préparation de la requête
        $stmt = $db->prepare($query);

        if ($stmt === false) {
            die('Erreur de préparation de la requête : ' . $db->error);
        }

        // Lier les paramètres sae_id et student_id
        $stmt->bind_param('ii', $saeId, $studentId); // 'ii' pour deux entiers

        // Exécution de la requête
        if (!$stmt->execute()) {
            die('Erreur lors de l\'exécution de la requête : ' . $stmt->error);
        }
    }

    // Obtenir les étudiants assignés à une SAE
    public static function getAssignedStudents(int $saeId): array
    {
        $query = "SELECT u.id, u.nom, u.prenom FROM users u
                  JOIN sae_attribution sa ON u.id = sa.student_id
                  WHERE sa.sae_id = :sae_id";
        $stmt = Database::getConnection()->prepare($query);
        $stmt->bindValue(':sae_id', $saeId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Vérifie que le responsable est bien celui qui a attribué l'étudiant à la SAE
     * @throws UnauthorizedSaeUnassignmentException
     */
    /**
     * Vérifie que le responsable est bien celui qui a attribué l'étudiant à la SAE
     * @throws UnauthorizedSaeUnassignmentException
     */
    public static function checkResponsableOwnership(int $saeId, int $responsableId, int $studentId): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
        SELECT sa.responsable_id, s.titre, u.nom, u.prenom, resp.nom AS resp_nom, resp.prenom AS resp_prenom
        FROM sae_attributions sa
        JOIN sae s ON sa.sae_id = s.id
        JOIN users u ON sa.student_id = u.id
        JOIN users resp ON sa.responsable_id = resp.id
        WHERE sa.sae_id = ? AND sa.student_id = ?
        LIMIT 1
    ");
        $stmt->bind_param("ii", $saeId, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $actualResponsableId = (int)$row['responsable_id'];

            if ($actualResponsableId !== $responsableId) {
                $saeTitre = $row['titre'] ?? 'N/A';
                $studentName = trim(($row['nom'] ?? '') . ' ' . ($row['prenom'] ?? 'N/A'));
                $actualResponsableName = trim(($row['resp_nom'] ?? '') . ' ' . ($row['resp_prenom'] ?? 'N/A'));

                $stmt->close();
                throw new \Shared\Exceptions\UnauthorizedSaeUnassignmentException(
                    $saeTitre,
                    $studentName,
                    $actualResponsableName
                );
            }
        }

        $stmt->close();
    }

    public static function isStudentAssignedByResponsable(int $saeId, int $studentId, int $responsableId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
        SELECT id 
        FROM sae_attributions 
        WHERE sae_id = ? AND student_id = ? AND responsable_id = ? 
        LIMIT 1
    ");
        $stmt->bind_param("iii", $saeId, $studentId, $responsableId);
        $stmt->execute();
        $result = $stmt->get_result();
        $assigned = (bool) $result->fetch_assoc();
        $stmt->close();
        return $assigned;
    }


}
