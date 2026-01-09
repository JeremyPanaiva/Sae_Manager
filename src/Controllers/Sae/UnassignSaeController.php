<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Shared\Exceptions\UnauthorizedSaeUnassignmentException;
use Shared\Exceptions\DataBaseException;

/**
 * SAE unassignment controller
 *
 * Handles the removal of student assignments from SAE by supervisors (responsables).
 * Verifies that only the supervisor who originally assigned students can unassign them.
 * Associated data (todos, feedback) are automatically deleted via database CASCADE rules.
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
    public function control()
    {
        // Verify user is authenticated as a supervisor
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'responsable') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Extract form data
            $saeId = (int)($_POST['sae_id'] ?? 0);
            $students = $_POST['etudiants'] ?? [];
            $responsableId = (int)$_SESSION['user']['id'];

            try {
                // Check database connection
                \Models\Database::checkConnection();

                // Process each student unassignment
                foreach ($students as $studentId) {
                    // Verify that the current supervisor is the one who assigned this student
                    SaeAttribution:: checkResponsableOwnership($saeId, $responsableId, (int)$studentId);

                    // Remove student assignment from SAE
                    // Associated entries in todo_list and sae_avis will be automatically deleted via ON DELETE CASCADE
                    SaeAttribution::removeFromStudent($saeId, (int)$studentId);
                }

                // Set success message in session
                $_SESSION['success_message'] = "Étudiant(s) retiré(s) avec succès de la SAE.";
            } catch (UnauthorizedSaeUnassignmentException $e) {
                // Supervisor is not authorized to unassign these students
                $_SESSION['error_message'] = $e->getMessage();
            } catch (DataBaseException $e) {
                // Database connection or operation error
                $_SESSION['error_message'] = $e->getMessage();
            } catch (\Exception $e) {
                // Generic error handling
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        // Redirect to SAE management page
        header('Location: /sae');
        exit();
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/unassign_sae' and method is POST
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self:: PATH && $method === 'POST';
    }
}
