<?php

namespace Models\Sae;

use Models\Database;
use Shared\Exceptions\DataBaseException;
use Shared\Exceptions\SaeAlreadyAssignedException;
use Shared\Exceptions\StudentAlreadyAssignedException;

/**
 * SAE Attribution model
 *
 * Manages the assignment of students to SAE (Situation d'Apprentissage et d'Évaluation)
 * by supervisors (responsables).  Handles creation, retrieval, and deletion of assignments,
 * as well as validation to ensure a SAE can only be assigned by one supervisor and students
 * cannot be assigned to the same SAE multiple times.
 *
 * @package Models\Sae
 */
class SaeAttribution
{
    /**
     * Assigns students to a SAE for a specific supervisor
     *
     * Validates that the SAE is not already assigned to another supervisor and that
     * students are not already assigned to this SAE.  Uses the existing submission
     * deadline if the supervisor has already made assignments to this SAE.
     *
     * @param int $saeId The ID of the SAE to assign
     * @param array $studentIds Array of student IDs to assign
     * @param int $responsableId The ID of the supervisor making the assignment
     * @throws SaeAlreadyAssignedException If SAE is already assigned to another supervisor
     * @throws StudentAlreadyAssignedException If a student is already assigned to this SAE
     * @throws DataBaseException If database operation fails
     */
    public static function assignStudentsToSae(int $saeId, array $studentIds, int $responsableId): void
    {
        Database::checkConnection();

        $db = Database::getConnection();

        // Check if SAE is already assigned to another supervisor
        self::checkIfSaeAlreadyAssignedToAnotherResponsable($saeId, $responsableId);

        // Retrieve existing submission deadline for this supervisor and SAE
        $stmt = $db->prepare("SELECT date_rendu FROM sae_attributions WHERE sae_id = ? AND responsable_id = ?  LIMIT 1");
        $stmt->bind_param("ii", $saeId, $responsableId);
        $stmt->execute();
        $result = $stmt->get_result();
        $dateRendu = $result->fetch_assoc()['date_rendu'] ?? date('Y-m-d');
        $stmt->close();

        // Assign each student
        foreach ($studentIds as $studentId) {
            if (self::isStudentAssignedToSae($saeId, $studentId)) {
                // Retrieve SAE title and student name in a single query
                $stmt = $db->prepare("
                    SELECT s.titre AS sae_titre, u.nom, u.prenom
                    FROM sae_attributions sa
                    JOIN sae s ON sa.sae_id = s. id
                    JOIN users u ON sa.student_id = u. id
                    WHERE sa.sae_id = ? AND sa. student_id = ?
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

            // Insert student assignment
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
     * Checks if a SAE is already assigned to a different supervisor
     *
     * @param int $saeId The ID of the SAE to check
     * @param int $responsableId The ID of the current supervisor
     * @throws SaeAlreadyAssignedException If SAE is assigned to another supervisor
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

            // Retrieve other supervisor's name
            $stmtResp = $db->prepare("SELECT nom, prenom FROM users WHERE id = ?");
            $stmtResp->bind_param("i", $otherResponsableId);
            $stmtResp->execute();
            $resp = $stmtResp->get_result()->fetch_assoc();
            $stmtResp->close();

            $fullName = trim(($resp['nom'] ?? 'N/A') . ' ' . ($resp['prenom'] ??  ''));
            $stmt->close();
            throw new SaeAlreadyAssignedException($row['titre'], $fullName);
        }

        $stmt->close();
    }

    /**
     * Checks if a student is already assigned to a SAE
     *
     * @param int $saeId The ID of the SAE
     * @param int $studentId The ID of the student
     * @return bool True if student is already assigned
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

    /**
     * Retrieves all students assigned to a specific SAE
     *
     * @param int $saeId The ID of the SAE
     * @return array Array of students with id, nom, prenom
     */
    public static function getStudentsForSae(int $saeId): array
    {
        $db = Database::getConnection();

        $query = "SELECT users.id, users.nom, users.prenom
                  FROM users
                  INNER JOIN sae_attributions ON sae_attributions.student_id = users.id
                  WHERE sae_attributions.sae_id = ?";

        $stmt = $db->prepare($query);

        if ($stmt === false) {
            die('Erreur de préparation de la requête : ' . $db->error);
        }

        $stmt->bind_param('i', $saeId);

        if (!$stmt->execute()) {
            die('Erreur lors de l\'exécution de la requête : ' . $stmt->error);
        }

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Retrieves students assigned to a SAE (alias method)
     *
     * @param int $saeId The ID of the SAE
     * @return array Array of students with nom, prenom
     */
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

    /**
     * Retrieves all SAE assigned by a specific supervisor
     *
     * Returns aggregated data with student names concatenated.
     *
     * @param int $responsableId The ID of the supervisor
     * @return array Array of SAE with assignment details
     */
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
     * Retrieves all attributions for a specific SAE (client view)
     *
     * @param int $saeId The ID of the SAE
     * @return array Array of attributions with student, supervisor, and deadline information
     */
    public static function getAttributionsBySae(int $saeId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT sa.id, sa.student_id, sa.responsable_id, sa.date_rendu, s.client_id
            FROM sae_attributions sa
            JOIN sae s ON sa.sae_id = s. id
            WHERE sa.sae_id = ?
        ");
        $stmt->bind_param("i", $saeId);
        $stmt->execute();
        $attributions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $attributions;
    }

    /**
     * Retrieves all feedback (avis) for a specific SAE attribution
     *
     * @param int $saeAttributionId The ID of the SAE attribution
     * @return array Array of feedback entries
     */
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

    /**
     * Updates the submission deadline for all students assigned to a SAE by a supervisor
     *
     * @param int $saeId The ID of the SAE
     * @param int $responsableId The ID of the supervisor
     * @param string $newDate The new submission deadline (Y-m-d format)
     * @throws DataBaseException If database operation fails
     */
    public static function updateDateRendu(int $saeId, int $responsableId, string $newDate): void
    {
        try {
            Database::checkConnection();

            $db = Database::getConnection();

            $stmt = $db->prepare("
                UPDATE sae_attributions
                SET date_rendu = ? 
                WHERE sae_id = ? AND responsable_id = ? 
            ");

            if (!$stmt) {
                throw new \Exception("Erreur lors de la préparation de la requête SQL");
            }

            $stmt->bind_param("sii", $newDate, $saeId, $responsableId);

            if (!$stmt->execute()) {
                throw new \Exception("Erreur lors de l'exécution de la requête SQL");
            }

            $stmt->close();
        } catch (DataBaseException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new DataBaseException(
                "Impossible de mettre à jour la date de rendu.  Veuillez contacter l'administrateur."
            );
        }
    }

    /**
     * Removes a student assignment from a SAE
     *
     * @param int $saeId The ID of the SAE
     * @param int $studentId The ID of the student to unassign
     * @throws DataBaseException If database operation fails
     */
    public static function removeFromStudent(int $saeId, int $studentId): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("DELETE FROM sae_attributions WHERE sae_id = ? AND student_id = ?");
            if ($stmt === false) {
                throw new DataBaseException("Erreur de préparation de la requête :  " . $db->error);
            }
            $stmt->bind_param("ii", $saeId, $studentId);
            if (!$stmt->execute()) {
                throw new DataBaseException("Erreur d'exécution :  " . $stmt->error);
            }
            $stmt->close();
        } catch (\mysqli_sql_exception $e) {
            throw new DataBaseException("Erreur SQL : " . $e->getMessage());
        }
    }

    /**
     * Retrieves assigned students for a SAE (alias method - deprecated)
     *
     * @deprecated Use getStudentsForSae() instead
     * @param int $saeId The ID of the SAE
     * @return array Array of students
     */
    public static function getAssignedStudents(int $saeId): array
    {
        $query = "SELECT u.id, u.nom, u.prenom FROM users u
                  JOIN sae_attribution sa ON u.id = sa. student_id
                  WHERE sa.sae_id = : sae_id";
        $stmt = Database::getConnection()->prepare($query);
        $stmt->bindValue(':sae_id', $saeId, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Verifies that the supervisor is authorized to unassign a student
     *
     * Checks that the supervisor making the unassignment request is the same
     * supervisor who originally assigned the student to the SAE.
     *
     * @param int $saeId The ID of the SAE
     * @param int $responsableId The ID of the supervisor requesting unassignment
     * @param int $studentId The ID of the student to unassign
     * @throws \Shared\Exceptions\UnauthorizedSaeUnassignmentException If supervisor is not authorized
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
            WHERE sa.sae_id = ? AND sa. student_id = ?
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

    /**
     * Checks if a student was assigned to a SAE by a specific supervisor
     *
     * @param int $saeId The ID of the SAE
     * @param int $studentId The ID of the student
     * @param int $responsableId The ID of the supervisor
     * @return bool True if the student was assigned by this supervisor
     */
    public static function isStudentAssignedByResponsable(int $saeId, int $studentId, int $responsableId): bool
    {
        $db = Database:: getConnection();
        $stmt = $db->prepare("
            SELECT id 
            FROM sae_attributions 
            WHERE sae_id = ? AND student_id = ?  AND responsable_id = ?  
            LIMIT 1
        ");
        $stmt->bind_param("iii", $saeId, $studentId, $responsableId);
        $stmt->execute();
        $result = $stmt->get_result();
        $assigned = (bool) $result->fetch_assoc();
        $stmt->close();
        return $assigned;
    }

    /**
     * Retrieves all SAE assigned to a specific student
     *
     * Includes information about the supervisor, client, and submission deadline.
     *
     * @param int $studentId The ID of the student
     * @return array Array of SAE with complete assignment details
     */
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
            JOIN sae_attributions sa ON s. id = sa.sae_id
            LEFT JOIN users u_resp ON sa. responsable_id = u_resp.id
            LEFT JOIN users u_client ON s.client_id = u_client.id
            WHERE sa.student_id = ?
        ");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $saes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $saes;
    }

    /**
     * Retrieves the supervisor who assigned a SAE
     *
     * @param int $saeId The ID of the SAE
     * @return array|null Supervisor information (id, nom, prenom) or null if unassigned
     */
    public static function getResponsableForSae(int $saeId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT DISTINCT u.id, u.nom, u.prenom
            FROM sae_attributions sa
            JOIN users u ON sa.responsable_id = u.id
            WHERE sa.sae_id = ? 
            LIMIT 1
        ");
        $stmt->bind_param("i", $saeId);
        $stmt->execute();
        $resp = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $resp ?: null;
    }

    /**
     * Retrieves students for a specific attribution
     *
     * Returns the student associated with a given attribution ID.
     *
     * @param int $attribId The ID of the attribution
     * @return array Array containing student information
     * @throws DataBaseException If database operation fails
     */
    public static function getStudentsByAttribution(int $attribId): array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT u.id, u.nom, u.prenom
            FROM users u
            JOIN sae_attributions sa ON sa.student_id = u.id
            WHERE sa.id = ?
        ");

        if (! $stmt) {
            throw new DataBaseException("Erreur de préparation de la requête :  " . $db->error);
        }

        $stmt->bind_param("i", $attribId);

        if (!$stmt->execute()) {
            throw new DataBaseException("Erreur lors de l'exécution de la requête :  " . $stmt->error);
        }

        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return $students;
    }
}