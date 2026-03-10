<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Shared\Exceptions\UnauthorizedSaeUnassignmentException;
use Shared\Exceptions\DataBaseException;
use Shared\SessionGuard;
use Shared\CsrfGuard;
use Shared\RoleGuard;

/**
 * SAE unassignment controller
 *
 * Handles the removal of student assignments from SAE by supervisors (responsables).
 * Verifies that only the supervisor who originally assigned students can unassign them.
 * Associated data (todos, feedback) are automatically deleted via database CASCADE rules.
 * Role verification is delegated to RoleGuard.
 *
 * @package Controllers\Sae
 */
class UnassignSaeController implements ControllerInterface
{
    /**
     * SAE unassignment route path
     *
     * @var string
     */
    public const PATH = '/unassign_sae';

    /**
     * Main controller method
     *
     * Validates supervisor authentication, verifies ownership of assignments,
     * and removes student assignments from the specified SAE.
     * Related data (todo_list, sae_avis) are automatically deleted via ON DELETE CASCADE.
     *
     * @return void
     */
    public function control(): void
    {
        SessionGuard::check();

        // Verify user is authenticated as a supervisor
        RoleGuard::requireRole('responsable');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfGuard::validate()) {
                http_response_code(403);
                die('Requête invalide (CSRF).');
            }

            $saeIdRaw         = $_POST['sae_id']    ?? 0;
            $saeId            = is_numeric($saeIdRaw)       ? (int) $saeIdRaw       : 0;
            $studentsRaw      = $_POST['etudiants']  ?? [];
            $students         = is_array($studentsRaw)      ? $studentsRaw          : [];
            $responsableIdRaw = $_SESSION['user']['id']     ?? 0;
            $responsableId    = is_numeric($responsableIdRaw) ? (int) $responsableIdRaw : 0;

            if (empty($students)) {
                $_SESSION['error_message'] = "Veuillez sélectionner au moins un étudiant à retirer.";
                header('Location: /sae');
                exit();
            }

            try {
                \Models\Database::checkConnection();

                foreach ($students as $studentId) {
                    $studentIdInt = is_numeric($studentId) ? (int) $studentId : 0;
                    SaeAttribution::checkResponsableOwnership($saeId, $responsableId, $studentIdInt);
                    SaeAttribution::removeFromStudent($saeId, $studentIdInt);
                }

                $_SESSION['success_message'] = "Étudiant(s) retiré(s) avec succès de la SAE.";

            } catch (UnauthorizedSaeUnassignmentException $e) {
                $_SESSION['error_message'] = $e->getMessage();
            } catch (DataBaseException $e) {
                $_SESSION['error_message'] = $e->getMessage();
            } catch (\Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        header('Location: /sae');
        exit();
    }

    /**
     * Checks if this controller supports the given route and HTTP method.
     *
     * @param string $path   The requested route path.
     * @param string $method The HTTP method (GET, POST, etc.).
     * @return bool True if path is '/unassign_sae' and method is POST.
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}