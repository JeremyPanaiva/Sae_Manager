<?php

namespace Controllers\Legal;

use Controllers\ControllerInterface;
use Models\User\EmailService;

/**
 * Contact form submission controller
 *
 * Handles POST requests from the contact form.  Validates user input,
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
     * Validates contact form data (email, subject, message), sends the email
     * via EmailService, and redirects with query parameters indicating success or failure.
     *
     * Validation errors:
     * - missing_fields: One or more required fields are empty
     * - invalid_email:  Email format is invalid
     * - mail_failed: Email sending failed
     *
     * @return void
     */
    public function control(): void
    {
        // Ensure POST method (redundant check, but safe)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location:  ' . self::PATH);
            exit();
        }

        // Extract and sanitize form data
        $email   = trim($_POST['email']   ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

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
                header('Location: ' .  self::PATH . '?success=message_sent');
            } else {
                header('Location: ' . self::PATH . '?error=mail_failed');
            }
        } catch (\Throwable $e) {
            // Log exception and redirect with error
            error_log('ContactPost error: ' . $e->getMessage());
            header('Location: ' .  self::PATH . '?error=mail_failed');
        }

        exit();
    }
}