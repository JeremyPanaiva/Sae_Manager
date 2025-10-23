<?php
namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;

class UnassignSaeController implements ControllerInterface
{
    public const PATH = '/unassign_sae';

    public function control()
    {
        session_start();

        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'responsable') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $saeId = (int)($_POST['sae_id'] ?? 0);
            $students = $_POST['etudiants'] ?? [];

            foreach ($students as $studentId) {
                SaeAttribution::removeFromStudent($saeId, (int)$studentId);
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
