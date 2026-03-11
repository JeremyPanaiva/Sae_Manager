<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\Sae;
use Models\Sae\SaeAttribution;
use Shared\Exceptions\SaeAttribueException;
use Views\Sae\SaeView;
use Shared\SessionGuard;
use Shared\CsrfGuard;
use Shared\RoleGuard;

/**
 * SAE deletion controller
 *
 * Handles the deletion of SAE (Situation d'Apprentissage et d'Évaluation) by clients.
 * Prevents deletion if the SAE has already been assigned to students.
 * Displays appropriate error messages and SAE list on failure.
 * Role verification is delegated to RoleGuard.
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
     * and performs deletion. Handles various error cases by displaying the SAE view
     * with appropriate error messages.
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

        // Extract client and SAE identifiers
        $user        = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : [];
        $clientIdRaw = $user['id']    ?? 0;
        $clientId    = is_numeric($clientIdRaw) ? (int) $clientIdRaw : 0;
        $saeIdRaw    = $_POST['sae_id']         ?? 0;
        $saeId       = is_numeric($saeIdRaw)    ? (int) $saeIdRaw    : 0;

        if ($saeId <= 0) {
            header('Location: /sae?error=invalid_id');
            exit();
        }

        // Store user information for view rendering
        $nom      = is_string($user['nom']    ?? null) ? (string) $user['nom']    : '';
        $prenom   = is_string($user['prenom'] ?? null) ? (string) $user['prenom'] : '';
        $username = $nom . ' ' . $prenom;
        $role     = is_string($user['role']   ?? null) ? (string) $user['role']   : '';

        try {
            $sae = Sae::getById($saeId);
            if (!$sae) {
                header('Location: /sae?error=sae_not_found');
                exit();
            }

            if (Sae::isAttribuee($saeId)) {
                $saeTitre = is_string($sae['titre'] ?? null) ? $sae['titre'] : '';
                throw new SaeAttribueException($saeTitre);
            }

            Sae::delete($clientId, $saeId);

            header('Location: /sae?success=sae_deleted');
            exit();
        } catch (\Shared\Exceptions\DataBaseException $e) {
            $data = ['saes' => [], 'error_message' => $e->getMessage()];
            $view = new SaeView("Gestion des SAE", $data, $username, $role);
            echo $view->render();
            exit();
        } catch (SaeAttribueException $e) {
            $saes = $this->getSaesWithResponsable($clientId);
            $data = ['saes' => $saes, 'error_message' => $e->getMessage()];
            $view = new SaeView("Gestion des SAE", $data, $username, $role);
            echo $view->render();
            exit();
        } catch (\Throwable $e) {
            $saes = $this->getSaesWithResponsable($clientId);
            $data = ['saes' => $saes, 'error_message' => $e->getMessage()];
            $view = new SaeView("Gestion des SAE", $data, $username, $role);
            echo $view->render();
            exit();
        }
    }

    /**
     * Retrieves the client's SAE list with supervisor information.
     * Returns an empty array if a database error occurs.
     *
     * @param int $clientId The client's user ID.
     * @return array<int, array<string, mixed>>
     */
    private function getSaesWithResponsable(int $clientId): array
    {
        try {
            $saes = Sae::getByClient($clientId);
            foreach ($saes as &$s) {
                $sId = is_numeric($s['id'] ?? null) ? (int) $s['id'] : 0;
                $s['responsable_attribution'] = SaeAttribution::getResponsableForSae($sId);
            }
            return $saes;
        } catch (\Shared\Exceptions\DataBaseException $e) {
            return [];
        }
    }

    /**
     * Checks if this controller supports the given route and HTTP method.
     *
     * @param string $path   The requested route path.
     * @param string $method The HTTP method (GET, POST, etc.).
     * @return bool True if path is '/delete_sae' and method is POST.
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}
