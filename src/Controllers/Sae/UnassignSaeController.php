<?php
namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Shared\Exceptions\UnauthorizedSaeUnassignmentException;
use Shared\Exceptions\DataBaseException;

class UnassignSaeController implements ControllerInterface
{
    public const PATH = '/unassign_sae';

    public function control()
    {
        // Vérifie que l'utilisateur est connecté et est un responsable
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'responsable') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $saeId = (int)($_POST['sae_id'] ?? 0);
            $students = $_POST['etudiants'] ?? [];
            $responsableId = (int)$_SESSION['user']['id'];

            try {
                // Vérifie la connexion à la base de données
                SaeAttribution::checkDatabaseConnection();

                foreach ($students as $studentId) {
                    // Vérifie que le responsable est bien celui qui a attribué la SAE
                    SaeAttribution::checkResponsableOwnership($saeId, $responsableId, (int)$studentId);

                    // Supprime l'attribution de la SAE à l'étudiant
                    // Les entrées associées dans todo_list et sae_avis seront supprimées automatiquement grâce à ON DELETE CASCADE
                    SaeAttribution::removeFromStudent($saeId, (int)$studentId);
                }

                $_SESSION['success_message'] = "Étudiant(s) retiré(s) avec succès de la SAE.";
            } catch (UnauthorizedSaeUnassignmentException $e) {
                $_SESSION['error_message'] = $e->getMessage();
            } catch (DataBaseException $e) {
                $_SESSION['error_message'] = $e->getMessage(); // "Unable to connect to the database"
            } catch (\Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
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
