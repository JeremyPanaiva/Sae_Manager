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
    public function register(string $firstName, string $lastName, string $email, string $password, string $role): void
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Unable to connect to the database.");
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // ✅ On ajoute la colonne "role" dans la requête
        $stmt = $conn->prepare("INSERT INTO users (nom, prenom, mail, mdp, role) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in register.");
        }

        // ✅ On lie les 5 paramètres
        $stmt->bind_param("sssss", $lastName, $firstName, $email, $hashedPassword, $role);

        $stmt->execute();
        $stmt->close();
        // ❌ Ne pas fermer $conn ici
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

        $stmt = $conn->prepare("SELECT id, mdp, nom, prenom, mail, role FROM users WHERE mail = ?");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in findByEmail.");
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $user = $result->fetch_assoc() ?: null;

        $stmt->close();
        // ❌ Ne pas fermer $conn ici

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
        $stmt = $db->prepare("SELECT id, nom, prenom, mail, role FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user ?: null;
    }
}