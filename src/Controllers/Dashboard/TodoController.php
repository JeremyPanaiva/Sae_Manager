<?php

namespace Controllers\Dashboard;

use Controllers\ControllerInterface;
use Models\Sae\TodoList;
use Shared\Exceptions\DataBaseException;

/**
 * Todo controller
 *
 * Handles todo list operations (add, toggle, delete) for students within their assigned SAE.
 * All operations require student authentication and use POST requests.
 *
 * @package Controllers\Dashboard
 */
class TodoController implements ControllerInterface
{
    /**
     * Route path for adding a todo task
     *
     * @var string
     */
    public const PATH_ADD = '/todo/add';

    /**
     * Route path for toggling a todo task completion status
     *
     * @var string
     */
    public const PATH_TOGGLE = '/todo/toggle';

    /**
     * Route path for deleting a todo task
     *
     * @var string
     */
    public const PATH_DELETE = '/todo/delete';

    /**
     * Main controller method
     *
     * Routes POST requests to appropriate handler methods based on the URL path.
     * Handles database exceptions and redirects to dashboard.
     *
     * @return void
     */
    public function control()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        if ($method === 'POST') {
            try {
                // Route to appropriate handler based on path
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
                // Store error message in session and redirect
                $_SESSION['error_message'] = $e->getMessage();
                header('Location: /dashboard');
                exit();
            }
        }

        // Default redirect to dashboard
        header('Location: /dashboard');
        exit();
    }

    /**
     * Handles adding a new todo task
     *
     * Validates student authentication and creates a new todo task for the specified SAE.
     * Requires valid SAE ID and task title.
     *
     * @return void
     */
    public function handleAdd(): void
    {
        // Verify user is authenticated as a student
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'etudiant') {
            header('Location: /login');
            exit();
        }

        // Extract and validate POST data
        $saeId = (int)($_POST['sae_id'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');

        // Create task if valid data provided
        if ($saeId > 0 && $titre !== '') {
            TodoList::addTask($saeId, $titre);
        }

        header('Location: /dashboard');
        exit();
    }

    /**
     * Handles toggling a todo task completion status
     *
     * Validates student authentication and toggles the completion status of a task.
     * Requires valid task ID and completion status.
     *
     * @return void
     */
    public function handleToggle(): void
    {
        // Verify user is authenticated as a student
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'etudiant') {
            header('Location: /login');
            exit();
        }

        // Extract POST data
        $taskId = (int)($_POST['task_id'] ?? 0);
        $fait = (int)($_POST['fait'] ?? 0);

        // Toggle task status if valid task ID provided
        if ($taskId > 0) {
            TodoList::toggleTask($taskId, $fait === 1);
        }

        header('Location: /dashboard');
        exit();
    }

    /**
     * Handles deleting a todo task
     *
     * Validates student authentication and permanently removes a task from the database.
     * Requires valid task ID.
     *
     * @return void
     */
    public function handleDelete(): void
    {
        // Verify user is authenticated as a student
        if (! isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'etudiant') {
            header('Location:  /login');
            exit();
        }

        // Extract task ID from POST data
        $taskId = (int)($_POST['task_id'] ?? 0);

        // Delete task if valid ID provided
        if ($taskId > 0) {
            TodoList::deleteTask($taskId);
        }

        header('Location:  /dashboard');
        exit();
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path matches one of the todo routes and method is POST
     */
    public static function support(string $path, string $method): bool
    {
        return in_array($path, [self::PATH_ADD, self::PATH_TOGGLE, self::PATH_DELETE]) && $method === 'POST';
    }
}
