<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAvis;

class AvisController implements ControllerInterface
{
    public const PATH_ADD = '/sae/avis/add';
    public const PATH_DELETE = '/sae/avis/delete';

    public static function support(string $path, string $method): bool
    {
        if ($method === 'POST') {
            return in_array($path, [self::PATH_ADD, self::PATH_DELETE]);
        }
        return false;
    }

    public function control(): void
    {
        $path = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];

        if ($path === self::PATH_ADD && $method === 'POST') {
            $this->handleAdd();
        } elseif ($path === self::PATH_DELETE && $method === 'POST') {
            $this->handleDelete();
        }

        header("Location: /dashboard");
        exit();
    }

    private function handleAdd(): void
    {
        if (!isset($_SESSION['user'])) {
            header("Location: /login");
            exit();
        }

        $role = strtolower($_SESSION['user']['role']);
        if (!in_array($role, ['client', 'responsable'])) {
            header("Location: /dashboard");
            exit();
        }

        $saeAttributionId = (int)($_POST['sae_attribution_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if ($saeAttributionId > 0 && $userId > 0 && $message !== '') {
            SaeAvis::add($saeAttributionId, $userId, $message);
        }
    }

    private function handleDelete(): void
    {
        if (!isset($_SESSION['user'])) {
            header("Location: /login");
            exit();
        }

        $role = strtolower($_SESSION['user']['role']);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $avisId = (int)($_POST['avis_id'] ?? 0);

        // Exemple : seuls le responsable, client ou l'auteur peuvent supprimer
        if ($avisId > 0) {
            SaeAvis::delete($avisId, $userId, $role);
        }
    }
}