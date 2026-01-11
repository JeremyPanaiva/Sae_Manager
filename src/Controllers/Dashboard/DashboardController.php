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
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        /** @var array{id:int, role:string, nom:string, prenom:string} $user */
        $user = $_SESSION['user'];

        $role = strtolower($user['role']);
        $username = $user['nom'] . ' ' . $user['prenom'];
        $userId = $user['id'];

        try {
            $data = $this->prepareDashboardData($userId, $role);
        } catch (\Shared\Exceptions\DataBaseException $e) {
            $data = ['error_message' => $e->getMessage()];
        }

        $view = new DashboardView([
            'title' => 'Tableau de bord',
            'username' => $username,
            'role' => ucfirst($role),
            'data' => $data
        ]);

        echo $view->render();
    }

    /**
     * @param int $userId
     * @param string $role
     *
     * @return array{
     *     saes: array<int, array<string, mixed>>
     * }
     */
    private function prepareDashboardData(int $userId, string $role): array
    {
        $saes = [];

        if ($role === 'etudiant') {
            $saes = SaeAttribution::getSaeForStudent($userId);

            foreach ($saes as &$sae) {
                $saeId = (isset($sae['sae_id']) && is_int($sae['sae_id'])) ? $sae['sae_id'] : null;

                $sae['todos'] = $saeId !== null ? TodoList::getBySae($saeId) : [];
                $sae['etudiants'] = $saeId !== null ? SaeAttribution::getStudentsBySae($saeId) : [];
                $sae['avis'] = $saeId !== null ? SaeAvis::getBySae($saeId) : [];

                $dateRendu = (isset($sae['date_rendu']) && is_string($sae['date_rendu']))
                    ? $sae['date_rendu']
                    : '';

                $sae['countdown'] = $this->calculateCountdown($dateRendu);
            }
        } elseif ($role === 'responsable') {
            $saes = SaeAttribution::getSaeForResponsable($userId);

            foreach ($saes as &$sae) {
                $saeId = (isset($sae['sae_id']) && is_int($sae['sae_id'])) ? $sae['sae_id'] : null;

                $sae['todos'] = $saeId !== null ? TodoList::getBySae($saeId) : [];
                $sae['etudiants'] = $saeId !== null ? SaeAttribution::getStudentsBySae($saeId) : [];
                $sae['avis'] = $saeId !== null ? SaeAvis::getBySae($saeId) : [];

                $dateRendu = (isset($sae['date_rendu']) && is_string($sae['date_rendu']))
                    ? $sae['date_rendu']
                    : '';

                $sae['countdown'] = $this->calculateCountdown($dateRendu);
            }
        } elseif ($role === 'client') {
            $clientSaes = \Models\Sae\Sae::getAssignedSaeByClient($userId);

            foreach ($clientSaes as $sae) {
                if (!isset($sae['id']) || !is_int($sae['id'])) {
                    continue;
                }

                $saeId = $sae['id'];
                $attributions = SaeAttribution::getAttributionsBySae($saeId);

                $sae['todos'] = TodoList::getBySae($saeId);
                $sae['avis'] = SaeAvis::getBySae($saeId);

                foreach ($attributions as &$attrib) {
                    if (isset($attrib['student_id']) && is_int($attrib['student_id'])) {
                        $attrib['student'] = User::getById($attrib['student_id']);
                    }
                }

                $sae['attributions'] = $attributions;

                $dateRendu = (isset($attributions[0]['date_rendu']) && is_string($attributions[0]['date_rendu']))
                    ? $attributions[0]['date_rendu']
                    : '';

                $sae['countdown'] = $this->calculateCountdown($dateRendu);
                $saes[] = $sae;
            }
        }

        return ['saes' => $saes];
    }

    /**
     * @param string $dateRendu
     *
     * @return array{
     *     expired: bool,
     *     jours?: int,
     *     heures?: int,
     *     minutes?: int,
     *     timestamp?: int,
     *     urgent?: bool
     * }|null
     */
    private function calculateCountdown(string $dateRendu): ?array
    {
        if ($dateRendu === '') {
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
                'urgent' => ($interval->days === 0),
            ];
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param array{
     *     expired: bool,
     *     jours?: int,
     *     heures?: int,
     *     minutes?: int,
     *     timestamp?: int,
     *     urgent?: bool
     * }|null $countdown
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

        return
            "<div class='countdown-container{$urgentClass}' " .
            "data-deadline='" . ($countdown['timestamp'] ?? 0) . "' " .
            "id='countdown-{$uniqueId}'>" .

            "<div class='countdown-box'>" .
            "<span class='countdown-value' data-type='jours'>" .
            ($countdown['jours'] ?? 0) .
            "</span>" .
            "<span class='countdown-label'>jours</span>" .
            "</div>" .

            "<div class='countdown-box'>" .
            "<span class='countdown-value' data-type='heures'>" .
            ($countdown['heures'] ?? 0) .
            "</span>" .
            "<span class='countdown-label'>heures</span>" .
            "</div>" .

            "<div class='countdown-box'>" .
            "<span class='countdown-value' data-type='minutes'>" .
            ($countdown['minutes'] ?? 0) .
            "</span>" .
            "<span class='countdown-label'>minutes</span>" .
            "</div>" .

            "<div class='countdown-box'>" .
            "<span class='countdown-value' data-type='secondes'>0</span>" .
            "<span class='countdown-label'>secondes</span>" .
            "</div>" .

            "</div>";
    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }
}
