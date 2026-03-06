<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\Database;
use Models\User\Log;
use Models\User\EmailService;
use Shared\Exceptions\DataBaseException;
use Shared\SessionGuard;

/**
 * Class ChangePasswordPost
 *
 * Change password submission controller.
 * Handles POST requests for authenticated users to change their password from their profile.
 * Validates the current password, checks new password complexity requirements,
 * enforces a 24-hour rate limit between changes, and ensures the new password
 * differs from the current one.
 * * Injects a SQL session variable to help database triggers distinguish this action
 * from a password reset. All failed attempts are logged for security auditing.
 *
 * @package Controllers\User
 */
class ChangePasswordPost implements ControllerInterface
{
    /**
     * Change password route path.
     * @var string
     */
    public const PATH = '/user/change-password';

    /**
     * Checks if this controller supports the given route and HTTP method.
     *
     * @param string $path   The requested route path.
     * @param string $method The HTTP method (GET, POST, etc.).
     * @return bool True if path is '/user/change-password' and method is POST.
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }

    /**
     * Main controller method.
     *
     * Validates password change request, verifies current password, checks new
     * password meets complexity requirements, ensures new password is different,
     * and updates the password in the database. Logs any security violations
     * (e.g., wrong current password, weak new password).
     *
     * Password requirements:
     * - Between 12 and 30 characters
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one digit
     * - At least one special character or punctuation
     * - Must differ from current password
     *
     * @return void
     */
    public function control(): void
    {
        SessionGuard::check();
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
        $userId = is_numeric($userIdRaw) ? (int) $userIdRaw : 0;

        $oldPasswordRaw = $_POST['old_password'] ?? '';
        $oldPassword = is_string($oldPasswordRaw) ? $oldPasswordRaw : '';

        $newPasswordRaw = $_POST['new_password'] ?? '';
        $newPassword = is_string($newPasswordRaw) ? $newPasswordRaw : '';

        $confirmPasswordRaw = $_POST['confirm_password'] ?? '';
        $confirmPassword = is_string($confirmPasswordRaw) ? $confirmPasswordRaw : '';

        $logger = new Log(); // Instanciation du logger pour tracer les échecs

        // Validate required fields
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            $logger->create(
                $userId,
                'ECHEC_MODIFICATION_MDP',
                'users',
                $userId,
                "Champs manquants lors de la tentative de modification depuis le profil"
            );
            header('Location: /user/change-password?error=missing_fields');
            exit;
        }

        // Verify passwords match
        if ($newPassword !== $confirmPassword) {
            $logger->create(
                $userId,
                'ECHEC_MODIFICATION_MDP',
                'users',
                $userId,
                "La confirmation du nouveau mot de passe ne correspond pas"
            );
            header('Location: /user/change-password?error=passwords_dont_match');
            exit;
        }

        // Validate password length
        if (strlen($newPassword) < 12 || strlen($newPassword) > 30) {
            $logger->create(
                $userId,
                'ECHEC_MODIFICATION_MDP',
                'users',
                $userId,
                "Nouveau mot de passe non conforme"
            );
            header('Location: /user/change-password?error=password_length');
            exit;
        }

        // Validate password contains uppercase letter
        if (!preg_match('/[A-Z]/', $newPassword)) {
            $logger->create(
                $userId,
                'ECHEC_MODIFICATION_MDP',
                'users',
                $userId,
                "Nouveau mot de passe sans majuscule"
            );
            header('Location: /user/change-password?error=password_no_uppercase');
            exit;
        }

        // Validate password contains lowercase letter
        if (!preg_match('/[a-z]/', $newPassword)) {
            $logger->create(
                $userId,
                'ECHEC_MODIFICATION_MDP',
                'users',
                $userId,
                "Nouveau mot de passe sans minuscule"
            );
            header('Location: /user/change-password?error=password_no_lowercase');
            exit;
        }

        // Validate password contains digit
        if (!preg_match('/[0-9]/', $newPassword)) {
            $logger->create(
                $userId,
                'ECHEC_MODIFICATION_MDP',
                'users',
                $userId,
                "Nouveau mot de passe sans chiffre"
            );
            header('Location: /user/change-password?error=password_no_digit');
            exit;
        }

        // Validate password contains special character
        if (!preg_match('/[!@#$%^&*()_+€£µ§?\\/\\[\\]|{}]/', $newPassword)) {
            $logger->create(
                $userId,
                'ECHEC_MODIFICATION_MDP',
                'users',
                $userId,
                "Nouveau mot de passe sans caractère spécial"
            );
            header('Location: /user/change-password?error=password_no_special');
            exit;
        }

        try {
            $conn = Database::getConnection();

            // Retrieve current password hash, last change timestamp, previous password hash, and email
            $stmt = $conn->prepare("SELECT mdp, last_password_change, previous_mdp, mail FROM users WHERE id = ?");
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

            // Verify user exists in DB
            if ($user === null) {
                $logger->create(
                    $userId,
                    'ECHEC_MODIFICATION_MDP',
                    'users',
                    $userId,
                    "Utilisateur introuvable en base de données"
                );
                header('Location: /user/change-password?error=wrong_password');
                exit;
            }

            // Extract email for notification
            $userEmail = isset($user['mail']) && is_string($user['mail']) ? $user['mail'] : '';

            // Check rate limit (24 hours)
            if (!empty($user['last_password_change'])) {
                $lastChangeRaw = $user['last_password_change'];
                $lastChangeStr = is_string($lastChangeRaw) ? $lastChangeRaw : '';

                if (!empty($lastChangeStr)) {
                    $lastChange = new \DateTime($lastChangeStr);
                    $now = new \DateTime();
                    $diff = $now->diff($lastChange);

                    $hours = $diff->h + ($diff->days * 24);
                    if ($hours < 24) {
                        // Audit: Log rate limit violation
                        $logger->create(
                            $userId,
                            'ECHEC_MODIFICATION_MDP',
                            'users',
                            $userId,
                            "Tentative de modification refusée : délai de 24h non respecté"
                        );
                        header('Location: /user/change-password?error=wait_before_retry');
                        exit;
                    }
                }
            }

            // Verify current password is correct
            $currentPasswordHash = isset($user['mdp']) && is_string($user['mdp']) ? $user['mdp'] : '';
            if ($currentPasswordHash === '' || !password_verify($oldPassword, $currentPasswordHash)) {
                // Audit: Log wrong current password attempt
                $logger->create($userId, 'ECHEC_MODIFICATION_MDP', 'users', $userId, "Ancien mot de passe incorrect");
                header('Location: /user/change-password?error=wrong_password');
                exit;
            }

            // Ensure new password is different from current password
            if (password_verify($newPassword, $currentPasswordHash)) {
                // Audit: Log attempt to reuse the exact same password
                $logger->create(
                    $userId,
                    'ECHEC_MODIFICATION_MDP',
                    'users',
                    $userId,
                    "Le nouveau mot de passe est identique à l'ancien"
                );
                header('Location: /user/change-password?error=same_password');
                exit;
            }

            // Ensure new password is different from previous password
            $previousPasswordHash = isset($user['previous_mdp']) && is_string($user['previous_mdp'])
                ? $user['previous_mdp'] : '';
            if ($previousPasswordHash !== '' && password_verify($newPassword, $previousPasswordHash)) {
                $logger->create(
                    $userId,
                    'ECHEC_MDP_PRECEDENT',
                    'users',
                    $userId,
                    "Ce mot de passe a déjà été utilisé. Veuillez utiliser un mot de passe différent."
                );
                header('Location: /user/change-password?error=previous_password');
                exit;
            }

            // Hash and update the new password (and timestamp)
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Indique au Trigger SQL qu'il s'agit d'une modification classique via le profil
            $conn->query("SET @pwd_action_type = 'CHANGE'");

            $updateStmt = $conn->prepare(
                "UPDATE users SET previous_mdp = ?, mdp = ?, last_password_change = NOW() WHERE id = ?"
            );
            if (!$updateStmt) {
                throw new DataBaseException("Erreur de préparation SQL update");
            }

            $updateStmt->bind_param("ssi", $currentPasswordHash, $hashedPassword, $userId);
            $updateStmt->execute();
            $updateStmt->close();

            // Send password change notification email
            if (!empty($userEmail)) {
                try {
                    $emailService = new EmailService();
                    $emailService->sendPasswordChangedNotificationEmail($userEmail);
                } catch (\Exception $e) {
                    // Log email sending error but don't fail the password change
                    error_log("Erreur lors de l'envoi de l'email de notification changement MDP: " . $e->getMessage());
                }
            }

            // Redirect with success message (Success log handled by SQL trigger)
            header('Location: /user/profile?success=password_updated');
            exit;
        } catch (\Exception $e) {
            // Log system error
            $logger->create(
                $userId,
                'ERREUR_SYSTEME',
                'users',
                $userId,
                "Erreur système lors du changement de MDP : " . $e->getMessage()
            );
            error_log("Erreur changement mot de passe: " . $e->getMessage());
            header('Location: /user/change-password?error=database_error');
            exit;
        }
    }
}
