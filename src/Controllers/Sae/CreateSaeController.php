<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\Sae;
use Models\User\User;
use Models\User\EmailService;

class CreateSaeController implements ControllerInterface
{
    public const PATH = '/creer_sae';

    public function control()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sae');
            exit();
        }

        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'client') {
            header('HTTP/1.1 403 Forbidden');
            echo "Accès refusé";
            exit();
        }

        $clientId = $_SESSION['user']['id'];
        $clientNom = $_SESSION['user']['nom'] . ' ' . $_SESSION['user']['prenom'];
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($titre) || empty($description)) {
            header('Location: /sae?error=missing_fields');
            exit();
        }

        try {
            // Créer la SAE dans la base
            $saeId = Sae::create($clientId, $titre, $description);

            // Récupérer tous les responsables
            $responsables = User::getAllResponsables();

            // Envoyer une notification à chaque responsable
            if (!empty($responsables)) {
                $emailService = new EmailService();

                foreach ($responsables as $responsable) {
                    try {
                        $responsableNom = $responsable['prenom'] . ' ' . $responsable['nom'];
                        $emailService->sendSaeCreationNotification(
                            $responsable['mail'],
                            $responsableNom,
                            $clientNom,
                            $titre,
                            $description
                        );
                    } catch (\Exception $e) {
                        // Logger l'erreur mais ne pas bloquer la création
                        error_log("Erreur lors de l'envoi de la notification au responsable {$responsable['mail']}: " . $e->getMessage());
                    }
                }
            }

            header('Location: /sae?success=sae_created');
            exit();

        } catch (\Exception $e) {
            error_log("Erreur lors de la création de la SAE: " . $e->getMessage());
            header('Location: /sae?error=creation_failed');
            exit();
        }
    }

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH;
    }
}