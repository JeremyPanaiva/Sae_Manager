<?php

namespace Models\User;

use Models\Database;

/**
 * Audit Log Model
 *
 * This model handles the insertion of audit trails and security events
 * into the centralized 'logs' database table.
 * It is designed to be used by Controllers to record actions such as
 * login attempts, logouts, or contact form submissions.
 *
 * @package Models
 */
class Log
{
    /**
     * Creates a new audit log entry in the database.
     *
     * This method uses a try-catch block to ensure that a logging failure
     * (e.g., database error) does not interrupt the main application flow.
     *
     * @param int|null $userId      The ID of the user performing the action (null if guest/unknown).
     * @param string   $action      The action code (e.g., 'CONNEXION', 'LOGOUT', 'CONTACT').
     * @param string   $table       The name of the table concerned (e.g., 'users', 'sae').
     * @param int      $elementId   The ID of the specific element concerned (0 if not applicable).
     * @param string   $details     Human-readable details describing the event.
     *
     * @return void
     */
    public function create(?int $userId, string $action, string $table, int $elementId, string $details): void
    {
        try {
            $db = Database::getConnection();

            // Prepare the SQL statement to prevent SQL injection
            $stmt = $db->prepare(
                "INSERT INTO logs (user_id, action, table_concernee, element_id, details) 
                 VALUES (?, ?, ?, ?, ?)"
            );

            if ($stmt) {
                // Bind parameters:
                // 'i' = integer (user_id - nullable)
                // 's' = string  (action)
                // 's' = string  (table_concernee)
                // 'i' = integer (element_id)
                // 's' = string  (details)
                $stmt->bind_param('issis', $userId, $action, $table, $elementId, $details);

                $stmt->execute();
                $stmt->close();
            }
        } catch (\Throwable $e) {
            // Silently catch errors.
            // We do not want to block the user if the logging system fails.
            // Optionally log the error to the server's error log file.
            error_log("Audit Log Error: " . $e->getMessage());
        }
    }
}
