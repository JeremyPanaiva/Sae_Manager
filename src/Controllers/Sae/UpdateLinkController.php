<?php

namespace Controllers\Sae;

use Controllers\ControllerInterface;
use Models\Sae\SaeAttribution;

/**
 * UpdateLinkController
 *
 * Handles the submission and update of project delivery links (GitHub, Google Drive, etc.)
 * by students. This controller ensures that only users with the 'etudiant' role can
 * modify the link for their assigned SAE.
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
     * Validates student session, checks permissions, validates the URL format,
     * and updates the 'github_link' field in the sae_attributions table.
     * Redirects back to the dashboard with a success or error message.
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

        // Access Control check with explicit type validation for PHPStan
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
            // URL validation: ensure the link is a valid URL if not empty
            if (!empty($githubLink) && !filter_var($githubLink, FILTER_VALIDATE_URL)) {
                throw new \Exception("The link format is invalid. " .
                    "Please provide a full URL (starting with http:// or https://).");
            }

            // Update the link in the database via the model
            SaeAttribution::updateGithubLink($saeId, $userId, $githubLink);

            // Set success feedback for the user
            $_SESSION['success_message'] = "Project link updated successfully.";
        } catch (\Exception $e) {
            // Log and display error feedback
            $_SESSION['error_message'] = "Update failed: " . $e->getMessage();
        }

        // Return to the dashboard to view changes
        header('Location: /dashboard');
        exit();
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path matches and method is POST
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }
}
