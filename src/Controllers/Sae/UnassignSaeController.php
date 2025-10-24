<?php
namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Models\User\User;

class UnassignSaeController implements ControllerInterface
{
    public const PATH = '/unassign_sae';

    public function control()
    {
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

        // Récupérer les étudiants déjà assignés à cette SAE
        $assignedStudents = SaeAttribution::getStudentsBySae($saeId);

        // Passer les étudiants assignés à la vue
        $data = [
            'assignedStudents' => $assignedStudents,
        ];

        // Rediriger vers la page SAE après la suppression
        header('Location: /sae');
        exit();
    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}
