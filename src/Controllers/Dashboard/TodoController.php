<?php
namespace Controllers\Dashboard;

use Controllers\ControllerInterface;
use Models\Sae\TodoList;

class TodoController implements ControllerInterface
{
    public const PATH_ADD = '/todo/add';
    public const PATH_TOGGLE = '/todo/toggle';

    public function control()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        if ($method === 'POST') {
            if ($path === self::PATH_ADD) {
                error_log(">>> handleAdd exécuté");
                $this->handleAdd();
                return;
            } elseif ($path === self::PATH_TOGGLE) {
                error_log(">>> handleToggle exécuté");
                $this->handleToggle();
                return;
            }
        }

        // Si on arrive ici, c’est un appel direct à /todo ou autre → redirection
        header('Location: /dashboard');
        exit();
    }



    public function handleAdd(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard');
            exit();
        }

        // Vérification du rôle étudiant
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'etudiant') {
            header('Location: /login');
            exit();
        }

        $saeAttributionId = (int)($_POST['sae_attribution_id'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');

        if ($saeAttributionId > 0 && $titre !== '') {
            TodoList::addTask($saeAttributionId, $titre);
        }

        error_log(">>> handleAdd() exécuté avec POST = " . print_r($_POST, true));

        header('Location: /dashboard');
        exit();
    }

    public function handleToggle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard');
            exit();
        }

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
