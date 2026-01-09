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
                $sae['countdown'] = $this->calculateCountdown($sae['date_rendu'] ?? '');
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
                $sae['countdown'] = $this->calculateCountdown($sae['date_rendu'] ?? '');
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
                $dateRendu = '';
                if (! empty($attributions)) {
                    $dateRendu = $attributions[0]['date_rendu'] ?? '';
                }
                $sae['countdown'] = $this->calculateCountdown($dateRendu);

                $saes[] = $sae;
            }
        }

        return ['saes' => $saes];
    }


    /**
     * Calculates countdown data for a given deadline
     *
     * @param string $dateRendu Deadline date in Y-m-d or Y-m-d H:i: s format
     * @return array|null Array with countdown information or null if invalid
     */
    private function calculateCountdown(string $dateRendu): ?array
    {
        if (empty($dateRendu)) {
            return null;
        }

        try {
            $now = new \DateTime();
            $deadline = new \DateTime($dateRendu);

            if ($deadline < $now) {
                return ['expired' => true];
            }

            $interval = $now->diff($deadline);

            return [
                'expired' => false,
                'jours' => $interval->days,
                'heures' => $interval->h,
                'minutes' => $interval->i,
                'timestamp' => $deadline->getTimestamp(),
                'urgent' => ($interval->days === 0) // Moins de 24h
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generates HTML for countdown display
     *
     * @param array|null $countdown Countdown data from calculateCountdown()
     * @param string $uniqueId Unique identifier for the countdown element
     * @return string HTML markup for the countdown
     */
    public static function generateCountdownHTML(?array $countdown, string $uniqueId): string
    {
        if ($countdown === null) {
            return "<span class='countdown-error'>Date invalide</span>";
        }

        if ($countdown['expired']) {
            return "<span class='countdown-expired'>Délai expiré</span>";
        }

        $urgentClass = $countdown['urgent'] ? ' urgent' : '';

        $html = "<div class='countdown-container{$urgentClass}' data-deadline='{$countdown['timestamp']}' id='countdown-{$uniqueId}'>";
        $html .= "<div class='countdown-box'>";
        $html .= "<span class='countdown-value' data-type='jours'>{$countdown['jours']}</span>";
        $html .= "<span class='countdown-label'>jours</span>";
        $html .= "</div>";
        $html .= "<div class='countdown-box'>";
        $html .= "<span class='countdown-value' data-type='heures'>{$countdown['heures']}</span>";
        $html .= "<span class='countdown-label'>heures</span>";
        $html .= "</div>";
        $html .= "<div class='countdown-box'>";
        $html .= "<span class='countdown-value' data-type='minutes'>{$countdown['minutes']}</span>";
        $html .= "<span class='countdown-label'>minutes</span>";
        $html .= "</div>";
        $html .= "<div class='countdown-box'>";
        $html .= "<span class='countdown-value' data-type='secondes'>0</span>";
        $html .= "<span class='countdown-label'>secondes</span>";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
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
