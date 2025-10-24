<?php
namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Shared\Exceptions\UnauthorizedSaeUnassignmentException;

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
            $responsableId = (int)$_SESSION['user']['id'];

            try {
                foreach ($students as $studentId) {
                    // Vérifier que le responsable est bien celui qui a attribué la SAE
                    SaeAttribution::checkResponsableOwnership($saeId, $responsableId, (int)$studentId);
                    SaeAttribution::removeFromStudent($saeId, (int)$studentId);
                }

                $_SESSION['success_message'] = "Étudiant(s) retiré(s) avec succès de la SAE.";
            } catch (UnauthorizedSaeUnassignmentException $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        // Rediriger vers la page SAE après la suppression
        header('Location: /sae');
        exit();
    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}