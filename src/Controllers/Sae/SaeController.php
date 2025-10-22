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

        // Récupération des données
        $contentData = $this->prepareSaeContent($userId, $role);

// Instanciation vue
        $view = new SaeView(
            'Gestion des SAE',   // titre
            $contentData,        // données SAE
            $username,           // nom complet
            ucfirst($role)       // rôle affiché correctement
        );


        echo $view->render();
    }

    /**
     * Préparer les données SAE selon le rôle
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
                return ['saes' => $saes, 'etudiants' => $etudiants];

            case 'client':
                // Client : voir ses SAE et possibilité d’en créer
                $saes = Sae::getByClient($userId);
                return ['saes' => $saes];

            default:
                return [];
        }
    }
    public function handleCreateSae(): void
    {
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'client') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre = $_POST['titre'] ?? '';
            $description = $_POST['description'] ?? '';
            $clientId = $_SESSION['user']['id'];

            if ($titre && $description) {
                Sae::create($clientId, $titre, $description);
            }
        }

        header('Location: /sae');
        exit();
    }

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

            foreach ($etudiants as $studentId) {
                SaeAttribution::assignToStudent($saeId, (int)$studentId, $dateRendu);
            }
        }

        header('Location: /sae');
        exit();
    }

    /**
     * Vérifie si ce controller supporte la route
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }
}
