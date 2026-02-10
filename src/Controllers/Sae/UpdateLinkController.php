<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;
use Models\Database;

/**
 * UpdateLinkController
 *
 * Handles the submission and update of project delivery links (GitHub, Google Drive, etc.)
 * for an entire student group. This controller ensures that only users with the 'etudiant'
 * role can modify the link for their assigned SAE.
 *
 * @package Controllers\Sae
 */
class UpdateLinkController implements ControllerInterface
{
    /**
     * Route path for updating the SAE project link
     * @var string
     */
    public const PATH = '/sae/update_link';

    /**
     * Main controller method
     *
     * Validates student session, retrieves the supervisor ID to identify the group,
     * validates the URL format, and updates the 'github_link' field for all
     * team members in the sae_attributions table.
     *
     * @return void
     */
    public function control()
    {
        // Ensure the request is sent via POST method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard');
            exit();
        }

        /** @var array<string, mixed>|null $userSession */
        $userSession = $_SESSION['user'] ?? null;

        // Access Control check
        if (
            !is_array($userSession) ||
            !isset($userSession['role']) ||
            !is_string($userSession['role']) ||
            strtolower($userSession['role']) !== 'etudiant'
        ) {
            header('HTTP/1.1 403 Forbidden');
            exit("Access Denied.");
        }

        // Explicitly handle mixed types from global arrays
        $saeIdRaw = $_POST['sae_id'] ?? 0;
        $githubLinkRaw = $_POST['github_link'] ?? '';
        $userIdRaw = $userSession['id'] ?? 0;

        // Secure casting and cleaning
        $saeId = is_numeric($saeIdRaw) ? (int)$saeIdRaw : 0;
        $githubLink = is_string($githubLinkRaw) ? trim($githubLinkRaw) : '';
        $userId = is_numeric($userIdRaw) ? (int)$userIdRaw : 0;

        try {
            // URL validation
            if (!empty($githubLink) && !filter_var($githubLink, FILTER_VALIDATE_URL)) {
                throw new \Exception("The link format is invalid. Please provide a full URL.");
            }

            Database::checkConnection();
            $db = Database::getConnection();

            // 1. Identify the supervisor (responsable) to target the entire group
            $stmt = $db->prepare("SELECT responsable_id FROM sae_attributions 
                      WHERE sae_id = ? AND student_id = ? LIMIT 1");
            if (!$stmt) {
                throw new \Exception("Database error during group identification.");
            }

            $stmt->bind_param("ii", $saeId, $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();

            if (!$row) {
                throw new \Exception("Assignment not found or unauthorized.");
            }

            $responsableId = (int)$row['responsable_id'];

            // 2. Update the link for the whole team using the updated Model method
            SaeAttribution::updateGithubLink($saeId, $responsableId, $githubLink);

            $_SESSION['success_message'] = "Project link updated for the entire team.";
        } catch (\Exception $e) {
            $_SESSION['error_message'] = "Update failed: " . $e->getMessage();
        }

        header('Location: /dashboard');
        exit();
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method
     * @return bool True if path matches and method is POST
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}
