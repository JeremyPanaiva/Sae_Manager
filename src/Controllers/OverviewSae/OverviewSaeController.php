<?php

namespace Controllers\OverviewSae;

use Controllers\ControllerInterface;
use Views\OverviewSae\OverviewSaeView;
use Models\User\User;
use Models\Sae\Sae;
use Models\Sae\SaeAttribution;

class OverviewSaeController implements ControllerInterface
{
    public const PATH = '/overview_sae';

    public function control()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $currentUser = $_SESSION['user'];
        $role = strtolower($currentUser['role']);
        $username = $currentUser['nom'] . ' ' . $currentUser['prenom'];
        $userId = $currentUser['id'];

        $contentData = [
            'saes' => [],
            'error_message' => ''
        ];

        try {
            $contentData['saes'] = $this->getSaeOverview($userId, $role);
        } catch (\Shared\Exceptions\DataBaseException $e) {
            $contentData['error_message'] = $e->getMessage();
        } catch (\Exception $e) {
            $contentData['error_message'] = "Erreur inattendue : " . $e->getMessage();
        }

        $view = new OverviewSaeView(
            'Récapitulatif des SAE',
            $contentData,
            $username,
            ucfirst($role)
        );

        echo $view->render();
    }

    /**
     * Récupère les SAE selon le rôle de l'utilisateur
     */
    private function getSaeOverview(int $userId, string $role): array
    {
        if ($role === 'etudiant') {
            return $this->getSaeForSingleStudent($userId);
        }

        if (in_array($role, ['responsable', 'client'])) {
            return $this->getSaeForAllStudents($role, $userId);
        }

        return [];
    }

    /**
     * Récupère les SAE pour un étudiant précis
     */
    private function getSaeForSingleStudent(int $studentId): array
    {
        $saes = SaeAttribution::getSaeForStudent($studentId);
        $student = User::getById($studentId); // récupère 'mail', 'nom', 'prenom', 'role'

        foreach ($saes as &$sae) {
            $responsable = SaeAttribution::getResponsableForSae($sae['sae_id'] ?? 0);
            $client = User::getById($sae['client_id'] ?? 0);

            $sae['etudiant_nom'] = $student['nom'] ?? '-';
            $sae['etudiant_prenom'] = $student['prenom'] ?? '-';
            $sae['etudiant_email'] = $student['mail'] ?? '-'; // <--- e-mail étudiant
            $sae['responsable_nom'] = $responsable['nom'] ?? 'N/A';
            $sae['responsable_prenom'] = $responsable['prenom'] ?? '';
            $sae['client_nom'] = $client['nom'] ?? 'N/A';
            $sae['client_prenom'] = $client['prenom'] ?? '';
            $sae['date_rendu'] = $sae['date_rendu'] ?? '-';
            $sae['sae_id'] = $sae['sae_id'] ?? 0;
        }

        return $saes;
    }

    /**
     * Récupère toutes les SAE pour tous les étudiants (responsable ou client)
     */
    private function getSaeForAllStudents(string $role, int $userId): array
    {
        // Récupère uniquement les utilisateurs avec role = 'etudiant'
        $allStudents = User::getAllByRole('etudiant'); // <-- nouvelle fonction à créer si elle n'existe pas
        $result = [];

        foreach ($allStudents as $student) {
            $studentSaes = SaeAttribution::getSaeForStudent($student['id']);

            if (empty($studentSaes)) {
                $result[] = [
                    'etudiant_nom' => $student['nom'],
                    'etudiant_prenom' => $student['prenom'],
                    'etudiant_email' => $student['mail'] ?? '-',
                    'sae_titre' => '-',
                    'client_nom' => '-',
                    'client_prenom' => '',
                    'responsable_nom' => '-',
                    'responsable_prenom' => '',
                    'date_rendu' => '-',
                    'sae_id' => 0
                ];
            } else {
                foreach ($studentSaes as $saeAttrib) {
                    $sae = Sae::getById($saeAttrib['sae_id']);
                    $responsable = SaeAttribution::getResponsableForSae($saeAttrib['sae_id']);
                    $client = User::getById($sae['client_id'] ?? 0);

                    $result[] = [
                        'etudiant_nom' => $student['nom'],
                        'etudiant_prenom' => $student['prenom'],
                        'etudiant_email' => $student['mail'] ?? '-', // <--- e-mail étudiant
                        'sae_titre' => $sae['titre'] ?? '-',
                        'client_nom' => $client['nom'] ?? '-',
                        'client_prenom' => $client['prenom'] ?? '',
                        'responsable_nom' => $responsable['nom'] ?? '-',
                        'responsable_prenom' => $responsable['prenom'] ?? '',
                        'date_rendu' => $saeAttrib['date_rendu'] ?? '-',
                        'sae_id' => $sae['id'] ?? 0
                    ];
                }
            }
        }

        return $result;
    }



    /**
     * Vérifie si ce contrôleur supporte la route
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }
}
