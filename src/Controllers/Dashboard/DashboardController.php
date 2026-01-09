<?php

namespace Controllers\Dashboard;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Models\Sae\TodoList;
use Models\Sae\SaeAvis;
use Views\Dashboard\DashboardView;
use Models\User\User;

class DashboardController implements ControllerInterface
{
    public const PATH = '/dashboard';

    public function control()
    {
        // Redirect to login if user is not authenticated
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        /** @var array{id: int, role: string, nom: string, prenom: string} $user */
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
     * @param int $userId
     * @param string $role
     * @return array{saes: array<int, array<string, mixed>>}
     * @throws \Shared\Exceptions\DataBaseException
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
     * @param string $dateRendu
     * @return array{expired: bool, jours?: int, heures?: int, minutes?: int, timestamp?: int, urgent?: bool}|null
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
                'jours' => $interval->days === false ? 0 : $interval->days,
                'heures' => $interval->h,
                'minutes' => $interval->i,
                'timestamp' => $deadline->getTimestamp(),
                'urgent' => ($interval->days === 0)
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param array{expired: bool, jours?: int, heures?: int,
     *      minutes?: int, timestamp?: int, urgent?: bool}|null $countdown
     * @param string $uniqueId
     * @return string
     */
    public static function generateCountdownHTML(?array $countdown, string $uniqueId): string
    {
        if ($countdown === null) {
            return "<span class='countdown-error'>Date invalide</span>";
        }

        if ($countdown['expired']) {
            return "<span class='countdown-expired'>Délai expiré</span>";
        }

        $urgentClass = !empty($countdown['urgent']) ? ' urgent' : '';

        $jours = $countdown['jours'] ?? 0;
        $heures = $countdown['heures'] ?? 0;
        $minutes = $countdown['minutes'] ?? 0;
        $timestamp = $countdown['timestamp'] ?? 0;

        $html = "<div class='countdown-container{$urgentClass}'
                data-deadline='{$timestamp}' id='countdown-{$uniqueId}'>";
        $html .= "<div class='countdown-box'>";
        $html .= "<span class='countdown-value' data-type='jours'>{$jours}</span>";
        $html .= "<span class='countdown-label'>jours</span>";
        $html .= "</div>";
        $html .= "<div class='countdown-box'>";
        $html .= "<span class='countdown-value' data-type='heures'>{$heures}</span>";
        $html .= "<span class='countdown-label'>heures</span>";
        $html .= "</div>";
        $html .= "<div class='countdown-box'>";
        $html .= "<span class='countdown-value' data-type='minutes'>{$minutes}</span>";
        $html .= "<span class='countdown-label'>minutes</span>";
        $html .= "</div>";
        $html .= "<div class='countdown-box'>";
        $html .= "<span class='countdown-value' data-type='secondes'>0</span>";
        $html .= "<span class='countdown-label'>secondes</span>";
        $html .= "</div>";
        $html .= "</div>";

        return $html;
    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }
}
