<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\Sae;
use Shared\Exceptions\DataBaseException;

class UpdateContentSaeController implements ControllerInterface
{
    public const PATH = '/update_sae';

    public function control()
    {
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'client') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $saeId = (int)($_POST['sae_id'] ?? 0);
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $clientId = $_SESSION['user']['id'];

            try {
                Sae::update($clientId, $saeId, $titre, $description);
                $_SESSION['success_message'] = "SAE mise à jour avec succès !";
            } catch (DataBaseException $e) {
                $_SESSION['error_message'] = $e->getMessage();
            } catch (\Exception $e) {
                $_SESSION['error_message'] = "Erreur inattendue : " . $e->getMessage();
            }
        }

        header('Location: /sae');
        exit();
    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}
