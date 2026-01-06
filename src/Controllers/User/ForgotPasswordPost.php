<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Models\User\PasswordResetToken;
use Models\User\EmailService;
use Shared\Exceptions\DataBaseException;
use Shared\Exceptions\EmailNotFoundException;

/**
 * Forgot password submission controller
 *
 * Handles POST requests from the forgot password form.  Validates the email address,
 * generates a password reset token, and sends a password reset email to the user.
 * Implements security best practice by always showing success message regardless of
 * whether the email exists, preventing email enumeration attacks.
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
     * Validates the submitted email address, generates a password reset token,
     * and sends a reset email.   Always displays success message to prevent
     * email enumeration attacks (security best practice).
     *
     * @return void
     */
    public function control()
    {
        // Ensure POST method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ? page=forgot-password');
            exit;
        }

        // Extract email from form
        $email = $_POST['email'] ?? '';

        // Validate email is provided
        if (empty($email)) {
            header('Location: ? page=forgot-password&error=email_required');
            exit;
        }

        try {
            // Check if user exists with this email
            $userModel = new User();
            $user = $userModel->findByEmail($email);

            if (!$user) {
                // Security: Don't reveal that email doesn't exist
                // Show success message to prevent email enumeration
                header('Location: ?page=forgot-password&success=email_sent');
                exit;
            }

            // Generate and save password reset token
            $tokenModel = new PasswordResetToken();
            $token = $tokenModel->createToken($email);

            // Send password reset email
            try {
                $emailService = new EmailService();
                $emailService->sendPasswordResetEmail($email, $token);
            } catch (\Exception $e) {
                // Log email sending error but don't expose to user
                error_log("Erreur SMTP lors de l'envoi de l'email de réinitialisation: " . $e->getMessage());
                if (method_exists($e, 'getTraceAsString')) {
                    error_log($e->getTraceAsString());
                }
            }

            // Redirect with success message
            header('Location:  ?page=forgot-password&success=email_sent');
            exit;

        } catch (DataBaseException $e) {
            // Database error
            error_log("Erreur base de données dans ForgotPasswordPost: " . $e->getMessage());
            header('Location: ? page=forgot-password&error=database_error');
            exit;
        } catch (\Exception $e) {
            // Generic error handling
            error_log("Erreur générale dans ForgotPasswordPost: " . $e->getMessage());
            header('Location: ? page=forgot-password&error=general_error');
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
    static function support(string $chemin, string $method): bool
    {
        return ($chemin === self::PATH ||
                (isset($_GET['page']) && $_GET['page'] === 'forgot-password'))
            && $method === "POST";
    }
}