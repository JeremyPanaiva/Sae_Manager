<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\Database;
use Shared\Exceptions\DataBaseException;

/**
 * Change password submission controller
 *
 * Handles POST requests for authenticated users to change their password.
 * Validates the current password, checks new password complexity requirements,
 * and ensures the new password differs from the current one.
 *
 * @package Controllers\User
 */
class ChangePasswordPost implements ControllerInterface
{
    /**
     * Change password route path
     *
     * @var string
     */
    public const PATH = '/user/change-password';

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/user/change-password' and method is POST
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }

    /**
     * Main controller method
     *
     * Validates password change request, verifies current password, checks new
     * password meets complexity requirements, ensures new password is different,
     * and updates the password in the database.
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
    public function control(): void
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verify user is authenticated
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
            header('Location: /login');
            exit;
        }

        // Extract user ID and form data
        $userIdRaw = $_SESSION['user']['id'];
        $userId = is_numeric($userIdRaw) ? (int)$userIdRaw : 0;
        $oldPasswordRaw = $_POST['old_password'] ?? '';
        $oldPassword = is_string($oldPasswordRaw) ? $oldPasswordRaw : '';
        $newPasswordRaw = $_POST['new_password'] ?? '';
        $newPassword = is_string($newPasswordRaw) ? $newPasswordRaw : '';
        $confirmPasswordRaw = $_POST['confirm_password'] ?? '';
        $confirmPassword = is_string($confirmPasswordRaw) ? $confirmPasswordRaw : '';

        // Validate required fields
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            header('Location: /user/change-password?error=missing_fields');
            exit;
        }

        // Verify passwords match
        if ($newPassword !== $confirmPassword) {
            header('Location: /user/change-password?error=passwords_dont_match');
            exit;
        }

        // Validate minimum password length
        if (strlen($newPassword) < 8) {
            header('Location: /user/change-password?error=password_too_short');
            exit;
        }

        // Validate password contains uppercase letter
        if (!preg_match('/[A-Z]/', $newPassword)) {
            header('Location: /user/change-password?error=password_no_uppercase');
            exit;
        }

        // Validate password contains lowercase letter
        if (!preg_match('/[a-z]/', $newPassword)) {
            header('Location: /user/change-password?error=password_no_lowercase');
            exit;
        }

        // Validate password contains digit
        if (!preg_match('/[0-9]/', $newPassword)) {
            header('Location: /user/change-password?error=password_no_digit');
            exit;
        }

        try {
            $conn = Database::getConnection();

            // Retrieve current password hash from database
            $stmt = $conn->prepare("SELECT mdp FROM users WHERE id = ?");
            if (!$stmt) {
                throw new DataBaseException("Erreur de préparation SQL");
            }

            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result === false) {
                throw new DataBaseException("Erreur lors de la récupération du résultat");
            }

            $user = $result->fetch_assoc();
            $stmt->close();

            // Verify current password is correct
            if ($user === null) {
                header('Location:  /user/change-password?error=wrong_password');
                exit;
            }

            $currentPasswordHash = isset($user['mdp']) && is_string($user['mdp']) ? $user['mdp'] : '';
            if ($currentPasswordHash === '' || !password_verify($oldPassword, $currentPasswordHash)) {
                header('Location:  /user/change-password?error=wrong_password');
                exit;
            }

            // Ensure new password is different from current password
            if (password_verify($newPassword, $currentPasswordHash)) {
                header('Location: /user/change-password? error=same_password');
                exit;
            }

            // Hash and update the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);


            $updateStmt = $conn->prepare("UPDATE users SET mdp = ? WHERE id = ?");
            if (!$updateStmt) {
                throw new DataBaseException("Erreur de préparation SQL update");
            }

            $updateStmt->bind_param("si", $hashedPassword, $userId);
            $updateStmt->execute();
            $updateStmt->close();

            // Redirect with success message
            header('Location:  /user/change-password?success=password_updated');
            exit;
        } catch (\Exception $e) {
            // Log error and redirect with error message
            error_log("Erreur changement mot de passe: " . $e->getMessage());
            header('Location: /user/change-password? error=database_error');
            exit;
        }
    }
}
