<?php
namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Models\Sae\Sae;
use Models\User\User;
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

        try {
            // Vérifie la connexion à la base
            SaeAttribution::checkDatabaseConnection();

            // Attribuer les étudiants
            SaeAttribution::assignStudentsToSae($saeId, array_map('intval', $etudiants), $responsableId);

            // Récupérer le titre de la SAE pour le message
            $saeInfo = Sae::getById($saeId);
            $saeTitre = $saeInfo['titre'] ?? 'la SAE';

            // Récupérer les noms des étudiants pour le message
            $studentNames = [];
            foreach ($etudiants as $studentId) {
                $student = User::getById((int)$studentId);
                if ($student) {
                    $studentNames[] = trim($student['nom'] . ' ' . $student['prenom']);
                }
            }

            // Construire le message de succès
            $nbEtudiants = count($studentNames);
            if ($nbEtudiants === 1) {
                $_SESSION['success_message'] = "L'étudiant « {$studentNames[0]} » a été attribué avec succès à la SAE « $saeTitre ».";
            } else {
                $listeEtudiants = implode(', ', array_slice($studentNames, 0, -1)) . ' et ' . end($studentNames);
                $_SESSION['success_message'] = "$nbEtudiants étudiants ont été attribués avec succès à la SAE « $saeTitre » : $listeEtudiants.";
            }

            header('Location: /sae');
            exit();

        } catch (\Shared\Exceptions\DataBaseException $e) {
            $_SESSION['error_message'] = $e->getMessage(); // "Unable to connect to the database"
        } catch (SaeAlreadyAssignedException $e) {
            $_SESSION['error_message'] = "Impossible d'attribuer la SAE « {$e->getSae()} » : elle a déjà été attribuée par le responsable « {$e->getResponsable()} ».";
        } catch (StudentAlreadyAssignedException $e) {
            $_SESSION['error_message'] = "L'étudiant « {$e->getStudent()} » est déjà assigné à la SAE « {$e->getSae()} ».";
        }

// Redirection avec message d'erreur
        header('Location: /sae');
        exit();

    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}