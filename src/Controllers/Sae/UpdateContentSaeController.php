<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\Sae;
use Shared\Exceptions\DataBaseException;

/**
 * SAE content update controller
 *
 * Handles updates to SAE (Situation d'Apprentissage et d'Évaluation) title and description
 * by clients who created them.  Only the client who owns the SAE can modify its content.
 *
 * @package Controllers\Sae
 */
class UpdateContentSaeController implements ControllerInterface
{
    /**
     * SAE update route path
     *
     * @var string
     */
    public const PATH = '/update_sae';

    /**
     * Main controller method
     *
     * Validates client authentication, updates the SAE title and description,
     * and redirects to the SAE management page with success or error message.
     *
     * @return void
     */
    public function control()
    {
        // Verify user is authenticated as a client
        if (!isset($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'client') {
            header('Location: /login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Extract and sanitize form data
            $saeId = (int)($_POST['sae_id'] ?? 0);
            $titre = trim($_POST['titre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $clientId = $_SESSION['user']['id'];

            try {
                // Update SAE in database
                Sae::update($clientId, $saeId, $titre, $description);

                // Set success message in session
                $_SESSION['success_message'] = "SAE mise à jour avec succès ! ";
            } catch (DataBaseException $e) {
                // Database error
                $_SESSION['error_message'] = $e->getMessage();
            } catch (\Exception $e) {
                // Generic error handling
                $_SESSION['error_message'] = "Erreur inattendue : " . $e->getMessage();
            }
        }

        // Redirect to SAE management page
        header('Location:  /sae');
        exit();
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/update_sae' and method is POST
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self:: PATH && $method === 'POST';
    }
}
