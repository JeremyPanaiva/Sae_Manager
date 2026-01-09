<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\Sae;
use Models\Sae\SaeAttribution;
use Shared\Exceptions\SaeAttribueException;
use Views\Sae\SaeView;

/**
 * SAE deletion controller
 *
 * Handles the deletion of SAE (Situation d'Apprentissage et d'Évaluation) by clients.
 * Prevents deletion if the SAE has already been assigned to students.
 * Displays appropriate error messages and SAE list on failure.
 *
 * @package Controllers\Sae
 */
class DeleteSaeController implements ControllerInterface
{
    /**
     * SAE deletion route path
     *
     * @var string
     */
    public const PATH = '/delete_sae';

    /**
     * Main controller method
     *
     * Validates client authentication, checks if SAE can be deleted (not assigned),
     * and performs deletion.   Handles various error cases by displaying the SAE view
     * with appropriate error messages.
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

        // Extract client and SAE identifiers
        $clientId = intval($_SESSION['user']['id']);
        $saeId = intval($_POST['sae_id'] ??  0);

        // Validate SAE ID
        if ($saeId <= 0) {
            header('Location: /sae?error=invalid_id');
            exit();
        }

        // Store user information for view rendering
        $username = $_SESSION['user']['nom'] . ' ' .  $_SESSION['user']['prenom'];
        $role = $_SESSION['user']['role'];

        try {
            // Retrieve SAE information
            $sae = Sae::getById($saeId);
            if (!$sae) {
                header('Location:  /sae?error=sae_not_found');
                exit();
            }

            // Check if SAE has been assigned to students
            if (Sae:: isAttribuee($saeId)) {
                throw new SaeAttribueException($sae['titre']);
            }

            // Delete SAE from database
            Sae::delete($clientId, $saeId);

            // Redirect with success message
            header('Location: /sae?success=sae_deleted');
            exit();
        } catch (\Shared\Exceptions\DataBaseException $e) {
            // Database error - display view with error message and empty SAE list
            $data = [
                'saes' => [],
                'error_message' => $e->getMessage(),
            ];
            $view = new SaeView("Gestion des SAE", $data, $username, $role);
            echo $view->render();
            exit();
        } catch (SaeAttribueException $e) {
            // SAE already assigned - retrieve client's SAE list and display with error
            $saes = [];
            try {
                $saes = Sae::getByClient($clientId);
                // Add supervisor information for each SAE
                foreach ($saes as &$s) {
                    $s['responsable_attribution'] = SaeAttribution:: getResponsableForSae($s['id']);
                }
            } catch (\Shared\Exceptions\DataBaseException $dbEx) {
                // If database is unavailable, leave list empty
            }

            $data = [
                'saes' => $saes,
                'error_message' => $e->getMessage(),
            ];

            $view = new SaeView("Gestion des SAE", $data, $username, $role);
            echo $view->render();
            exit();
        } catch (\Throwable $e) {
            // Generic error handling - attempt to retrieve SAE list
            $saes = [];
            try {
                $saes = Sae::getByClient($clientId);
                // Add supervisor information for each SAE
                foreach ($saes as &$s) {
                    $s['responsable_attribution'] = SaeAttribution::getResponsableForSae($s['id']);
                }
            } catch (\Shared\Exceptions\DataBaseException $dbEx) {
                // If database is unavailable, leave list empty
            }

            $data = [
                'saes' => $saes,
                'error_message' => $e->getMessage(),
            ];

            $view = new SaeView("Gestion des SAE", $data, $username, $role);
            echo $view->render();
            exit();
        }
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/delete_sae' and method is POST
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}
