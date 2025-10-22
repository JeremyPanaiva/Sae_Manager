<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Models\User\User;
use Views\Sae\SaeView;
use Shared\Exceptions\SaeAlreadyAssignedException;
use Shared\Exceptions\StudentAlreadyAssignedException;

class AttribuerSaeController implements ControllerInterface
{
    public const PATH = '/attribuer_sae';

    public function control()
    {
        // Vérifie que la requête est POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sae');
            exit();
        }

        // Vérifie que l'utilisateur est un responsable
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'responsable') {
            header('HTTP/1.1 403 Forbidden');
            echo "Accès refusé";
            exit();
        }

        $saeId = intval($_POST['sae_id'] ?? 0);
        $etudiants = $_POST['etudiants'] ?? [];
        $responsableId = $_SESSION['user']['id'];
        $username = $_SESSION['user']['nom'] . ' ' . $_SESSION['user']['prenom'];
        $role = $_SESSION['user']['role'];

        $errorMessage = '';

        try {
            // Attribuer les étudiants
            SaeAttribution::assignStudentsToSae($saeId, array_map('intval', $etudiants), $responsableId);

            // Redirection si succès
            header('Location: /sae?success=sae_assigned');
            exit();

        }catch (SaeAlreadyAssignedException $e) {
            $message = "Impossible d'attribuer la SAE « {$e->getSae()} » : elle a déjà été attribuée par le responsable \"{$e->getResponsable()}\".";
        }
        catch (StudentAlreadyAssignedException $e) {
            $message = "L'étudiant « {$e->getStudent()} » est déjà assigné à la SAE « {$e->getSae()} ».";
        }


        // Récupérer toutes les SAE et tous les étudiants pour la vue
        $data = [
            'error_message' => $e->getMessage(), // pas besoin de getSae() ni getResponsable()
            'saes' => \Models\Sae\Sae::getAll(),
            'etudiants' => User::getAllStudents(),
        ];


        // Afficher la vue avec l'erreur si elle existe
        $view = new SaeView("Attribution des SAE", $data, $username, $role);
        echo $view->render();
        exit();
    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}
