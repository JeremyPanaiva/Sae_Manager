<?php
namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAvis;
use Shared\Exceptions\DataBaseException;

class AvisController implements ControllerInterface
{
    public const PATH_ADD = '/sae/avis/add';
    public const PATH_DELETE = '/sae/avis/delete';

    public function control()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        try {
            if ($path === self::PATH_ADD && $method === 'POST') {
                $this->handleAdd();
            } elseif ($path === self::PATH_DELETE && $method === 'POST') {
                $this->handleDelete();
            }
        } catch (DataBaseException $e) {
            // Stocke le message d'erreur dans la session pour le dashboard
            $_SESSION['error_message'] = $e->getMessage();
        } catch (\Exception $e) {
            $_SESSION['error_message'] = "Erreur inattendue : " . $e->getMessage();
        }

        // Redirection vers le dashboard
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

        $avisId = (int)($_POST['avis_id'] ?? 0);
        if ($avisId > 0) {
            SaeAvis::delete($avisId);
        }
    }

    public static function support(string $path, string $method): bool
    {
        return ($path === self::PATH_ADD && $method === 'POST')
            || ($path === self::PATH_DELETE && $method === 'POST');
    }
}
