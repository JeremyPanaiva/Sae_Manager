<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Models\User\PasswordResetToken;
use Models\User\EmailService;
use Shared\Exceptions\DataBaseException;

/**
 * Forgot password submission controller
 *
 * Handles POST requests from the forgot password form. Validates the email,
 * creates a password reset token, and sends a reset link to the user's email.
 *
 * @package Controllers\User
 */
class ForgotPasswordPost implements ControllerInterface
{
    /**
     * Forgot password route path
     *
     * @var string
     */
    public const PATH = "/user/forgot-password";

    /**
     * Main controller method
     *
     * Validates the email address, checks if user exists, creates a reset token,
     * and sends a password reset email. Always shows success message for security
     * (doesn't reveal if email exists in database).
     *
     * @return void
     */
    public function control()
    {
        // Ensure POST method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /user/forgot-password');
            exit;
        }

        // Extract and validate email
        $emailRaw = $_POST['email'] ?? '';
        $email = is_string($emailRaw) ? trim($emailRaw) : '';

        // Validate email format
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: /user/forgot-password?error=invalid_email');
            exit;
        }

        try {
            // Check if user exists
            $userModel = new User();
            $user = $userModel->findByEmail($email);

            // Only send email if user exists (but always show success message for security)
            if ($user) {
                // Create password reset token
                $tokenModel = new PasswordResetToken();
                $token = $tokenModel->createToken($email);

                // Send password reset email
                $emailService = new EmailService();
                $emailService->sendPasswordResetEmail($email, $token);
            }

            // Always redirect with success message (don't reveal if email exists)
            header('Location: /user/forgot-password?success=email_sent');
            exit;
        } catch (DataBaseException $e) {
            // Log error with stack trace
            error_log("Database error in ForgotPasswordPost: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            header('Location: /user/forgot-password?error=database_error');
            exit;
        } catch (\Exception $e) {
            // Log generic error
            error_log("Error in ForgotPasswordPost: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            header('Location: /user/forgot-password?error=general_error');
            exit;
        }
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * Supports both standard path and legacy query parameter format.
     *
     * @param string $chemin The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path matches forgot password route and method is POST
     */
    public static function support(string $chemin, string $method): bool
    {
        return ($chemin === self::PATH ||
                (isset($_GET['page']) && $_GET['page'] === 'forgot-password'))
            && $method === "POST";
    }
}
