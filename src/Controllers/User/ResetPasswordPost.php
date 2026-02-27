<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\PasswordResetToken;
use Models\Database;
use Models\User\Log; // Ajout de l'import pour la journalisation
use Shared\Exceptions\DataBaseException;
use Shared\Exceptions\SamePasswordException;

/**
 * Class ResetPasswordPost
 *
 * Password reset submission controller.
 * Handles the POST request from the password reset form. Validates the reset token,
 * checks password complexity requirements, ensures the new password is different from
 * the old one, and updates the user's password in the database.
 * * Injects a SQL session variable to help database triggers distinguish this action
 * from a standard profile password change. All failed attempts are logged for auditing.
 *
 * @package Controllers\User
 */
class ResetPasswordPost implements ControllerInterface
{
    /**
     * Password reset route path.
     * @var string
     */
    public const PATH = "/user/reset-password";

    /**
     * Main controller method.
     *
     * Validates form data, verifies the reset token to retrieve the user's email,
     * checks password requirements, ensures the new password differs from the current one,
     * and updates the password. Redirects with appropriate error or success messages
     * and logs any security violations.
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
        // Ensure POST method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /user/forgot-password');
            exit;
        }

        $logger = new Log(); // Instanciation du logger

        // Extract form data
        $tokenRaw = $_POST['token'] ?? '';
        $token = is_string($tokenRaw) ? $tokenRaw : '';

        $passwordRaw = $_POST['password'] ?? '';
        $password = is_string($passwordRaw) ? $passwordRaw : '';

        $confirmPasswordRaw = $_POST['confirm_password'] ?? '';
        $confirmPassword = is_string($confirmPasswordRaw) ? $confirmPasswordRaw : '';

        // Validate required fields
        if (empty($token) || empty($password) || empty($confirmPassword)) {
            // Log as anonymous because we don't know the user yet
            $logger->create(
                null,
                'ECHEC_REINITIALISATION_MDP',
                'users',
                0,
                "Champs manquants lors de la soumission du formulaire de réinitialisation"
            );
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=missing_fields');
            exit;
        }

        try {
            // 1. Validate reset token FIRST to retrieve associated email and identify the user
            $tokenModel = new PasswordResetToken();
            $email = $tokenModel->validateToken($token);

            if (!$email) {
                // Token is invalid or expired
                $logger->create(
                    null,
                    'ECHEC_REINITIALISATION_MDP',
                    'users',
                    0,
                    "Tentative de réinitialisation avec un jeton invalide ou expiré"
                );
                header('Location: /user/forgot-password?error=invalid_token');
                exit;
            }

            // Retrieve current password hash and user ID
            $conn = Database::getConnection();
            $stmt = $conn->prepare("SELECT id, mdp FROM users WHERE mail = ?");
            if (!$stmt) {
                throw new DataBaseException("SQL prepare failed in user check.");
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result === false) {
                throw new DataBaseException("Failed to get result from query.");
            }

            $row = $result->fetch_assoc();
            $stmt->close();

            // We need the ID for accurate logging
            $userIdRaw = $row['id'] ?? 0;
            $userId = is_numeric($userIdRaw) ? (int)$userIdRaw : 0;

            if ($row === null) {
                // Edge case: Token exists but user doesn't
                $logger->create(
                    null,
                    'ECHEC_REINITIALISATION_MDP',
                    'users',
                    0,
                    "Jeton valide mais utilisateur introuvable pour l'email : $email"
                );
                header('Location: /user/forgot-password?error=invalid_token');
                exit;
            }

            // 2. NOW perform password complexity validations (so we can log them with the correct user ID)

            // Verify passwords match
            if ($password !== $confirmPassword) {
                $logger->create(
                    $userId,
                    'ECHEC_REINITIALISATION_MDP',
                    'users',
                    $userId,
                    "La confirmation du nouveau mot de passe ne correspond pas"
                );
                header('Location: /user/reset-password?token=' . urlencode($token) . '&error=passwords_dont_match');
                exit;
            }

            // Validate minimum password length
            if (strlen($password) < 8) {
                $logger->create(
                    $userId,
                    'ECHEC_REINITIALISATION_MDP',
                    'users',
                    $userId,
                    "Nouveau mot de passe trop court"
                );
                header('Location: /user/reset-password?token=' . urlencode($token) . '&error=password_too_short');
                exit;
            }

            // Validate password contains uppercase letter
            if (!preg_match('/[A-Z]/', $password)) {
                $logger->create(
                    $userId,
                    'ECHEC_REINITIALISATION_MDP',
                    'users',
                    $userId,
                    "Nouveau mot de passe sans majuscule"
                );
                header('Location: /user/reset-password?token=' . urlencode($token) . '&error=password_no_uppercase');
                exit;
            }

            // Validate password contains lowercase letter
            if (!preg_match('/[a-z]/', $password)) {
                $logger->create(
                    $userId,
                    'ECHEC_REINITIALISATION_MDP',
                    'users',
                    $userId,
                    "Nouveau mot de passe sans minuscule"
                );
                header('Location: /user/reset-password?token=' . urlencode($token) . '&error=password_no_lowercase');
                exit;
            }

            // Validate password contains digit
            if (!preg_match('/[0-9]/', $password)) {
                $logger->create(
                    $userId,
                    'ECHEC_REINITIALISATION_MDP',
                    'users',
                    $userId,
                    "Nouveau mot de passe sans chiffre"
                );
                header('Location: /user/reset-password?token=' . urlencode($token) . '&error=password_no_digit');
                exit;
            }

            // Check if new password is the same as current password
            $currentPasswordHash = isset($row['mdp']) && is_string($row['mdp']) ? $row['mdp'] : '';
            if ($currentPasswordHash !== '' && password_verify($password, $currentPasswordHash)) {
                // Audit: Log attempt to reuse the exact same password
                $logger->create(
                    $userId,
                    'ECHEC_REINITIALISATION_MDP',
                    'users',
                    $userId,
                    "Tentative de réutilisation de l'ancien mot de passe"
                );
                throw new SamePasswordException();
            }

            // Hash and update the new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Indique au Trigger SQL qu'il s'agit d'une réinitialisation (mot de passe oublié)
            $conn->query("SET @pwd_action_type = 'RESET'");

            $stmt = $conn->prepare("UPDATE users SET mdp = ?, last_password_change = NOW() WHERE mail = ?");
            if (!$stmt) {
                throw new DataBaseException("SQL prepare failed in reset password.");
            }
            $stmt->bind_param("ss", $hashedPassword, $email);
            $stmt->execute();
            $stmt->close();

            // Delete the used token to prevent reuse
            $tokenModel->deleteToken($token);

            // Redirect to login with success message (Success log handled by SQL trigger)
            header('Location: /user/login?success=password_reset');
            exit;
        } catch (SamePasswordException $e) {
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=same_password');
            exit;
        } catch (DataBaseException $e) {
            $logger->create(
                $userId ?? null,
                'ERREUR_SYSTEME',
                'users',
                $userId ?? 0,
                "Erreur base de données lors de la réinitialisation du MDP : " . $e->getMessage()
            );
            error_log("Erreur base de données dans ResetPasswordPost: " . $e->getMessage());
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=database_error');
            exit;
        } catch (\Exception $e) {
            $logger->create(
                $userId ?? null,
                'ERREUR_SYSTEME',
                'users',
                $userId ?? 0,
                "Erreur système lors de la réinitialisation du MDP : " . $e->getMessage()
            );
            error_log("Erreur générale dans ResetPasswordPost: " . $e->getMessage());
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=general_error');
            exit;
        }
    }

    /**
     * Checks if this controller supports the given route and HTTP method.
     *
     * Supports both standard path and legacy query parameter format.
     *
     * @param string $chemin The requested route path.
     * @param string $method The HTTP method (GET, POST, etc.).
     * @return bool True if path matches reset password route and method is POST.
     */
    public static function support(string $chemin, string $method): bool
    {
        return ($chemin === self::PATH ||
                (isset($_GET['page']) && $_GET['page'] === 'reset-password'))
            && $method === "POST";
    }
}
