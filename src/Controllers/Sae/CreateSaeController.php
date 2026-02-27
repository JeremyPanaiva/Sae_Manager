<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\Sae;
use Models\User\User;
use Models\User\EmailService;
use Shared\SessionGuard;

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
        SessionGuard::check();
        // Ensure POST method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sae');
            exit();
        }

        // Verify user is authenticated as a client
        if (
            !isset($_SESSION['user']) ||
            !is_array($_SESSION['user']) ||
            !isset($_SESSION['user']['role']) ||
            !is_string($_SESSION['user']['role']) ||
            strtolower($_SESSION['user']['role']) !== 'client'
        ) {
            header('HTTP/1.1 403 Forbidden');
            echo "Accès refusé";
            exit();
        }

        // Extract client information
        $clientIdRaw = $_SESSION['user']['id'] ?? 0;
        $clientId = is_numeric($clientIdRaw) ? (int)$clientIdRaw : 0;
        $nomRaw = $_SESSION['user']['nom'] ?? '';
        $prenomRaw = $_SESSION['user']['prenom'] ?? '';
        $nom = is_string($nomRaw) ? $nomRaw : '';
        $prenom = is_string($prenomRaw) ? $prenomRaw : '';
        $clientNom = $nom . ' ' . $prenom;

        // Extract and sanitize form data
        $titreRaw = $_POST['titre'] ?? '';
        $titre = is_string($titreRaw) ? trim($titreRaw) : '';
        $descriptionRaw = $_POST['description'] ?? '';
        $description = is_string($descriptionRaw) ? trim($descriptionRaw) : '';

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
                        $prenomResponsableRaw = $responsable['prenom'] ?? '';
                        $nomResponsableRaw = $responsable['nom'] ?? '';
                        $prenomResponsable = is_string($prenomResponsableRaw) ? $prenomResponsableRaw : '';
                        $nomResponsable = is_string($nomResponsableRaw) ? $nomResponsableRaw : '';
                        $responsableNom = $prenomResponsable . ' ' . $nomResponsable;

                        $responsableMailRaw = $responsable['mail'] ?? '';
                        $responsableMail = is_string($responsableMailRaw) ? $responsableMailRaw : '';

                        $emailService->sendSaeCreationNotification(
                            $responsableMail,
                            $responsableNom,
                            $clientNom,
                            $titre,
                            $description
                        );
                    } catch (\Exception $e) {
                        // Log error but don't block SAE creation
                        $responsableMailLog = isset($responsable['mail']) && is_string($responsable['mail'])
                            ? $responsable['mail']
                            : 'unknown';

                        error_log("Erreur lors de l'envoi de la notification au responsable 
                        {$responsableMailLog}:  " . $e->getMessage());
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
