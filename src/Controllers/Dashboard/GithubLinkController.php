<?php

namespace Controllers\Dashboard;

use Controllers\ControllerInterface;
use Models\Sae\TodoList;
use Shared\Exceptions\DataBaseException;

class GithubLinkController implements ControllerInterface
{
    public const PATH_ADD = '/github/add';
    private const GITHUB_PREFIX = '[GITHUB_LINK]';

    public function control()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        if ($method === 'POST' && $path === self::PATH_ADD) {
            try {
                $this->handleAddOrUpdate();
                return;
            } catch (DataBaseException $e) {
                $_SESSION['error_message'] = $e->getMessage();
                header('Location:  /dashboard');
                exit();
            }
        }

        header('Location: /dashboard');
        exit();
    }

    public function handleAddOrUpdate(): void
    {
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'etudiant') {
            header('Location: /login');
            exit();
        }

        $saeAttributionId = (int)($_POST['sae_attribution_id'] ?? 0);
        $githubLink = trim($_POST['github_link'] ?? '');

        if ($saeAttributionId <= 0) {
            header('Location: /dashboard');
            exit();
        }

        // Validation du lien GitHub
        if ($githubLink !== '' && !preg_match('/^https?:\/\/(www\.)?github\.com\/.+/i', $githubLink)) {
            $_SESSION['error_message'] = "Veuillez entrer un lien GitHub valide (ex: https://github.com/user/repo)";
            header('Location: /dashboard');
            exit();
        }

        // Supprimer l'ancien lien GitHub s'il existe
        $existingTodos = TodoList::getBySaeAttribution($saeAttributionId);
        foreach ($existingTodos as $todo) {
            if (strpos($todo['titre'], self::GITHUB_PREFIX) === 0) {
                TodoList::deleteTask($todo['id']);
            }
        }

        // Ajouter le nouveau lien si non vide
        if ($githubLink !== '') {
            $titre = self::GITHUB_PREFIX .  ' ' . $githubLink;
            TodoList::addTask($saeAttributionId, $titre);
        }

        header('Location: /dashboard');
        exit();
    }

    /**
     * Extrait le lien GitHub depuis la liste des todos
     */
    public static function extractGithubLink(array $todos): ?string
    {
        foreach ($todos as $todo) {
            if (strpos($todo['titre'], self::GITHUB_PREFIX) === 0) {
                return trim(str_replace(self::GITHUB_PREFIX, '', $todo['titre']));
            }
        }
        return null;
    }

    /**
     * Filtre les todos pour retirer le lien GitHub
     */
    public static function filterOutGithubLink(array $todos): array
    {
        return array_filter($todos, function($todo) {
            return strpos($todo['titre'], self:: GITHUB_PREFIX) !== 0;
        });
    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH_ADD && $method === 'POST';
    }
}