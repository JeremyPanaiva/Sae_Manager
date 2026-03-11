<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\Sae;
use Models\User\User;
use Models\User\EmailService;
use Shared\SessionGuard;
use Shared\CsrfGuard;
use Shared\RoleGuard;

/**
 * SAE creation controller
 *
 * Handles the creation of new SAE (Situation d'Apprentissage et d'Évaluation)
 * by clients. Sends email notifications to all supervisors (responsables)
 * when a new SAE is created.
 * Role verification is delegated to RoleGuard.
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
    public function control(): void
    {
        SessionGuard::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /sae');
            exit();
        }

        if (!CsrfGuard::validate()) {
            http_response_code(403);
            die('Requête invalide (CSRF).');
        }

        // Verify user is authenticated as a client
        RoleGuard::requireRoleOrForbid('client');

        // Extract client information
        $user        = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : [];
        $clientIdRaw = $user['id']     ?? 0;
        $clientId    = is_numeric($clientIdRaw) ? (int) $clientIdRaw : 0;
        $nom         = is_string($user['nom']    ?? null) ? $user['nom']    : '';
        $prenom      = is_string($user['prenom'] ?? null) ? $user['prenom'] : '';
        $clientNom   = $nom . ' ' . $prenom;

        // Extract and sanitize form data
        $titreRaw       = $_POST['titre']       ?? '';
        $titre          = is_string($titreRaw)       ? trim($titreRaw)       : '';
        $descriptionRaw = $_POST['description'] ?? '';
        $description    = is_string($descriptionRaw) ? trim($descriptionRaw) : '';

        if (empty($titre) || empty($description)) {
            header('Location: /sae?error=missing_fields');
            exit();
        }

        try {
            $saeId = Sae::create($clientId, $titre, $description);

            $responsables = User::getAllResponsables();

            if (!empty($responsables)) {
                $emailService = new EmailService();

                foreach ($responsables as $responsable) {
                    try {
                        $prenomResponsable = is_string($responsable['prenom'] ?? null) ? $responsable['prenom'] : '';
                        $nomResponsable    = is_string($responsable['nom']    ?? null) ? $responsable['nom']    : '';
                        $responsableNom    = $prenomResponsable . ' ' . $nomResponsable;
                        $responsableMail   = is_string($responsable['mail']   ?? null) ? $responsable['mail']   : '';

                        $emailService->sendSaeCreationNotification(
                            $responsableMail,
                            $responsableNom,
                            $clientNom,
                            $titre,
                            $description
                        );
                    } catch (\Exception $e) {
                        $responsableMailLog = is_string($responsable['mail'] ?? null)
                            ? $responsable['mail']
                            : 'unknown';
                        error_log("Erreur notification responsable {$responsableMailLog}: " . $e->getMessage());
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

    /**
     * Checks if this controller supports the given route and HTTP method.
     *
     * @param string $path   The requested route path.
     * @param string $method The HTTP method (GET, POST, etc.).
     * @return bool True if path is '/creer_sae'.
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH;
    }
}
