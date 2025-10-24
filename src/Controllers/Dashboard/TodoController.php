<?php

namespace Controllers\Dashboard;

use Controllers\ControllerInterface;
use Models\Sae\TodoList;
use Shared\Exceptions\DataBaseException;

class TodoController implements ControllerInterface
{
    public const PATH_ADD = '/todo/add';
    public const PATH_TOGGLE = '/todo/toggle';

    public function control()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        if ($method === 'POST') {
            try {
                if ($path === self::PATH_ADD) {
                    $this->handleAdd();
                    return;
                } elseif ($path === self::PATH_TOGGLE) {
                    $this->handleToggle();
                    return;
                }
            } catch (DataBaseException $e) {
                // On stocke le message dans la session pour le dashboard
                $_SESSION['error_message'] = $e->getMessage();
                header('Location: /dashboard');
                exit();
            }
        }

        // Redirection si URL invalide
        header('Location: /dashboard');
        exit();
    }

    public function handleAdd(): void
    {
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'etudiant') {
            header('Location: /login');
            exit();
        }

        $saeAttributionId = (int)($_POST['sae_attribution_id'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');

        if ($saeAttributionId > 0 && $titre !== '') {
            TodoList::addTask($saeAttributionId, $titre);
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

    public static function support(string $path, string $method): bool
    {
        return in_array($path, [self::PATH_ADD, self::PATH_TOGGLE]) && $method === 'POST';
    }
}
