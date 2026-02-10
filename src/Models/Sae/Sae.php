<?php

namespace Models\Sae;

use Models\Database;
use Shared\Exceptions\DataBaseException;

/**
 * SAE (Situation d'Apprentissage et d'Évaluation) model
 *
 * Handles database operations for SAE entities including creation, retrieval,
 * updating, and deletion.  SAE are learning projects proposed by clients and
 * assigned to students by supervisors (responsables).
 *
 * @package Models\Sae
 */
class Sae
{
    /**
     * Creates a new SAE and returns its ID
     *
     * @param int $clientId The ID of the client creating the SAE
     * @param string $titre The title of the SAE
     * @param string $description The description of the SAE
     * @return int The ID of the created SAE
     * @throws DataBaseException If database operation fails
     */
    public static function create(int $clientId, string $titre, string $description): int
    {
        try {
            Database::checkConnection();

            $db = Database::getConnection();
            $stmt = $db->prepare(
                "INSERT INTO sae (titre, description, client_id, date_creation) " .
                "VALUES (?, ?, ?, NOW())"
            );
            if (!$stmt) {
                throw new \Exception("Erreur prepare:  " . $db->error);
            }

            $stmt->bind_param("ssi", $titre, $description, $clientId);

            if (!$stmt->execute()) {
                throw new \Exception("Erreur execute:  " . $stmt->error);
            }

            $saeId = (int) $db->insert_id;
            $stmt->close();

            return $saeId;
        } catch (\Exception $e) {
            throw new DataBaseException(
                "Impossible de créer la SAE : " . $e->getMessage()
            );
        }
    }

    /**
     * Deletes a SAE
     *
     * Only the client who created the SAE can delete it.
     *
     * @param int $clientId The ID of the client who owns the SAE
     * @param int $saeId The ID of the SAE to delete
     * @return bool True if deletion was successful
     * @throws DataBaseException If database operation fails
     */
    public static function delete(int $clientId, int $saeId): bool
    {
        try {
            Database::checkConnection();

            $db = Database::getConnection();

            $stmt = $db->prepare("DELETE FROM sae WHERE id = ? AND client_id = ? ");
            if (!$stmt) {
                throw new \Exception("Erreur prepare: " .   $db->error);
            }

            $stmt->bind_param("ii", $saeId, $clientId);

            if (! $stmt->execute()) {
                throw new \Exception("Erreur execute: " . $stmt->error);
            }

            $stmt->close();

            return true;
        } catch (\Exception $e) {
            throw new DataBaseException(
                "Impossible de supprimer la SAE :  " . $e->getMessage()
            );
        }
    }

    /**
     * Retrieves all proposed SAE
     *
     * Returns all SAE available in the system, typically used by supervisors
     * to view SAE they can assign to students.
     *
     * @return array<int, array<string, mixed>> Array of SAE with id, titre, description, client_id, date_creation
     * @throws DataBaseException If database operation fails
     */
    public static function getAllProposed(): array
    {
        $db = Database::getConnection();

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
    ");

        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans getAllProposed.");
        }

        if (!$stmt->execute()) {
            throw new DataBaseException("Impossible de récupérer les SAE.");
        }

        $result = $stmt->get_result();
        if ($result === false) {
            throw new DataBaseException("Échec de récupération du résultat dans getAllProposed.");
        }

        $saes = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $saes;
    }

    /**
     * Retrieves all SAE created by a specific client
     *
     * @param int $clientId The ID of the client
     * @return array<int, array<string, mixed>> Array of SAE ordered by creation date (newest first)
     * @throws DataBaseException If database operation fails
     */
    public static function getByClient(int $clientId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, titre, description, date_creation
            FROM sae
            WHERE client_id = ?  
            ORDER BY date_creation DESC
        ");
        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans getByClient.");
        }

        $stmt->bind_param("i", $clientId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            throw new DataBaseException("Échec de récupération du résultat dans getByClient.");
        }

        $saes = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $saes;
    }

    /**
     * Checks if a SAE has been assigned to any students
     *
     * @param int $saeId The ID of the SAE to check
     * @return bool True if the SAE has at least one student assignment
     * @throws DataBaseException If database operation fails
     */
    public static function isAttribuee(int $saeId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM sae_attributions WHERE sae_id = ?");
        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans isAttribuee.");
        }

        $stmt->bind_param("i", $saeId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            throw new DataBaseException("Échec de récupération du résultat dans isAttribuee.");
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        return isset($row['count']) && $row['count'] > 0;
    }

    /**
     * Retrieves a SAE by its ID with client information
     *
     * Performs a LEFT JOIN with the users table to include client details
     * (name, first name, email) in the result.
     *
     * @param int $saeId The ID of the SAE to retrieve
     * @return array<string, mixed>|null The SAE data with client information, or null if not found
     * @throws DataBaseException If database operation fails
     */
    public static function getById(int $saeId): ?array
    {
        $db = Database:: getConnection();

        // JOIN with users table to retrieve client information
        $stmt = $db->prepare("
            SELECT 
                s. id,
                s. titre,
                s.description,
                s.client_id,
                s.date_creation,
                u.nom AS client_nom,
                u.prenom AS client_prenom,
                u. mail AS client_mail
            FROM sae s
            LEFT JOIN users u ON s.client_id = u.id
            WHERE s. id = ?
        ");
        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans getById.");
        }

        $stmt->bind_param("i", $saeId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            throw new DataBaseException("Échec de récupération du résultat dans getById.");
        }

        $sae = $result->fetch_assoc();
        $stmt->close();

        return $sae ?:  null;
    }

    /**
     * Retrieves all SAE with client information
     *
     * Returns all SAE in the system with associated client details,
     * ordered by creation date (newest first).
     *
     * @return array<int, array<string, mixed>> Array of SAE with client information
     * @throws DataBaseException If database operation fails
     */
    public static function getAll(): array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT 
                s.id,
                s. titre,
                s.description,
                s. date_creation,
                u. nom AS client_nom,
                u.prenom AS client_prenom,
                u.mail AS client_mail
            FROM sae s
            JOIN users u ON s. client_id = u.id
            ORDER BY s.date_creation DESC
        ");
        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans getAll.");
        }

        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            throw new DataBaseException("Échec de récupération du résultat dans getAll.");
        }

        $saes = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $saes;
    }

    /**
     * Updates a SAE's title and description
     *
     * Only the client who created the SAE can update it.
     *
     * @param int $clientId The ID of the client who owns the SAE
     * @param int $saeId The ID of the SAE to update
     * @param string $titre The new title
     * @param string $description The new description
     * @return bool True if update was successful
     * @throws DataBaseException If database operation fails
     */
    public static function update(int $clientId, int $saeId, string $titre, string $description): bool
    {
        try {
            Database::checkConnection();

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
            throw new DataBaseException("Impossible de modifier la SAE :   " . $e->getMessage());
        }
    }

    /**
     * Retrieves only the assigned SAE for a specific client
     *
     * Returns SAE that have at least one student assignment,
     * filtered by client ID.
     *
     * @param int $clientId The ID of the client
     * @return array<int, array<string, mixed>> Array of assigned SAE ordered by creation date (newest first)
     * @throws DataBaseException If database operation fails
     */
    public static function getAssignedSaeByClient(int $clientId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
        SELECT s.id, s.titre, s.description, s.date_creation, MAX(sa.github_link) AS github_link
        FROM sae s
        INNER JOIN sae_attributions sa ON s.id = sa.sae_id
        WHERE s.client_id = ? 
        GROUP BY s.id, s.titre, s.description, s.date_creation
        ORDER BY s.date_creation DESC
    ");
        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans getAssignedSaeByClient.");
        }

        $stmt->bind_param("i", $clientId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            throw new DataBaseException("Échec de récupération du résultat dans getAssignedSaeByClient.");
        }

        $saes = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $saes;
    }
}
