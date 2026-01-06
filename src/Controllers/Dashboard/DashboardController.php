<?php

namespace Controllers\Dashboard;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Models\Sae\TodoList;
use Models\Sae\SaeAvis;
use Views\Dashboard\DashboardView;
use Models\User\User;

/**
 * Dashboard controller
 *
 * Handles the display of role-based dashboard views for students, supervisors, and clients.
 * Aggregates SAE (Situation d'Apprentissage et d'Évaluation) data including todos,
 * student assignments, and feedback.
 *
 * @package Controllers\Dashboard
 */
class DashboardController implements ControllerInterface
{
    /**
     * Dashboard route path
     *
     * @var string
     */
    public const PATH = '/dashboard';

    /**
     * Main controller method
     *
     * Checks user authentication, retrieves role-specific dashboard data,
     * and renders the dashboard view.
     *
     * @return void
     */
    public function control()
    {
        // Redirect to login if user is not authenticated
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $user = $_SESSION['user'];
        $role = strtolower($user['role']);
        $username = $user['nom'] .  ' ' . $user['prenom'];
        $userId = $user['id'];

        try {
            // Fetch role-specific dashboard data
            $data = $this->prepareDashboardData($userId, $role);
        } catch (\Shared\Exceptions\DataBaseException $e) {
            // Handle database errors gracefully
            $data = ['error_message' => $e->getMessage()];
        }

        // Create and render dashboard view
        $view = new DashboardView(
            title: 'Tableau de bord',
            username: $username,
            role: ucfirst($role),
            data: $data
        );

        echo $view->render();
    }

    /**
     * Prepares dashboard data based on user role
     *
     * Retrieves SAE assignments, todos, students, and feedback depending on
     * whether the user is a student, supervisor (responsable), or client.
     *
     * @param int $userId The ID of the current user
     * @param string $role The role of the user (etudiant, responsable, client)
     * @return array Dashboard data containing SAE information and related entities
     * @throws \Shared\Exceptions\DataBaseException If database operations fail
     */
    private function prepareDashboardData(int $userId, string $role): array
    {
        $saes = [];

        if ($role === 'etudiant') {
            // Get SAE assignments for student
            $saes = SaeAttribution::getSaeForStudent($userId);
            foreach ($saes as &$sae) {
                $saeId = $sae['sae_id'] ?? null;

                // Fetch related data for each SAE
                $sae['todos'] = $saeId ? TodoList::getBySae($saeId) : [];
                $sae['etudiants'] = $saeId ?  SaeAttribution::getStudentsBySae($saeId) : [];
                $sae['avis'] = $saeId ? SaeAvis::getBySae($saeId) : [];
            }

        } elseif ($role === 'responsable') {
            // Get SAE assignments managed by supervisor
            $saes = SaeAttribution::getSaeForResponsable($userId);
            foreach ($saes as &$sae) {
                $saeId = $sae['sae_id'] ?? null;

                // Fetch related data for each SAE
                $sae['todos'] = $saeId ? TodoList:: getBySae($saeId) : [];
                $sae['etudiants'] = $saeId ? SaeAttribution:: getStudentsBySae($saeId) : [];
                $sae['avis'] = $saeId ? SaeAvis::getBySae($saeId) : [];
            }

        } elseif ($role === 'client') {
            // Get SAE created by client that have been assigned
            $clientSaes = \Models\Sae\Sae::getAssignedSaeByClient($userId);

            foreach ($clientSaes as $sae) {
                $saeId = $sae['id'];

                // Get all attributions for this SAE
                $attributions = SaeAttribution::getAttributionsBySae($saeId);

                // Fetch todos and feedback
                $sae['todos'] = TodoList::getBySae($saeId);
                $sae['avis'] = SaeAvis:: getBySae($saeId);

                // Enrich attributions with student details
                foreach ($attributions as &$attrib) {
                    $attrib['student'] = User::getById($attrib['student_id']);
                }

                $sae['attributions'] = $attributions;
                $saes[] = $sae;
            }
        }

        return ['saes' => $saes];
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if this controller handles the request, false otherwise
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }
}