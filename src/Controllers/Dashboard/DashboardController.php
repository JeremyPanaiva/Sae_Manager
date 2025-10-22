<?php
namespace Controllers\Dashboard;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Models\Sae\TodoList;
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

        $data = $this->prepareDashboardData($userId, $role);

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
            // Récupération des SAE pour l'étudiant
            $saes = SaeAttribution::getSaeForStudent($userId);

            foreach ($saes as &$sae) {
                $saeAttributionId = $sae['sae_attribution_id'] ?? null;
                $sae['todos'] = $sae['sae_id'] ? TodoList::getBySae($sae['sae_id']) : [];
                $sae['etudiants'] = $sae['sae_id'] ? SaeAttribution::getStudentsBySae($sae['sae_id']) : [];
            }
        }
        elseif ($role === 'responsable') {
            // Récupération des SAE attribuées par le responsable
            $saes = SaeAttribution::getSaeForResponsable($userId);

            foreach ($saes as &$sae) {
                $saeId = $sae['sae_id'] ?? null;
                $sae['todos'] = $saeId ? TodoList::getBySae($saeId) : [];
                $sae['etudiants'] = $saeId ? SaeAttribution::getStudentsBySae($saeId) : [];
            }
        }
        elseif ($role === 'client') {
            // Récupérer toutes les SAE créées par ce client
            $clientSaes = \Models\Sae\Sae::getByClient($userId);

            foreach ($clientSaes as $sae) {
                $saeId = $sae['id'];

                // Récupérer les attributions de cette SAE
                $attributions = SaeAttribution::getAttributionsBySae($saeId);

                // Ne garder que les SAE qui ont au moins une attribution
                if (empty($attributions)) {
                    continue; // passe à la SAE suivante
                }

                foreach ($attributions as &$attrib) {
                    $attrib['etudiants'] = SaeAttribution::getStudentsBySae($saeId);
                    $attrib['todos'] = TodoList::getBySaeAttribution($attrib['id']);
                    $attrib['avis'] = SaeAttribution::getAvisBySaeAttribution($attrib['id']);
                    $attrib['sae_attribution_id'] = $attrib['id'];
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
