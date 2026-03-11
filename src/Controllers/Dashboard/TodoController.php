<?php

namespace Controllers\Dashboard;

use Controllers\ControllerInterface;
use Models\Sae\TodoList;
use Shared\Exceptions\DataBaseException;
use Shared\SessionGuard;
use Shared\CsrfGuard;
use Shared\RoleGuard;

/**
 * TodoController
 *
 * Handles todo task actions (add, toggle, delete) for students.
 * Role verification is delegated to RoleGuard.
 *
 * @package Controllers\Dashboard
 */
class TodoController implements ControllerInterface
{
    public const PATH_ADD    = '/todo/add';
    public const PATH_TOGGLE = '/todo/toggle';
    public const PATH_DELETE = '/todo/delete';

    /**
     * Main controller method.
     *
     * @return void
     */
    public function control(): void
    {
        SessionGuard::check();

        $pathString = $_SERVER['REQUEST_URI'] ?? '';
        if (!is_string($pathString)) {
            header('Location: /dashboard');
            exit();
        }

        $path   = parse_url($pathString, PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        if ($method === 'POST') {
            if (!CsrfGuard::validate()) {
                http_response_code(403);
                die('Requête invalide (CSRF).');
            }

            try {
                if ($path === self::PATH_ADD) {
                    $this->handleAdd();
                    return;
                } elseif ($path === self::PATH_TOGGLE) {
                    $this->handleToggle();
                    return;
                } elseif ($path === self::PATH_DELETE) {
                    $this->handleDelete();
                    return;
                }
            } catch (DataBaseException $e) {
                $_SESSION['error_message'] = $e->getMessage();
                header('Location: /dashboard');
                exit();
            }
        }

        header('Location: /dashboard');
        exit();
    }

    /**
     * Handles adding a new task.
     *
     * @return void
     */
    public function handleAdd(): void
    {
        RoleGuard::requireRole('etudiant');

        $user   = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : [];
        $saeId  = isset($_POST['sae_id']) && is_numeric($_POST['sae_id']) ? (int) $_POST['sae_id'] : 0;
        $titre  = isset($_POST['titre'])  && is_scalar($_POST['titre'])   ? trim(strval($_POST['titre'])) : '';

        if ($saeId > 0 && $titre !== '') {
            $userId = isset($user['id']) && is_numeric($user['id']) ? (int) $user['id'] : null;
            TodoList::addTask($saeId, $titre, $userId);
        }

        header('Location: /dashboard');
        exit();
    }

    /**
     * Handles toggling a task's completion status.
     *
     * @return void
     */
    public function handleToggle(): void
    {
        RoleGuard::requireRole('etudiant');

        $taskId = isset($_POST['task_id']) && is_numeric($_POST['task_id']) ? (int) $_POST['task_id'] : 0;

        if ($taskId > 0) {
            TodoList::toggleTask($taskId);
        }

        header('Location: /dashboard');
        exit();
    }

    /**
     * Handles deleting a task.
     *
     * @return void
     */
    public function handleDelete(): void
    {
        RoleGuard::requireRole('etudiant');

        $taskId = isset($_POST['task_id']) && is_numeric($_POST['task_id']) ? (int) $_POST['task_id'] : 0;

        if ($taskId > 0) {
            TodoList::deleteTask($taskId);
        }

        header('Location: /dashboard');
        exit();
    }

    /**
     * @param string $path
     * @param string $method
     * @return bool
     */
    public static function support(string $path, string $method): bool
    {
        return in_array($path, [self::PATH_ADD, self::PATH_TOGGLE, self::PATH_DELETE]) && $method === 'POST';
    }
}
