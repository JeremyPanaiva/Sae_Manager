<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Views\Sae\SaeView;
use Models\User\User;
use Models\Sae\Sae;
use Models\Sae\SaeAttribution;

class SaeController implements ControllerInterface
{
    public const PATH = '/sae';

    public function control()
    {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit();
        }

        $currentUser = $_SESSION['user'];
        $role = strtolower($currentUser['role']); // identique au header
        $username = $currentUser['nom'] . ' ' . $currentUser['prenom'];
        $userId = $currentUser['id'];

        // Récupération des données selon le rôle
        $contentData = $this->prepareSaeContent($userId, $role);

        // Instanciation de la vue
        $view = new SaeView(
            'Gestion des SAE',
            $contentData,
            $username,
            ucfirst($role)
        );

        echo $view->render();
    }

    /**
     * Préparer les données SAE selon le rôle de l'utilisateur
     */
    // Contrôleur SaeController.php

    // Contrôleur SaeController.php

    /**
     * Préparer les données SAE selon le rôle de l'utilisateur
     */
    /**
     * Préparer les données SAE selon le rôle de l'utilisateur
     */
    private function prepareSaeContent(int $userId, string $role): array
    {
        switch ($role) {
            case 'etudiant':
                // Étudiant : voir ses SAE attribuées
                $saes = SaeAttribution::getSaeForStudent($userId);
                return ['saes' => $saes];

            case 'responsable':
                // Responsable : voir toutes les SAE proposées + liste des étudiants
                $saes = Sae::getAllProposed();
                $etudiants = User::getAllByRole('etudiant');
                $responsableId = $userId; // ID du responsable connecté

                // Exclure les étudiants déjà attribués à chaque SAE pour le formulaire d'attribution
                foreach ($saes as &$sae) {
                    // Récupérer les étudiants déjà attribués à cette SAE
                    $assignedStudents = SaeAttribution::getStudentsForSae($sae['id']);

                    // Filtrer les étudiants attribués PAR CE RESPONSABLE pour la suppression
                    $etudiantsAttribuesParMoi = [];
                    foreach ($assignedStudents as $assignedStudent) {
                        // Vérifier si c'est bien ce responsable qui a attribué cet étudiant
                        if (SaeAttribution::isStudentAssignedByResponsable($sae['id'], $assignedStudent['id'], $responsableId)) {
                            $etudiantsAttribuesParMoi[] = $assignedStudent;
                        }
                    }

                    // Filtrer les étudiants non attribués pour l'attribution
                    $etudiantsDisponibles = array_filter($etudiants, function ($etudiant) use ($assignedStudents) {
                        foreach ($assignedStudents as $assignedStudent) {
                            if ($assignedStudent['id'] == $etudiant['id']) {
                                return false; // L'étudiant est déjà attribué, on l'exclut
                            }
                        }
                        return true; // L'étudiant n'est pas encore attribué à la SAE
                    });

                    // Ajouter les étudiants disponibles pour cette SAE pour l'attribution
                    $sae['etudiants_disponibles'] = $etudiantsDisponibles;

                    // Ajouter SEULEMENT les étudiants attribués par CE responsable
                    $sae['etudiants_attribues'] = $etudiantsAttribuesParMoi;
                }

                // Récupérer les messages de session
                $errorMessage = $_SESSION['error_message'] ?? '';
                $successMessage = $_SESSION['success_message'] ?? '';

                // Nettoyer les messages de session
                unset($_SESSION['error_message']);
                unset($_SESSION['success_message']);

                return [
                    'saes' => $saes,
                    'error_message' => $errorMessage,
                    'success_message' => $successMessage
                ];

            case 'client':
                // Client : voir ses SAE et possibilité d'en créer
                $saes = Sae::getByClient($userId);
                return ['saes' => $saes];

            default:
                return [];
        }
    }



    /**
     * Gestion de la création d'une SAE (client)
     */
    public function handleCreateSae(): void
    {
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'client') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $clientId = $_SESSION['user']['id'];

            if ($titre !== '' && $description !== '') {
                Sae::create($clientId, $titre, $description);
            }
        }

        header('Location: /sae');
        exit();
    }

    /**
     * Gestion de l'attribution d'une SAE à un ou plusieurs étudiants (responsable)
     */
    public function handleAssignSae(): void
    {
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'responsable') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $saeId = (int)($_POST['sae_id'] ?? 0);
            $dateRendu = $_POST['date_rendu'] ?? '';
            $etudiants = $_POST['etudiants'] ?? [];

            if ($saeId > 0 && !empty($etudiants)) {
                foreach ($etudiants as $studentId) {
                    SaeAttribution::assignToStudent($saeId, (int)$studentId, $dateRendu);
                }
            }
        }

        header('Location: /sae');
        exit();
    }

    /**
     * 🔥 Gestion de la désattribution d'une SAE (suppression d'étudiants)
     */
    public function handleUnassignSae(): void
    {
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'responsable') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $saeId = (int)($_POST['sae_id'] ?? 0);
            $etudiants = $_POST['etudiants'] ?? [];

            if ($saeId > 0 && !empty($etudiants)) {
                foreach ($etudiants as $studentId) {
                    SaeAttribution::removeFromStudent($saeId, (int)$studentId);
                }
            }
        }

        header('Location: /sae');
        exit();
    }

    /**
     * Vérifie si ce contrôleur supporte la route
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }
}
