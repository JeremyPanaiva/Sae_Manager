<?php

namespace Models\User;

use Models\Database;
use Shared\Exceptions\DataBaseException;

class PasswordResetToken
{
    /**
     * Génère et sauvegarde un token de réinitialisation
     *
     * @throws DataBaseException
     */
    public function createToken(string $email): string
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Unable to connect to the database.");
        }

        // Récupérer l'ID de l'utilisateur par email
        $stmt = $conn->prepare("SELECT id FROM users WHERE mail = ?");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in createToken (get user id).");
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            throw new DataBaseException("User not found for email: " . $email);
        }

        $userId = $user['id'];

        // Générer un token unique
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day')); // Token valide 1 jour

        // Supprimer les anciens tokens pour cet utilisateur
        $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in createToken (delete).");
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // Insérer le nouveau token
        $stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expiry, used) VALUES (?, ?, ?, 0)");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in createToken (insert).");
        }
        $stmt->bind_param("iss", $userId, $token, $expiresAt);
        $stmt->execute();
        $stmt->close();

        return $token;
    }

    /**
     * Vérifie si un token est valide
     *
     * @throws DataBaseException
     */
    public function validateToken(string $token): ?string
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Unable to connect to the database.");
        }

        $stmt = $conn->prepare("SELECT u.mail FROM password_reset_tokens prt 
                                JOIN users u ON prt.user_id = u.id 
                                WHERE prt.token = ? AND prt.expiry > UTC_TIMESTAMP() AND prt.used = 0");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in validateToken.");
        }
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? $row['mail'] : null;
    }

    /**
     * Supprime un token après utilisation
     *
     * @throws DataBaseException
     */
    public function deleteToken(string $token): void
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Unable to connect to the database.");
        }

        // Marquer le token comme utilisé au lieu de le supprimer
        $stmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in deleteToken.");
        }
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();
    }
}
