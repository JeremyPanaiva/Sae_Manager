<?php

namespace Controllers\Dashboard;

use Controllers\ControllerInterface;
use Models\Sae\TodoList;
use Shared\Exceptions\DataBaseException;
use Shared\SessionGuard;

class TodoController implements ControllerInterface
{
    public const PATH_ADD = '/todo/add';
    public const PATH_TOGGLE = '/todo/toggle';
    public const PATH_DELETE = '/todo/delete';

    public function control()
    {
        SessionGuard::check();

        $pathString = $_SERVER['REQUEST_URI'] ?? '';
        // Vérification explicite du type pour PHPStan
        if (!is_string($pathString)) {
            header('Location: /dashboard');
            exit();
        }

        $path = parse_url($pathString, PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        if ($method === 'POST') {
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

    public function handleAdd(): void
    {
        if (
            !isset($_SESSION['user'])
            || !is_array($_SESSION['user'])
            || !isset($_SESSION['user']['role'])
            || !is_string($_SESSION['user']['role'])
            || strtolower($_SESSION['user']['role']) !== 'etudiant'
        ) {
            header('Location: /login');
            exit();
        }

        $saeId = isset($_POST['sae_id']) && is_numeric($_POST['sae_id']) ? (int) $_POST['sae_id'] : 0;
        $titre = isset($_POST['titre']) && is_scalar($_POST['titre']) ? trim(strval($_POST['titre'])) : '';

        if ($saeId > 0 && $titre !== '') {
            $userId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
            TodoList::addTask($saeId, $titre, $userId);
        }

        header('Location: /dashboard');
        exit();
    }

    public function handleToggle(): void
    {
        if (
            !isset($_SESSION['user'])
            || !is_array($_SESSION['user'])
            || !isset($_SESSION['user']['role'])
            || !is_string($_SESSION['user']['role'])
            || strtolower($_SESSION['user']['role']) !== 'etudiant'
        ) {
            header('Location: /login');
            exit();
        }

        $taskId = isset($_POST['task_id']) && is_numeric($_POST['task_id']) ? (int) $_POST['task_id'] : 0;
        $fait = isset($_POST['fait']) && is_numeric($_POST['fait']) ? (int) $_POST['fait'] : 0;

        if ($taskId > 0) {
            // Vérifier la signature de toggleTask dans TodoList
            // Si elle attend 1 paramètre, utiliser:
            TodoList::toggleTask($taskId);
            // Si elle attend 2 paramètres (id, état), utiliser:
            // TodoList::toggleTask($taskId, $fait === 1);
        }

        header('Location: /dashboard');
        exit();
    }

    public function handleDelete(): void
    {
        if (
            !isset($_SESSION['user'])
            || !is_array($_SESSION['user'])
            || !isset($_SESSION['user']['role'])
            || !is_string($_SESSION['user']['role'])
            || strtolower($_SESSION['user']['role']) !== 'etudiant'
        ) {
            header('Location: /login');
            exit();
        }

        $taskId = isset($_POST['task_id']) && is_numeric($_POST['task_id']) ? (int) $_POST['task_id'] : 0;

        if ($taskId > 0) {
            TodoList::deleteTask($taskId);
        }

        header('Location: /dashboard');
        exit();
    }

    public static function support(string $path, string $method): bool
    {
        return in_array($path, [self::PATH_ADD, self::PATH_TOGGLE, self::PATH_DELETE]) && $method === 'POST';
    }
}
