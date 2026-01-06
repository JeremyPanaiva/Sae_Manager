<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Views\Sae\SaeView;
use Models\User\User;
use Models\Sae\Sae;
use Models\Sae\SaeAttribution;

/**
 * SAE management controller
 *
 * Handles the display of SAE (Situation d'Apprentissage et d'Évaluation) management page.
 * Provides role-specific views for students, supervisors (responsables), and clients.
 * Students see their assigned SAE, supervisors see available SAE to assign, and clients
 * see the SAE they have created.
 *
 * @package Controllers\Sae
 */
class SaeController implements ControllerInterface
{
    /**
     * SAE management page route path
     *
     * @var string
     */
    public const PATH = '/sae';

    /**
     * Main controller method
     *
     * Verifies user authentication, retrieves role-specific SAE data,
     * and renders the SAE management view with appropriate content.
     *
     * @return void
     */
    public function control()
    {
        // Verify user is authenticated
        if (!isset($_SESSION['user'])) {
            header('Location:  /login');
            exit();
        }

        // Extract user information
        $currentUser = $_SESSION['user'];
        $role = strtolower($currentUser['role']);
        $username = $currentUser['nom'] . ' ' . $currentUser['prenom'];
        $userId = $currentUser['id'];

        // Initialize content data
        $contentData = [
            'saes' => [],
            'error_message' => '',
            'success_message' => ''
        ];

        try {
            // Retrieve role-specific SAE data
            $contentData = array_merge($contentData, $this->prepareSaeContent($userId, $role));
        } catch (\Shared\Exceptions\DataBaseException $e) {
            // Store database error message
            $contentData['error_message'] =  $e->getMessage();
        } catch (\Exception $e) {
            // Store generic error message
            $contentData['error_message'] = "Erreur inattendue : " .  $e->getMessage();
        }

        // Create and render view
        $view = new SaeView(
            'Gestion des SAE',
            $contentData,
            $username,
            ucfirst($role)
        );

        echo $view->render();
    }

    /**
     * Prepares SAE content based on user role
     *
     * Retrieves and formats SAE data differently for students, supervisors, and clients.
     * - Students:  See their assigned SAE with formatted deadlines
     * - Supervisors: See all proposed SAE with student availability and assignment status
     * - Clients:  See SAE they have created with assignment information
     *
     * @param int $userId The ID of the current user
     * @param string $role The role of the user (etudiant, responsable, client)
     * @return array Formatted SAE data with role-specific information
     * @throws \Shared\Exceptions\DataBaseException If database operations fail
     */
    private function prepareSaeContent(int $userId, string $role): array
    {
        switch ($role) {
            case 'etudiant':
                // Retrieve student's assigned SAE
                $saes = SaeAttribution::getSaeForStudent($userId);

                // Format submission deadlines
                foreach ($saes as &$sae) {
                    $sae['date_rendu_formatee'] = !empty($sae['date_rendu'])
                        ? date('d/m/Y', strtotime($sae['date_rendu']))
                        : 'Non définie';
                }
                return ['saes' => $saes];

            case 'responsable':
                // Retrieve all proposed SAE
                $saes = Sae::getAllProposed();
                $etudiants = User::getAllByRole('etudiant');
                $responsableId = $userId;

                foreach ($saes as &$sae) {
                    // Get all students assigned to this SAE
                    $assignedStudents = SaeAttribution::getStudentsForSae($sae['id']);

                    // Filter students assigned by current supervisor
                    $etudiantsAttribuesParMoi = [];
                    foreach ($assignedStudents as $assignedStudent) {
                        if (SaeAttribution::isStudentAssignedByResponsable($sae['id'], $assignedStudent['id'], $responsableId)) {
                            $etudiantsAttribuesParMoi[] = $assignedStudent;
                        }
                    }

                    // Filter available students (not assigned to this SAE)
                    $etudiantsDisponibles = array_filter($etudiants, function ($etudiant) use ($assignedStudents) {
                        foreach ($assignedStudents as $assignedStudent) {
                            if ($assignedStudent['id'] == $etudiant['id']) {
                                return false;
                            }
                        }
                        return true;
                    });

                    // Attach student data to SAE
                    $sae['etudiants_disponibles'] = $etudiantsDisponibles;
                    $sae['etudiants_attribues']   = $etudiantsAttribuesParMoi;

                    // Attach supervisor who assigned the SAE (null if unassigned)
                    $sae['responsable_attribution'] = SaeAttribution::getResponsableForSae($sae['id']);
                }

                // Sort SAE by priority:  my assignments → free → assigned by others
                usort($saes, function ($a, $b) use ($responsableId) {
                    $aIsMine  = !empty($a['etudiants_attribues']);
                    $bIsMine  = !empty($b['etudiants_attribues']);

                    $aIsFree  = empty($a['responsable_attribution']);
                    $bIsFree  = empty($b['responsable_attribution']);

                    // Priority:  0 = mine, 1 = free, 2 = others
                    $priorityA = $aIsMine ? 0 : ($aIsFree ? 1 :  2);
                    $priorityB = $bIsMine ? 0 : ($bIsFree ? 1 : 2);

                    return $priorityA - $priorityB;
                });

                // Retrieve and clear session messages
                $errorMessage = $_SESSION['error_message'] ?? '';
                $successMessage = $_SESSION['success_message'] ?? '';
                unset($_SESSION['error_message'], $_SESSION['success_message']);

                return [
                    'saes' => $saes,
                    'error_message' => $errorMessage,
                    'success_message' => $successMessage
                ];

            case 'client':
                // Retrieve client's created SAE
                $saes = Sae::getByClient($userId);

                foreach ($saes as &$sae) {
                    // Add supervisor assignment information
                    $sae['responsable_attribution'] = SaeAttribution::getResponsableForSae($sae['id']);
                }

                return ['saes' => $saes];

            default:
                return [];
        }
    }

    /**
     * Handles SAE creation (legacy method)
     *
     * Note: This method appears unused as SAE creation is handled by CreateSaeController.
     * Consider removing if confirmed unused.
     *
     * @return void
     * @deprecated Use CreateSaeController instead
     */
    public function handleCreateSae(): void
    {
        // Verify user is authenticated as client
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'client') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ??  '');
            $clientId = $_SESSION['user']['id'];

            if ($titre !== '' && $description !== '') {
                try {
                    \Models\Database:: checkConnection();
                    Sae::create($clientId, $titre, $description);
                } catch (\Shared\Exceptions\DataBaseException $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                } catch (\Exception $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                }
            }
        }

        header('Location: /sae');
        exit();
    }

    /**
     * Handles SAE assignment (legacy method)
     *
     * Note: This method appears unused as assignment is handled by AttribuerSaeController.
     * Consider removing if confirmed unused.
     *
     * @return void
     * @deprecated Use AttribuerSaeController instead
     */
    public function handleAssignSae(): void
    {
        // Verify user is authenticated as supervisor
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'responsable') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $saeId = (int)($_POST['sae_id'] ?? 0);
            $dateRendu = $_POST['date_rendu'] ?? '';
            $etudiants = $_POST['etudiants'] ?? [];

            if ($saeId > 0 && !empty($etudiants)) {
                try {
                    \Models\Database:: checkConnection();
                    foreach ($etudiants as $studentId) {
                        SaeAttribution::assignToStudent($saeId, (int)$studentId, $dateRendu);
                    }
                } catch (\Shared\Exceptions\DataBaseException $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                } catch (\Exception $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                }
            }
        }

        header('Location: /sae');
        exit();
    }

    /**
     * Handles SAE unassignment (legacy method)
     *
     * Note: This method appears unused as unassignment is handled by UnassignSaeController.
     * Consider removing if confirmed unused.
     *
     * @return void
     * @deprecated Use UnassignSaeController instead
     */
    public function handleUnassignSae(): void
    {
        // Verify user is authenticated as supervisor
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'responsable') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $saeId = (int)($_POST['sae_id'] ?? 0);
            $etudiants = $_POST['etudiants'] ?? [];

            if ($saeId > 0 && !empty($etudiants)) {
                try {
                    \Models\Database::checkConnection();
                    foreach ($etudiants as $studentId) {
                        SaeAttribution::removeFromStudent($saeId, (int)$studentId);
                    }
                } catch (\Shared\Exceptions\DataBaseException $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                } catch (\Exception $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                }
            }
        }

        header('Location: /sae');
        exit();
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/sae' and method is GET
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }
}