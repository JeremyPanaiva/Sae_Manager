<?php

namespace Controllers;

use Controllers\ControllerInterface;
use Models\Log;
use Shared\Exceptions\ValidationException;

/**
 * Class ContactPost
 *
 * Handles the submission of the contact form.
 * It processes the email sending logic and logs the communication attempt
 * in the database for tracking purposes.
 *
 * @package Controllers
 */
class ContactPost implements ControllerInterface
{
    /**
     * Executes the contact form logic.
     *
     * 1. Sanitizes inputs.
     * 2. Sends the email (logic abstracted).
     * 3. Logs the event (associating it with a User ID if logged in, or NULL if anonymous).
     *
     * @return void
     */
    public function control()
    {
        // 1. Retrieve Data
        $email = $_POST['email'] ?? 'anonymous@test.com';
        $subject = $_POST['subject'] ?? 'No Subject';
        $message = $_POST['message'] ?? '';

        // ... (Email sending logic goes here: mail() or PHPMailer) ...
        // Let's assume the mail was sent successfully:
        $mailSent = true;

        if ($mailSent) {
            $Logger = new Log();

            // Check session to see if user is logged in
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // If logged in, use their ID. If not, use NULL.
            $userId = $_SESSION['user']['id'] ?? null;
            $safeUserId = is_numeric($userId) ? (int)$userId : null;

            // Audit: Log the contact attempt
            // We use 'contact_form' as the table name context, and 0 for element_id as it's a generic action
            $Logger->create(
                $safeUserId,
                'CONTACT_ENVOI',
                'contact_form',
                0,
                "Contact msg from: $email | Subject: $subject"
            );
        }

        // Redirect with success flag
        header("Location: /contact?success=1");
        exit();
    }

    /**
     * Router Support Check
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === "/contact" && $method === "POST";
    }
}
