<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Shared\Exceptions\DataBaseException;
use Shared\SessionGuard;
use Shared\CsrfGuard;
use Shared\RoleGuard;

/**
 * SAE submission date update controller
 *
 * Handles updates to SAE submission deadlines (date de rendu) by supervisors (responsables).
 * The updated deadline applies to all students assigned to the SAE by the supervisor.
 * Role verification is delegated to RoleGuard.
 *
 * @package Controllers\Sae
 */
class UpdateSaeDateController implements ControllerInterface
{
    /**
     * Date update route path
     *
     * @var string
     */
    public const PATH = '/sae/update_date';

    /**
     * Main controller method
     *
     * Validates supervisor authentication, updates the submission deadline for all
     * students assigned to the specified SAE, and redirects to dashboard with
     * success or error message.
     *
     * @return void
     */
    public function control(): void
    {
        SessionGuard::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard');
            exit();
        }

        if (!CsrfGuard::validate()) {
            http_response_code(403);
            die('Requête invalide (CSRF).');
        }

        // Verify user is authenticated as a supervisor
        RoleGuard::requireRole('responsable', '/dashboard');

        try {
            $user             = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : [];
            $responsableIdRaw = $user['id'] ?? 0;
            $responsableId    = is_numeric($responsableIdRaw) ? (int) $responsableIdRaw : 0;
            $saeIdRaw         = $_POST['sae_id']       ?? 0;
            $saeId            = is_numeric($saeIdRaw)  ? (int) $saeIdRaw  : 0;

            $newDate = is_string($_POST['date_rendu']  ?? null) ? trim($_POST['date_rendu'])  : '';
            $newTime = is_string($_POST['heure_rendu'] ?? null) ? trim($_POST['heure_rendu']) : '20:00';

            // Combine date and time
            $newDateTime = '';
            if (!empty($newDate)) {
                if (!str_contains($newTime, ':')) {
                    $newTime = '20:00';
                }
                $newDateTime = $newDate . ' ' . $newTime . ':00';
            }

            if ($saeId <= 0 || empty($newDateTime)) {
                $_SESSION['error_message'] = "Tous les champs sont obligatoires.";
                header('Location: /dashboard');
                exit();
            }

            $timestamp = strtotime($newDateTime);
            if ($timestamp === false) {
                $_SESSION['error_message'] = "Format de date ou d'heure invalide.";
                header('Location: /dashboard');
                exit();
            }

            $newDateTime = date('Y-m-d H:i:s', $timestamp);

            SaeAttribution::updateDateRendu($saeId, $responsableId, $newDateTime);

            $formattedDate = date('d/m/Y', $timestamp);
            $formattedTime = date('H:i', $timestamp);
            $_SESSION['success_message'] = "La date de rendu a été modifiée avec succès pour le {$formattedDate} à 
            {$formattedTime} !";

            header('Location: /dashboard');
            exit();
        } catch (DataBaseException $e) {
            $_SESSION['error_message'] = "Erreur de base de données : " . $e->getMessage();
            header('Location: /dashboard');
            exit();
        } catch (\Exception $e) {
            $_SESSION['error_message'] = "Une erreur est survenue : " . $e->getMessage();
            header('Location: /dashboard');
            exit();
        }
    }

    /**
     * Checks if this controller supports the given route and HTTP method.
     *
     * @param string $path   The requested route path.
     * @param string $method The HTTP method (GET, POST, etc.).
     * @return bool True if path is '/sae/update_date' and method is POST.
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}
