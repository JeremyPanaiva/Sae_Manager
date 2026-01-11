<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAvis;
use Shared\Exceptions\DataBaseException;

/**
 * SAE feedback controller
 *
 * Handles adding and deleting feedback (avis) on SAE assignments.
 * Accessible to clients and supervisors (responsables) for providing
 * comments and updates on SAE progress.
 *
 * @package Controllers\Sae
 */
class AvisController implements ControllerInterface
{
    /**
     * Route path for adding feedback
     *
     * @var string
     */
    public const PATH_ADD = '/sae/avis/add';

    /**
     * Route path for deleting feedback
     *
     * @var string
     */
    public const PATH_DELETE = '/sae/avis/delete';

    /**
     * Route path for updating feedback
     *
     * @var string
     */
    public const PATH_UPDATE = '/sae/avis/update';

    /**
     * Main controller method
     *
     * Routes POST requests to appropriate handler methods for adding or deleting feedback.
     * Handles exceptions and redirects to dashboard with error messages if operations fail.
     *
     * @return void
     */
    public function control()
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $path = is_string($requestUri) ? parse_url($requestUri, PHP_URL_PATH) : null;
        $method = $_SERVER['REQUEST_METHOD'] ?? '';

        try {
            // Route to appropriate handler based on path
            if ($path === self::PATH_ADD && $method === 'POST') {
                $this->handleAdd();
            } elseif ($path === self::PATH_DELETE && $method === 'POST') {
                $this->handleDelete();
            } elseif ($path === self::PATH_UPDATE && $method === 'POST') {
                $this->handleUpdate();
            }
        } catch (DataBaseException $e) {
            // Store database error message in session
            $_SESSION['error_message'] = $e->getMessage();
        } catch (\Exception $e) {
            // Store generic error message in session
            $_SESSION['error_message'] = "Erreur inattendue :  " . $e->getMessage();
        }

        // Redirect to dashboard
        header("Location: /dashboard");
        exit();
    }

    /**
     * Handles adding new feedback to a SAE
     *
     * Validates user authentication and role (client or supervisor),
     * then creates a new feedback entry for the specified SAE.
     *
     * @return void
     */
    private function handleAdd(): void
    {
        // Verify user authentication
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
            header("Location: /login");
            exit();
        }

        // Verify user has permission to add feedback (client or responsable)
        $roleRaw = $_SESSION['user']['role'] ?? '';
        $role = is_string($roleRaw) ? strtolower($roleRaw) : '';
        if (!in_array($role, ['client', 'responsable'])) {
            header("Location: /dashboard");
            exit();
        }

        // Extract and validate form data
        $saeIdRaw = $_POST['sae_id'] ?? 0;
        $saeId = is_numeric($saeIdRaw) ? (int)$saeIdRaw : 0;
        $userIdRaw = $_SESSION['user']['id'] ?? 0;
        $userId = is_numeric($userIdRaw) ? (int)$userIdRaw : 0;
        $messageRaw = $_POST['message'] ?? '';
        $message = is_string($messageRaw) ? trim($messageRaw) : '';

        // Create feedback if all required data is valid
        if ($saeId > 0 && $userId > 0 && $message !== '') {
            SaeAvis::add($saeId, $userId, $message);
        }
    }

    /**
     * Handles deleting feedback from a SAE
     *
     * Validates user authentication and deletes the specified feedback entry.
     * Note: Authorization checks should be performed in the model layer
     * to ensure users can only delete their own feedback.
     *
     * @return void
     */
    private function handleDelete(): void
    {
        // Verify user authentication
        if (!isset($_SESSION['user'])) {
            header("Location: /login");
            exit();
        }

        // Extract feedback ID and delete if valid
        $avisIdRaw = $_POST['avis_id'] ?? 0;
        $avisId = is_numeric($avisIdRaw) ? (int)$avisIdRaw : 0;
        if ($avisId > 0) {
            SaeAvis::delete($avisId);
        }
    }

    /**
     * Handles updating feedback from a SAE
     *
     * Validates user authentication and role (client only, not responsable),
     * then updates the feedback message if the user is the author.
     *
     * @return void
     */
    private function handleUpdate(): void
    {
        // Verify user authentication
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
            header("Location: /login");
            exit();
        }

        // Only clients can update feedback (responsables can only delete)
        $roleRaw = $_SESSION['user']['role'] ?? '';
        $role = is_string($roleRaw) ? strtolower($roleRaw) : '';
        if ($role !== 'client') {
            $_SESSION['error_message'] = "Seuls les clients peuvent modifier leurs remarques.";
            header("Location: /dashboard");
            exit();
        }

        // Extract and validate form data
        $avisIdRaw = $_POST['avis_id'] ?? 0;
        $avisId = is_numeric($avisIdRaw) ? (int)$avisIdRaw : 0;
        $userIdRaw = $_SESSION['user']['id'] ?? 0;
        $userId = is_numeric($userIdRaw) ? (int)$userIdRaw : 0;
        $messageRaw = $_POST['message'] ?? '';
        $message = is_string($messageRaw) ? trim($messageRaw) : '';

        // Update feedback if all required data is valid
        if ($avisId > 0 && $userId > 0 && $message !== '') {
            SaeAvis::update($avisId, $userId, $message);
            $_SESSION['success_message'] = "Remarque modifiée avec succès.";
        }
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path matches feedback routes and method is POST
     */
    public static function support(string $path, string $method): bool
    {
        return (($path === self::PATH_ADD
                || $path === self::PATH_DELETE ||
                $path === self::PATH_UPDATE) && $method === 'POST');
    }
}
