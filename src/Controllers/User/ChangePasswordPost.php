<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\Database;
use Shared\Exceptions\DataBaseException;

class ChangePasswordPost implements ControllerInterface
{
    public const PATH = '/user/change-password';

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }

    public function control(): void
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();

        if (!isset($_SESSION['user']['id'])) {
            header('Location: /login');
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation basique
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            header('Location: /user/change-password?error=missing_fields');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            header('Location: /user/change-password?error=passwords_dont_match');
            exit;
        }

        if (strlen($newPassword) < 8) {
            header('Location: /user/change-password?error=password_too_short');
            exit;
        }

        try {
            $conn = Database::getConnection();

            // 1. Récupérer le mot de passe actuel
            $stmt = $conn->prepare("SELECT mdp FROM users WHERE id = ?");
            if (!$stmt)
                throw new DataBaseException("Erreur de préparation SQL");

            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($oldPassword, $user['mdp'])) {
                header('Location: /user/change-password?error=wrong_password');
                exit;
            }

            // 2. Vérifier que le nouveau mot de passe est différent
            if (password_verify($newPassword, $user['mdp'])) {
                header('Location: /user/change-password?error=same_password');
                exit;
            }

            // 3. Mettre à jour le mot de passe
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET mdp = ? WHERE id = ?");
            if (!$updateStmt)
                throw new DataBaseException("Erreur de préparation SQL update");

            $updateStmt->bind_param("si", $hashedPassword, $userId);
            $updateStmt->execute();
            $updateStmt->close();

            header('Location: /user/change-password?success=password_updated');
            exit;

        } catch (\Exception $e) {
            error_log("Erreur changement mot de passe: " . $e->getMessage());
            header('Location: /user/change-password?error=database_error');
            exit;
        }
    }
}
