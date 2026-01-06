<?php

namespace Models\Sae;

use Models\Database;
use mysqli;

/**
 * TodoList model
 *
 * Manages task lists (to-do lists) associated with SAE (Situation d'Apprentissage
 * et d'Évaluation).  Allows students and supervisors to create, track, toggle
 * completion status, and delete tasks for a SAE project.
 *
 * @package Models\Sae
 */
class TodoList
{
    /**
     * Adds a task for a SAE
     *
     * Creates a new task entry associated with a SAE.  Tasks are initially
     * marked as incomplete (fait = 0).
     *
     * @param int $saeId The ID of the SAE to add the task to
     * @param string $titre The title/description of the task
     * @return bool True if task was successfully added, false otherwise
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
     * Retrieves all tasks for a specific SAE
     *
     * Returns all tasks associated with a SAE ordered by task ID (creation order).
     *
     * @param int $saeId The ID of the SAE
     * @return array Array of tasks with id, titre, fait (completion status), date_creation
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
     * Toggles a task's completion status
     *
     * Switches a task between completed and incomplete states.
     * If the task is marked as done (fait = 1), it becomes undone (fait = 0), and vice versa.
     *
     * @param int $taskId The ID of the task to toggle
     * @return bool True if toggle was successful, false otherwise
     */
    public static function toggleTask(int $taskId): bool
    {
        $db = Database:: getConnection();
        $stmt = $db->prepare("UPDATE todo_list SET fait = NOT fait WHERE id = ?");
        $stmt->bind_param("i", $taskId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Deletes a task
     *
     * Permanently removes a task from the database.
     *
     * @param int $taskId The ID of the task to delete
     * @return bool True if deletion was successful, false otherwise
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