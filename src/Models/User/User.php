<?php

namespace Models\User;

use Models\Database;
use Shared\Exceptions\EmailAlreadyExistsException;
use Shared\Exceptions\DataBaseException;

class User
{
    /**
     * Vérifie si un email existe déjà
     *
     * @throws EmailAlreadyExistsException
     * @throws DataBaseException
     */
    public function emailExists(string $email): void
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Unable to connect to the database.");
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE mail = ?");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in emailExists.");
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            throw new EmailAlreadyExistsException($email);
        }

        $stmt->close();
        // ❌ Ne pas fermer $conn ici
    }

    /**
     * Enregistre un nouvel utilisateur
     *
     * @throws DataBaseException
     */
    public function register(string $firstName, string $lastName, string $email, string $password, string $role, string $verificationToken): void
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Unable to connect to the database.");
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // On ajoute la colonne "role" et "verification_token" dans la requête
        $stmt = $conn->prepare("INSERT INTO users (nom, prenom, mail, mdp, role, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, ?, 0)");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in register.");
        }

        // On lie les 6 paramètres
        $stmt->bind_param("ssssss", $lastName, $firstName, $email, $hashedPassword, $role, $verificationToken);

        $stmt->execute();
        $stmt->close();
        // Ne pas fermer $conn ici
    }

    /**
     * Récupère un utilisateur par email
     *
     * @throws DataBaseException
     */
    public function findByEmail(string $email): ?array
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Unable to connect to the database.");
        }

        $stmt = $conn->prepare("SELECT id, mdp, nom, prenom, mail, role, is_verified FROM users WHERE mail = ?");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in findByEmail.");
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $user = $result->fetch_assoc() ?: null;

        $stmt->close();
        // Ne pas fermer $conn ici

        return $user;
    }

    /**
     * Récupère une liste d'utilisateurs paginée
     *
     * @throws DataBaseException
     */

    public static function getAllByRole(string $role): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, nom, prenom FROM users WHERE role = ?");
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $users;
    }

    /**
     * Récupère tous les responsables
     *
     * @return array Liste des responsables avec leurs informations
     * @throws DataBaseException
     */
    public static function getAllResponsables(): array
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Unable to connect to the database.");
        }

        $stmt = $conn->prepare("SELECT id, nom, prenom, mail FROM users WHERE role = 'Responsable'");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in getAllResponsables.");
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $responsables = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $responsables;
    }

    public function getUsersPaginated(int $limit, int $offset): array
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Unable to connect to the database.");
        }

        $stmt = $conn->prepare("SELECT id, nom, prenom, mail FROM users ORDER BY date_creation ASC LIMIT ? OFFSET ?");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in getUsersPaginated.");
        }

        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        $stmt->close();
        return $users;
    }

    /**
     * Compte le nombre total d'utilisateurs
     *
     * @throws DataBaseException
     */
    public function countUsers(): int
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Unable to connect to the database.");
        }

        $result = $conn->query("SELECT COUNT(*) AS total FROM users");
        if (!$result) {
            throw new DataBaseException("SQL query failed in countUsers.");
        }

        $count = $result->fetch_assoc()['total'];
        return $count;
    }

    public static function getAllStudents(): array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT id, nom, prenom FROM users WHERE LOWER(role) = 'etudiant'");
        $stmt->execute();
        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $students;
    }

    /**
     * Récupère un utilisateur par son ID
     */
    public static function getById(int $id): ?array
    {
        $db = \Models\Database::getConnection();
        $stmt = $db->prepare("SELECT id, nom, prenom, mail, role, date_creation FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user ?: null;
    }

    public static function checkDatabaseConnection(): void
    {
        try {
            $db = \Models\Database::getConnection();
            if (!$db->ping()) {
                throw new \Shared\Exceptions\DataBaseException(
                    "Unable to connect to the database please contact sae-manager@alwaysdata.net"
                );
            }
        } catch (\Throwable $e) {
            // 🔹 Message user-friendly pour toutes les exceptions
            throw new \Shared\Exceptions\DataBaseException(
                "Unable to connect to the database please contact sae-manager@alwaysdata.net"
            );
        }
    }


    public static function deleteAccount(int $userId): void
    {
        self::checkDatabaseConnection();
        $db = Database::getConnection();

        try {
            // ✅ Une seule requête suffit grâce à ON DELETE CASCADE !
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            if ($stmt->affected_rows === 0) {
                throw new \Exception("Aucun utilisateur trouvé avec cet ID.");
            }

            $stmt->close();

        } catch (\Exception $e) {
            error_log("Erreur User::deleteAccount : " . $e->getMessage());
            throw new DataBaseException("Impossible de supprimer le compte.");
        }
    }

    /**
     * Met à jour l'email et demande une nouvelle vérification
     */
    public function updateEmail(int $userId, string $newEmail, string $token): void
    {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare("UPDATE users SET mail = ?, verification_token = ?, is_verified = 0 WHERE id = ?");
            if (!$stmt)
                throw new DataBaseException("Erreur de préparation SQL updateEmail");

            $stmt->bind_param("ssi", $newEmail, $token, $userId);
            $stmt->execute();
            $stmt->close();
        } catch (\Throwable $e) {
            throw new DataBaseException("Erreur lors de la mise à jour de l'email : " . $e->getMessage());
        }
    }

    /**
     * Vérifie le compte de l'utilisateur via le token
     *
     * @return bool True si vérification réussie, False sinon
     * @throws DataBaseException
     */
    public function verifyAccount(string $token): bool
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Unable to connect to the database.");
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt->close();
            return false;
        }

        $stmt->close();

        $stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in verifyAccount update.");
        }

        $stmt->bind_param("s", $token);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }





}