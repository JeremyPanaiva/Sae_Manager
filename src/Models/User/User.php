<?php

namespace Models\User;

use Models\Database;
use Shared\Exceptions\EmailAlreadyExistsException;
use Shared\Exceptions\DataBaseException;

/**
 * User model
 *
 * Manages user data and operations including registration, authentication, profile updates,
 * and account verification.  Handles interactions with the users table in the database.
 *
 * Supported user roles:
 * - etudiant (student)
 * - responsable (supervisor)
 * - client
 *
 * @package Models\User
 */
class User
{
    /**
     * Checks if an email address is already registered
     *
     * @param string $email The email address to check
     * @throws EmailAlreadyExistsException If the email already exists
     * @throws DataBaseException If database connection or query fails
     */
    public function emailExists(string $email): void
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Impossible de se connecter à la base de données.");
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE mail = ?  ");
        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans emailExists.");
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            throw new EmailAlreadyExistsException($email);
        }

        $stmt->close();
    }

    /**
     * Registers a new user
     *
     * Creates a new user account with the provided information.  The account is initially
     * unverified and requires email verification via the verification token.
     *
     * @param string $firstName User's first name
     * @param string $lastName User's last name
     * @param string $email User's email address
     * @param string $password Plain text password (will be hashed)
     * @param string $role User's role (etudiant, responsable, client)
     * @param string $verificationToken Email verification token
     * @throws DataBaseException If database connection or query fails
     */
    public function register(
        string $firstName,
        string $lastName,
        string $email,
        string $password,
        string $role,
        string $verificationToken
    ): void {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Impossible de se connecter à la base de données.");
        }

        // Hash password using bcrypt
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user with verification token and unverified status
        $stmt = $conn->prepare(
            "INSERT INTO users (nom, prenom, mail, mdp, role, verification_token, is_verified) " .
            "VALUES (?, ?, ?, ?, ?, ?, 0)"
        );
        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans register.");
        }

        $stmt->bind_param("ssssss", $lastName, $firstName, $email, $hashedPassword, $role, $verificationToken);

        $stmt->execute();
        $stmt->close();
    }

    /**
     * Retrieves a user by email address
     *
     * @param string $email The email address to search for
     * @return array<string, mixed>|null User data array or null if not found
     * @throws DataBaseException If database connection or query fails
     */
    public function findByEmail(string $email): ?array
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Impossible de se connecter à la base de données.");
        }

        $stmt = $conn->prepare("SELECT id, mdp, nom, prenom, mail, role, is_verified FROM users WHERE mail = ? ");
        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans findByEmail.");
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            throw new DataBaseException("Échec de récupération du résultat dans findByEmail.");
        }

        $user = $result->fetch_assoc() ?: null;

        $stmt->close();

        return $user;
    }

    /**
     * Retrieves all users with a specific role
     *
     * @param string $role The role to filter by
     * @return array<int, array<string, mixed>> Array of users with id, nom, prenom
     * @throws DataBaseException If database connection or query fails
     */
    public static function getAllByRole(string $role): array
    {
        try {
            $db = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Impossible de se connecter à la base de données.");
        }

        $stmt = $db->prepare("SELECT id, nom, prenom FROM users WHERE role = ? AND is_verified = 1");
        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans getAllByRole.");
        }

        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            throw new DataBaseException("Échec de récupération du résultat dans getAllByRole.");
        }

        $users = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $users;
    }

    /**
     * Retrieves all supervisors (responsables)
     *
     * @return array<int, array<string, mixed>> Array of supervisors with id, nom, prenom, mail
     * @throws DataBaseException If database connection or query fails
     */
    public static function getAllResponsables(): array
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Impossible de se connecter à la base de données.");
        }

        $stmt = $conn->prepare("SELECT id, nom, prenom, mail FROM users " .
            "WHERE role = 'Responsable' AND is_verified = 1");
        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans getAllResponsables.");
        }

        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            throw new DataBaseException("Échec de récupération du résultat dans getAllResponsables.");
        }

        $responsables = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $responsables;
    }

    /**
     * Retrieves a paginated list of users with sorting
     *
     * @param int $limit Maximum number of results to return
     * @param int $offset Number of results to skip
     * @param string $sort Column to sort by (nom, prenom, mail, role, date_creation)
     * @param string $order Sort order (ASC or DESC)
     * @return array<int, array<string, mixed>> Array of users
     * @throws DataBaseException If database connection or query fails
     */
    public function getUsersPaginated(
        int $limit,
        int $offset,
        string $sort = 'date_creation',
        string $order = 'ASC'
    ): array {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Impossible de se connecter à la base de données.");
        }

        // Whitelist of allowed sort columns to prevent SQL injection
        $allowedSortColumns = ['nom', 'prenom', 'mail', 'role', 'date_creation'];
        if (!in_array($sort, $allowedSortColumns)) {
            $sort = 'date_creation';
        }

        // Validate sort order
        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'ASC';
        }

        $sql = "SELECT id, nom, prenom, mail, role FROM users 
                                   WHERE is_verified = 1 
                                   ORDER BY $sort $order LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans getUsersPaginated.");
        }

        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            throw new DataBaseException("Échec de récupération du résultat dans getUsersPaginated.");
        }

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        $stmt->close();
        return $users;
    }

    /**
     * Counts the total number of users
     *
     * @return int Total number of users in the database
     * @throws DataBaseException If database connection or query fails
     */
    public function countUsers(): int
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Impossible de se connecter à la base de données.");
        }

        $result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE is_verified = 1");
        if (!($result instanceof \mysqli_result)) {
            throw new DataBaseException("Échec de la requête SQL dans countUsers.");
        }

        $row = $result->fetch_assoc();
        if ($row === null || $row === false) {
            throw new DataBaseException("Échec de récupération du comptage dans countUsers.");
        }

        if (!isset($row['total'])) {
            throw new DataBaseException("Colonne total introuvable dans countUsers.");
        }

        return (int) $row['total'];
    }

    /**
     * Retrieves all students
     *
     * @return array<int, array<string, mixed>> Array of students with id, nom, prenom
     * @throws DataBaseException If database connection or query fails
     */
    public static function getAllStudents(): array
    {
        try {
            $db = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Impossible de se connecter à la base de données.");
        }

        $stmt = $db->prepare("SELECT id, nom, prenom FROM users WHERE LOWER(role) = 'etudiant' AND is_verified = 1");
        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans getAllStudents.");
        }

        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            throw new DataBaseException("Échec de récupération du résultat dans getAllStudents.");
        }

        $students = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $students;
    }

    /**
     * Retrieves a user by their ID
     *
     * @param int $id The user's ID
     * @return array<string, mixed>|null User data or null if not found
     * @throws DataBaseException If database connection or query fails
     */
    public static function getById(int $id): ?array
    {
        try {
            $db = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Impossible de se connecter à la base de données.");
        }

        $stmt = $db->prepare("SELECT id, nom, prenom, mail, role, date_creation FROM users WHERE id = ?    LIMIT 1");
        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans getById.");
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            throw new DataBaseException("Échec de récupération du résultat dans getById.");
        }

        $user = $result->fetch_assoc();
        $stmt->close();
        return $user ?: null;
    }

    /**
     * Deletes a user account
     *
     * Permanently removes a user and all associated data from the database.
     * Related data is automatically deleted via ON DELETE CASCADE constraints.
     *
     * @param int $userId The ID of the user to delete
     * @throws DataBaseException If deletion fails or user not found
     */
    public static function deleteAccount(int $userId): void
    {
        Database::checkConnection();
        $db = Database::getConnection();

        try {
            // Single query is sufficient thanks to ON DELETE CASCADE
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?  ");
            if (!$stmt) {
                throw new DataBaseException("Erreur de préparation SQL dans deleteAccount.");
            }

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
     * Updates a user's email address and requests re-verification
     *
     * Changes the user's email address, generates a new verification token,
     * and sets the account to unverified status.  User must verify the new email.
     *
     * @param int $userId The user's ID
     * @param string $newEmail The new email address
     * @param string $token The new verification token
     * @throws DataBaseException If update fails
     */
    public function updateEmail(int $userId, string $newEmail, string $token): void
    {
        try {
            $conn = Database::getConnection();
            $stmt = $conn->prepare("UPDATE users SET mail = ?, verification_token = ?, is_verified = 0 WHERE id = ?");
            if (!$stmt) {
                throw new DataBaseException("Erreur de préparation SQL updateEmail");
            }

            $stmt->bind_param("ssi", $newEmail, $token, $userId);
            $stmt->execute();
            $stmt->close();
        } catch (\Throwable $e) {
            throw new DataBaseException("Erreur lors de la mise à jour de l'email :    " . $e->getMessage());
        }
    }

    /**
     * Verifies a user account using a verification token
     *
     * Marks the user account as verified if the token is valid and the account
     * is currently unverified.  Removes the token after successful verification.
     *
     * @param string $token The verification token from the email
     * @return bool True if verification was successful, false otherwise
     * @throws DataBaseException If database operation fails
     */
    public function verifyAccount(string $token): bool
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Impossible de se connecter à la base de données.");
        }

        // Check if token exists and account is unverified
        $stmt = $conn->prepare(
            "SELECT id FROM users " .
            "WHERE verification_token = ? AND is_verified = 0"
        );
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

        // Mark account as verified and remove token
        $stmt = $conn->prepare(
            "UPDATE users SET is_verified = 1, verification_token = NULL " .
            "WHERE verification_token = ?"
        );
        if (!$stmt) {
            throw new DataBaseException("Erreur de préparation SQL dans verifyAccount update.");
        }

        $stmt->bind_param("s", $token);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }


    /**
     * Saves the JWT token for a user in the database.
     *
     * @param int    $userId The user's ID
     * @param string $token  The JWT token to save
     * @return void
     */
    public function saveJwtToken(int $userId, string $token): void
    {
        $conn = \Models\Database::getConnection();
        $stmt = $conn->prepare("UPDATE users SET jwt_token = ? WHERE id = ?");
        if ($stmt === false) {
            return;
        }
        $stmt->bind_param("si", $token, $userId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Checks database connection
     *
     * @throws DataBaseException If connection check fails
     */
    public static function checkDatabaseConnection(): void
    {
        Database::checkConnection();
    }
}
