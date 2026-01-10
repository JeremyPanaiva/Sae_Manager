<?php

namespace Models\User;

use Models\Database;
use Shared\Exceptions\DataBaseException;

/**
 * Password Reset Token model
 *
 * Manages password reset tokens for user password recovery.    Handles token generation,
 * validation, and expiration.  Tokens are valid for 1 hour and can only be used once.
 *
 * @package Models\User
 */
class PasswordResetToken
{
    /**
     * Generates and saves a password reset token
     *
     * Creates a unique token for password reset, stores it in the database with a
     * 1-hour expiration time.    Removes any existing tokens for the user to ensure
     * only one active token exists at a time.
     *
     * @param string $email The email address of the user requesting password reset
     * @return string The generated token
     * @throws DataBaseException If database connection fails, user is not found, or query fails
     */
    public function createToken(string $email): string
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Unable to connect to the database.");
        }

        // Retrieve user ID by email
        $stmt = $conn->prepare("SELECT id FROM users WHERE mail = ? ");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in createToken (get user id).");
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            $stmt->close();
            throw new DataBaseException("Failed to get result in createToken.");
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            throw new DataBaseException("User not found for email: " .  $email);
        }

        $userId = $user['id'];

        // Generate unique token (64 character hex string)
        $token = bin2hex(random_bytes(32));

        // Delete old tokens for this user to ensure only one active token
        $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in createToken (delete).");
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // Insert new token with 1-hour expiration
        $stmt = $conn->prepare(
            "INSERT INTO password_reset_tokens (user_id, token, expiry, used) " .
            "VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR), 0)"
        );
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in createToken (insert).");
        }
        $stmt->bind_param("is", $userId, $token);
        $stmt->execute();
        $stmt->close();

        return $token;
    }

    /**
     * Validates a password reset token
     *
     * Checks if the token exists, has not expired, and has not been used.
     * Returns the associated email address if valid, null otherwise.
     *
     * @param string $token The token to validate
     * @return string|null The email address associated with the token, or null if invalid
     * @throws DataBaseException If database connection or query fails
     */
    public function validateToken(string $token): ?string
    {
        try {
            $conn = Database::getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Unable to connect to the database.");
        }

        // Check token is valid, not expired, and not used
        $stmt = $conn->prepare("SELECT u. mail FROM password_reset_tokens prt 
                                JOIN users u ON prt.user_id = u.id 
                                WHERE prt.token = ? AND prt.expiry > UTC_TIMESTAMP() AND prt.used = 0");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in validateToken.");
        }
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            $stmt->close();
            throw new DataBaseException("Failed to get result in validateToken.");
        }

        $row = $result->fetch_assoc();
        $stmt->close();

        // Cast to string to match return type
        return $row && isset($row['mail']) ? (string)$row['mail'] : null;
    }

    /**
     * Marks a token as used after password reset
     *
     * Instead of deleting the token, marks it as used to prevent reuse while
     * maintaining an audit trail.   This ensures tokens can only be used once.
     *
     * @param string $token The token to mark as used
     * @throws DataBaseException If database connection or query fails
     */
    public function deleteToken(string $token): void
    {
        try {
            $conn = Database:: getConnection();
        } catch (\Throwable $e) {
            throw new DataBaseException("Unable to connect to the database.");
        }

        // Mark token as used instead of deleting for audit trail
        $stmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
        if (!$stmt) {
            throw new DataBaseException("SQL prepare failed in deleteToken.");
        }
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->close();
    }
}
