<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\Sae;

class CreateSaeController implements ControllerInterface
{
    public const PATH = '/creer_sae';

    public function control()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sae');
            exit();
        }

        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'client') {
            header('HTTP/1.1 403 Forbidden');
            echo "Accès refusé";
            exit();
        }

        $clientId = $_SESSION['user']['id'];
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($titre) || empty($description)) {
            header('Location: /sae?error=missing_fields');
            exit();
        }

        // Insérer dans la base
        Sae::create($clientId, $titre, $description);

        header('Location: /sae?success=sae_created');
        exit();
    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH;
    }
}
