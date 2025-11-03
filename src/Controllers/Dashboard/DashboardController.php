<?php

namespace Controllers\Dashboard;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Models\Sae\TodoList;
use Models\Sae\SaeAvis;
use Views\Dashboard\DashboardView;

class DashboardController implements ControllerInterface
{
    public const PATH = '/dashboard';

    public function control()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $user = $_SESSION['user'];
        $role = strtolower($user['role']);
        $username = $user['nom'] . ' ' . $user['prenom'];
        $userId = $user['id'];

        try {
            $data = $this->prepareDashboardData($userId, $role);
        } catch (\Shared\Exceptions\DataBaseException $e) {
            $data = ['error_message' => $e->getMessage()];
        }

        $view = new DashboardView(
            title: 'Tableau de bord',
            username: $username,
            role: ucfirst($role),
            data: $data
        );

        echo $view->render();
    }

    private function prepareDashboardData(int $userId, string $role): array
    {
        $saes = [];

        if ($role === 'etudiant') {
            $saes = SaeAttribution::getSaeForStudent($userId);
            foreach ($saes as &$sae) {
                $saeId = $sae['sae_id'] ?? null;
                $attribId = $sae['sae_attribution_id'] ?? null;

                $sae['todos'] = $attribId ? TodoList::getBySaeAttribution($attribId) : [];
                $sae['etudiants'] = $saeId ? SaeAttribution::getStudentsBySae($saeId) : [];
                $sae['avis'] = $attribId ? SaeAvis::getBySaeAttribution($attribId) : [];
            }
        } elseif ($role === 'responsable') {
            $saes = SaeAttribution::getSaeForResponsable($userId);
            foreach ($saes as &$sae) {
                $saeId = $sae['sae_id'] ?? null;
                $attribId = $sae['sae_attribution_id'] ?? null;

                $sae['todos'] = $attribId ? TodoList::getBySaeAttribution($attribId) : [];
                $sae['etudiants'] = $saeId ? SaeAttribution::getStudentsBySae($saeId) : [];
                $sae['avis'] = $attribId ? SaeAvis::getBySaeAttribution($attribId) : [];
            }
        } elseif ($role === 'client') {
            $clientSaes = \Models\Sae\Sae::getByClient($userId);

            foreach ($clientSaes as $sae) {
                $saeId = $sae['id'];

                $attributions = SaeAttribution::getAttributionsBySae($saeId);
                if (empty($attributions)) continue;

                foreach ($attributions as &$attrib) {
                    $attribId = $attrib['id'];
                    $attrib['etudiants'] = SaeAttribution::getStudentsBySae($saeId);
                    $attrib['todos'] = TodoList::getBySaeAttribution($attribId);
                    $attrib['avis'] = SaeAvis::getBySaeAttribution($attribId);
                    $attrib['sae_attribution_id'] = $attribId;
                }

                $sae['attributions'] = $attributions;
                $saes[] = $sae;
            }
        }

        return ['saes' => $saes];
    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }
}
