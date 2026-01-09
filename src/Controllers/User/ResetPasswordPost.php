<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\PasswordResetToken;
use Models\Database;
use Shared\Exceptions\DataBaseException;
use Shared\Exceptions\SamePasswordException;

/**
 * Password reset submission controller
 *
 * Handles the POST request from the password reset form.   Validates the reset token,
 * checks password complexity requirements, ensures the new password is different from
 * the old one, and updates the user's password in the database.
 *
 * @package Controllers\User
 */
class ResetPasswordPost implements ControllerInterface
{
    /**
     * Password reset route path
     *
     * @var string
     */
    public const PATH = "/user/reset-password";

    /**
     * Main controller method
     *
     * Validates form data, checks password requirements, verifies the reset token,
     * ensures the new password differs from the current one, and updates the password.
     * Redirects with appropriate error or success messages.
     *
     * Password requirements:
     * - Minimum 8 characters
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one digit
     * - Must differ from current password
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

        // Extract form data
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ??  '';

        // Validate required fields
        if (empty($token) || empty($password) || empty($confirmPassword)) {
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=missing_fields');
            exit;
        }

        // Verify passwords match
        if ($password !== $confirmPassword) {
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=passwords_dont_match');
            exit;
        }

        // Validate minimum password length
        if (strlen($password) < 8) {
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=password_too_short');
            exit;
        }

        // Validate password contains uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            header('Location: /user/reset-password? token=' . urlencode($token) . '&error=password_no_uppercase');
            exit;
        }

        // Validate password contains lowercase letter
        if (! preg_match('/[a-z]/', $password)) {
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=password_no_lowercase');
            exit;
        }

        // Validate password contains digit
        if (!preg_match('/[0-9]/', $password)) {
            header('Location:  /user/reset-password?token=' . urlencode($token) . '&error=password_no_digit');
            exit;
        }

        try {
            // Validate reset token and retrieve associated email
            $tokenModel = new PasswordResetToken();
            $email = $tokenModel->validateToken($token);

            if (!$email) {
                // Token is invalid or expired
                header('Location: /user/forgot-password?error=invalid_token');
                exit;
            }

            // Retrieve current password hash to check if new password is different
            $conn = Database::getConnection();
            $stmt = $conn->prepare("SELECT mdp FROM users WHERE mail = ?");
            if (!$stmt) {
                throw new DataBaseException("SQL prepare failed in user check.");
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                // Check if new password is the same as current password
                if (password_verify($password, $row['mdp'])) {
                    throw new SamePasswordException();
                }
            } else {
                // User not found
                header('Location: /user/forgot-password?error=invalid_token');
                exit;
            }
            $stmt->close();

            // Hash and update the new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET mdp = ?  WHERE mail = ?");
            if (!$stmt) {
                throw new DataBaseException("SQL prepare failed in reset password.");
            }
            $stmt->bind_param("ss", $hashedPassword, $email);
            $stmt->execute();
            $stmt->close();

            // Delete the used token to prevent reuse
            $tokenModel->deleteToken($token);

            // Redirect to login with success message
            header('Location: /user/login?success=password_reset');
            exit;
        } catch (SamePasswordException $e) {
            // New password is identical to current password
            header('Location: /user/reset-password? token=' . urlencode($token) . '&error=same_password');
            exit;
        } catch (DataBaseException $e) {
            // Database error
            error_log("Erreur base de données dans ResetPasswordPost: " . $e->getMessage());
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=database_error');
            exit;
        } catch (\Exception $e) {
            // Generic error handling
            error_log("Erreur générale dans ResetPasswordPost: " . $e->getMessage());
            header('Location: /user/reset-password?token=' .  urlencode($token) . '&error=general_error');
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
     * @return bool True if path matches reset password route and method is POST
     */
    public static function support(string $chemin, string $method): bool
    {
        return ($chemin === self::PATH ||
                (isset($_GET['page']) && $_GET['page'] === 'reset-password'))
            && $method === "POST";
    }
}
