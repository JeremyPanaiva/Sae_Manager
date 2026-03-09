<?php

namespace Controllers\Legal;

use Controllers\ControllerInterface;
use Models\User\Log;
use Shared\CsrfGuard;

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
    public const PATH = '/contact';
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
        if (!CsrfGuard::validate()) {
            http_response_code(403);
            die('Invalid request (CSRF).');
        }


        $emailRaw = $_POST['email'] ?? '';
        $email = is_string($emailRaw) ? $emailRaw : '';

        $subjectRaw = $_POST['subject'] ?? '';
        $subject = is_string($subjectRaw) ? $subjectRaw : 'No Subject';


        $Logger = new Log();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }


        $userId = null;

        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            if (isset($_SESSION['user']['id']) && is_numeric($_SESSION['user']['id'])) {
                $userId = (int) $_SESSION['user']['id'];
            }
        }


        $Logger->create(
            $userId,
            'CONTACT_ENVOI',
            'contact_form',
            0,
            "Message de : $email | Subject: $subject"
        );

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
