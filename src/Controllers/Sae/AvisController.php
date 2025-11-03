<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAvis;

class AvisController implements ControllerInterface
{
    public const PATH_ADD = '/sae/avis/add';

    public function control()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        if ($path === self::PATH_ADD && $method === 'POST') {
            $this->handleAdd();
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
        $message = trim($_POST['message'] ?? '');

        if ($saeAttributionId > 0 && $message !== '') {
            SaeAvis::add($saeAttributionId, $role, $message);
        }
    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH_ADD && $method === 'POST';
    }
}
