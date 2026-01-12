<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Shared\Exceptions\DataBaseException;

/**
 * SAE submission date update controller
 *
 * Handles updates to SAE submission deadlines (date de rendu) by supervisors (responsables).
 * The updated deadline applies to all students assigned to the SAE by the supervisor.
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
    public function control()
    {
        // Démarrer la session si ce n'est pas déjà fait
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Ensure POST method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard');
            exit();
        }

        // Verify user is authenticated as a supervisor
        if (
            !isset($_SESSION['user']) ||
            !is_array($_SESSION['user']) ||
            !isset($_SESSION['user']['role']) ||
            !is_string($_SESSION['user']['role']) ||
            strtolower($_SESSION['user']['role']) !== 'responsable'
        ) {
            header('Location: /dashboard');
            exit();
        }

        try {
            // Extract supervisor ID and form data
            $responsableIdRaw = $_SESSION['user']['id'] ?? 0;
            $responsableId = is_numeric($responsableIdRaw) ? (int)$responsableIdRaw : 0;
            $saeIdRaw = $_POST['sae_id'] ?? 0;
            $saeId = is_numeric($saeIdRaw) ? (int)$saeIdRaw : 0;
            $newDateRaw = $_POST['date_rendu'] ?? '';
            $newDate = is_string($newDateRaw) ? $newDateRaw : '';

            // Validate required fields
            if ($saeId <= 0 || !$newDate) {
                $_SESSION['error_message'] = "Tous les champs sont obligatoires. ";
                header('Location: /dashboard');
                exit();
            }

            // Update submission deadline for all students assigned to this SAE by this supervisor
            SaeAttribution::updateDateRendu($saeId, $responsableId, $newDate);

            // Set success message in session with formatted date
            $timestamp = strtotime($newDate);
            $formattedDate = $timestamp !== false ? date('d/m/Y', $timestamp) : $newDate;
            $_SESSION['success_message'] = "La date de rendu a été modifiée avec succès pour le {$formattedDate} !";

            // Redirect to dashboard
            header('Location: /dashboard');
            exit();
        } catch (DataBaseException $e) {
            // Database error
            $_SESSION['error_message'] = "Erreur de base de données :  " . $e->getMessage();
            header('Location: /dashboard');
            exit();
        } catch (\Exception $e) {
            // Generic error handling
            $_SESSION['error_message'] = "Une erreur est survenue :  " . $e->getMessage();
            header('Location: /dashboard');
            exit();
        }
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/sae/update_date' and method is POST
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}
