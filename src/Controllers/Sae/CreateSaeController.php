<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\Sae;
use Models\User\User;
use Models\User\EmailService;

/**
 * SAE creation controller
 *
 * Handles the creation of new SAE (Situation d'Apprentissage et d'Évaluation)
 * by clients.  Sends email notifications to all supervisors (responsables)
 * when a new SAE is created.
 *
 * @package Controllers\Sae
 */
class CreateSaeController implements ControllerInterface
{
    /**
     * SAE creation route path
     *
     * @var string
     */
    public const PATH = '/creer_sae';

    /**
     * Main controller method
     *
     * Validates client authentication, creates a new SAE in the database,
     * and sends email notifications to all supervisors about the new opportunity.
     *
     * @return void
     */
    public function control()
    {
        // Ensure POST method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sae');
            exit();
        }

        // Verify user is authenticated as a client
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'client') {
            header('HTTP/1.1 403 Forbidden');
            echo "Accès refusé";
            exit();
        }

        // Extract client information
        $clientId = $_SESSION['user']['id'];
        $clientNom = $_SESSION['user']['nom'] . ' ' . $_SESSION['user']['prenom'];

        // Extract and sanitize form data
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Validate required fields
        if (empty($titre) || empty($description)) {
            header('Location: /sae?error=missing_fields');
            exit();
        }

        try {
            // Create SAE in database
            $saeId = Sae::create($clientId, $titre, $description);

            // Retrieve all supervisors
            $responsables = User::getAllResponsables();

            // Send notification email to each supervisor
            if (!empty($responsables)) {
                $emailService = new EmailService();

                foreach ($responsables as $responsable) {
                    try {
                        $responsableNom = $responsable['prenom'] . ' ' .  $responsable['nom'];
                        $emailService->sendSaeCreationNotification(
                            $responsable['mail'],
                            $responsableNom,
                            $clientNom,
                            $titre,
                            $description
                        );
                    } catch (\Exception $e) {
                        // Log error but don't block SAE creation
                        error_log("Erreur lors de l'envoi de la notification au responsable {$responsable['mail']}:  " . $e->getMessage());
                    }
                }
            }

            // Redirect with success message
            header('Location:  /sae?success=sae_created');
            exit();
        } catch (\Exception $e) {
            // Log error and redirect with failure message
            error_log("Erreur lors de la création de la SAE: " . $e->getMessage());
            header('Location:  /sae?error=creation_failed');
            exit();
        }
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * Note: This matches any HTTP method, but the control() method enforces POST.
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/creer_sae'
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH;
    }
}
