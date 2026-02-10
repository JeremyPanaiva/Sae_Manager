<?php

namespace Controllers\Legal;

use Controllers\ControllerInterface;
use Models\User\EmailService;
use Models\Database;

/**
 * Contact form submission controller
 *
 * Handles POST requests from the contact form. Validates user input,
 * sends the contact email via EmailService, and redirects with appropriate
 * success or error messages.
 *
 * @package Controllers\Legal
 */
class ContactPost implements ControllerInterface
{
    /**
     * Contact form submission route path
     *
     * @var string
     */
    public const PATH = '/contact';

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/contact' and method is POST
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }

    /**
     * Main controller method
     *
     * Validates contact form data, sends the email via EmailService,
     * logs the action in the database manually, and redirects.
     *
     * @return void
     */
    public function control(): void
    {
        // Ensure POST method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location:  ' . self::PATH);
            exit();
        }

        // Extract and sanitize form data
        $email   = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
        $subject = isset($_POST['subject']) && is_string($_POST['subject']) ? trim($_POST['subject']) : '';
        $message = isset($_POST['message']) && is_string($_POST['message']) ? trim($_POST['message']) : '';

        // Validate required fields
        if ($email === '' || $subject === '' || $message === '') {
            header('Location: ' .  self::PATH . '?error=missing_fields');
            exit();
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . self::PATH . '?error=invalid_email');
            exit();
        }

        try {
            // Send contact email
            $mailer = new EmailService();
            $ok = $mailer->sendContactEmail($email, $subject, $message);

            // Redirect based on email sending result
            if ($ok) {
                // --- DEBUT LOG MANUEL ---
                try {
                    $db = Database::getConnection();

                    $userId = null;
                    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
                        if (isset($_SESSION['user']['id']) && is_numeric($_SESSION['user']['id'])) {
                            $userId = (int) $_SESSION['user']['id'];
                        }
                    }

                    // On prépare le message de détails
                    $details = "Sujet : " . substr($subject, 0, 50) . " | Email contact : " . $email;

                    // On coupe la requête en deux pour respecter la limite de 120 caractères (PHPCS)
                    $sql = "INSERT INTO logs (user_id, action, table_concernee, element_id, details) " .
                        "VALUES (?, 'CONTACT_ENVOI', 'system', 0, ?)";

                    $stmt = $db->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param('is', $userId, $details);
                        $stmt->execute();
                        $stmt->close();
                    }
                } catch (\Throwable $e) {
                    error_log("Erreur Log Contact: " . $e->getMessage());
                }
                // --- FIN LOG MANUEL ---

                header('Location: ' .  self::PATH . '?success=message_sent');
            } else {
                header('Location: ' . self::PATH . '?error=mail_failed');
            }
        } catch (\Throwable $e) {
            error_log('ContactPost error: ' . $e->getMessage());
            header('Location: ' .  self::PATH . '?error=mail_failed');
        }

        exit();
    }
}
