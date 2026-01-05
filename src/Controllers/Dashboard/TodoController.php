<?php

namespace Controllers\Dashboard;

use Controllers\ControllerInterface;
use Models\Sae\TodoList;
use Shared\Exceptions\DataBaseException;

class TodoController implements ControllerInterface
{
    public const PATH_ADD = '/todo/add';
    public const PATH_TOGGLE = '/todo/toggle';
    public const PATH_DELETE = '/todo/delete';

    public function control()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        if ($method === 'POST') {
            try {
                if ($path === self::PATH_ADD) {
                    $this->handleAdd();
                    return;
                } elseif ($path === self:: PATH_TOGGLE) {
                    $this->handleToggle();
                    return;
                } elseif ($path === self:: PATH_DELETE) {
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

    public function handleAdd(): void
    {
        if (! isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'etudiant') {
            header('Location:  /login');
            exit();
        }

        $saeId = (int)($_POST['sae_id'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');

        if ($saeId > 0 && $titre !== '') {
            TodoList::addTask($saeId, $titre);
        }

        header('Location: /dashboard');
        exit();
    }

    public function handleToggle(): void
    {
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'etudiant') {
            header('Location: /login');
            exit();
        }

        $taskId = (int)($_POST['task_id'] ?? 0);
        $fait = (int)($_POST['fait'] ?? 0);

        if ($taskId > 0) {
            TodoList::toggleTask($taskId, $fait === 1);
        }

        header('Location: /dashboard');
        exit();
    }

    // ✅ NOUVELLE MÉTHODE : Gérer la suppression
    public function handleDelete(): void
    {
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'etudiant') {
            header('Location: /login');
            exit();
        }

        $taskId = (int)($_POST['task_id'] ?? 0);

        if ($taskId > 0) {
            TodoList::deleteTask($taskId);
        }

        header('Location:  /dashboard');
        exit();
    }

    public static function support(string $path, string $method): bool
    {
        return in_array($path, [self::PATH_ADD, self::PATH_TOGGLE, self::PATH_DELETE]) && $method === 'POST';
    }
}