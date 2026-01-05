<?php
namespace Models\Sae;

use Models\Database;
use mysqli;

class TodoList
{
    /**
     * Ajouter une tâche pour une SAE
     */
    public static function addTask(int $saeId, string $titre): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO todo_list (sae_id, titre, fait) VALUES (?, ?, 0)");
        $stmt->bind_param("is", $saeId, $titre);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Récupérer toutes les tâches d'une SAE
     */
    public static function getBySae(int $saeId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT id, titre, fait, date_creation 
            FROM todo_list 
            WHERE sae_id = ?  
            ORDER BY id ASC
        ");
        $stmt->bind_param("i", $saeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $todos = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $todos;
    }

    /**
     * Marquer une tâche comme faite/non faite
     */
    public static function toggleTask(int $taskId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE todo_list SET fait = NOT fait WHERE id = ?");
        $stmt->bind_param("i", $taskId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Supprimer une tâche
     */
    public static function deleteTask(int $taskId): bool
    {
        $db = Database:: getConnection();
        $stmt = $db->prepare("DELETE FROM todo_list WHERE id = ?");
        $stmt->bind_param("i", $taskId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}