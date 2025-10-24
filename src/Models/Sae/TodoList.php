<?php
namespace Models\Sae;

use Models\Database;
use mysqli_sql_exception;
use Shared\Exceptions\DataBaseException;

class TodoList
{
    public static function addTask(int $saeAttributionId, string $titre): void
    {
        self::checkDatabaseConnection();
        $db = Database::getConnection();

        try {
            $stmt = $db->prepare("INSERT INTO todo_list (sae_attribution_id, titre, fait) VALUES (?, ?, 0)");
            $stmt->bind_param("is", $saeAttributionId, $titre);
            $stmt->execute();
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Erreur TodoList::addTask : " . $e->getMessage());
        }
    }

    public static function toggleTask(int $taskId, bool $fait): void
    {
        self::checkDatabaseConnection();
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE todo_list SET fait = ? WHERE id = ?");
        $faitInt = $fait ? 1 : 0;
        $stmt->bind_param("ii", $faitInt, $taskId);
        $stmt->execute();
        $stmt->close();
    }

    public static function getBySaeAttribution(int $saeAttributionId): array
    {
        $db = Database::getConnection();
        // ✅ Table corrigée
        $stmt = $db->prepare("SELECT id, titre, fait FROM todo_list WHERE sae_attribution_id = ?");
        $stmt->bind_param("i", $saeAttributionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $todos = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $todos;
    }

    public static function getBySae(int $saeId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
        SELECT t.id, t.titre, t.fait, sa.student_id
        FROM todo_list t
        JOIN sae_attributions sa ON t.sae_attribution_id = sa.id
        WHERE sa.sae_id = ?
        ORDER BY t.id ASC
    ");
        $stmt->bind_param("i", $saeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $todos = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $todos;
    }

    public static function checkDatabaseConnection(): void
    {
        try {
            $db = Database::getConnection();
            // simple ping pour tester la connexion
            if (!$db->ping()) {
                throw new DataBaseException("Unable to connect to the database");
            }
        } catch (\Exception $e) {
            throw new DataBaseException("Unable to connect to the database");
        }
    }

}
