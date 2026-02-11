<?php

namespace Controllers\Legal;

use Controllers\ControllerInterface;
use Models\User\Log;

/**
 * Class ContactPost
 *
 * Handles the submission of the contact form.
 * It processes input data and logs the communication attempt using the Audit Logger.
 *
 * @package Controllers\Legal
 */
class ContactPost implements ControllerInterface
{
    /**
     * Executes the contact controller logic.
     *
     * 1. Sanitizes and strictly types input data (fixing PHPStan 'mixed' errors).
     * 2. Safely retrieves user session data (fixing PHPStan 'offset access' errors).
     * 3. Logs the event and redirects the user.
     *
     * @return void
     */
    public function control()
    {
        // 1. Strict Input Typing (Fixes: "Part of encapsed string cannot be cast to string")
        // We ensure variables are strictly strings before using them.
        $emailRaw = $_POST['email'] ?? '';
        $email = is_string($emailRaw) ? $emailRaw : '';

        $subjectRaw = $_POST['subject'] ?? '';
        $subject = is_string($subjectRaw) ? $subjectRaw : 'No Subject';

        // (Insert your email sending logic here)

        // 2. Audit Logging
        $Logger = new Log();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 3. Safe Session Access (Fixes: "Cannot access offset 'id' on mixed")
        // We must verify that $_SESSION['user'] is an array before accessing keys.
        $userId = null;

        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            if (isset($_SESSION['user']['id']) && is_numeric($_SESSION['user']['id'])) {
                $userId = (int) $_SESSION['user']['id'];
            }
        }

        // Create the log entry
        // We use 0 as element_id because this is a generic action not tied to a specific DB row.
        $Logger->create(
            $userId,
            'CONTACT_ENVOI',
            'contact_form',
            0,
            "Message from: $email | Subject: $subject"
        );

        // Redirect with success flag
        header("Location: /contact?success=1");
        exit();
    }

    /**
     * Checks if the router supports this controller.
     *
     * @param string $chemin The request path.
     * @param string $method The HTTP method.
     * @return bool True if path is '/contact' and method is POST.
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === "/contact" && $method === "POST";
    }
}
