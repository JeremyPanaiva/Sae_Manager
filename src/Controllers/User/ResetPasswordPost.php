<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\PasswordResetToken;

use Models\Database;
use Shared\Exceptions\DataBaseException;
use Shared\Exceptions\SamePasswordException;

class ResetPasswordPost implements ControllerInterface
{
    public const PATH = "/user/reset-password";

    public function control()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /user/forgot-password');
            exit;
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($token) || empty($password) || empty($confirmPassword)) {
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=missing_fields');
            exit;
        }

        if ($password !== $confirmPassword) {
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=passwords_dont_match');
            exit;
        }

        if (strlen($password) < 8) {
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=password_too_short');
            exit;
        }

        try {
            $tokenModel = new PasswordResetToken();
            $email = $tokenModel->validateToken($token);

            if (!$email) {
                header('Location: /user/forgot-password?error=invalid_token');
                exit;
            }

            // Vérifier si le nouveau mot de passe est identique à l'ancien
            $conn = Database::getConnection();
            $stmt = $conn->prepare("SELECT mdp FROM users WHERE mail = ?");
            if (!$stmt) {
                throw new DataBaseException("SQL prepare failed in user check.");
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['mdp'])) {
                    throw new SamePasswordException();
                }
            } else {
                // Utilisateur introuvable
                header('Location: /user/forgot-password?error=invalid_token');
                exit;
            }
            $stmt->close();

            // Mettre à jour le mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET mdp = ? WHERE mail = ?");
            if (!$stmt) {
                throw new DataBaseException("SQL prepare failed in reset password.");
            }
            $stmt->bind_param("ss", $hashedPassword, $email);
            $stmt->execute();
            $stmt->close();

            // Supprimer le token utilisé
            $tokenModel->deleteToken($token);

            header('Location: /user/login?success=password_reset');
            exit;

        } catch (SamePasswordException $e) {
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=same_password');
            exit;
        } catch (DataBaseException $e) {
            error_log("Erreur base de données dans ResetPasswordPost: " . $e->getMessage());
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=database_error');
            exit;
        } catch (\Exception $e) {
            error_log("Erreur générale dans ResetPasswordPost: " . $e->getMessage());
            header('Location: /user/reset-password?token=' . urlencode($token) . '&error=general_error');
            exit;
        }
    }

    static function support(string $chemin, string $method): bool
    {
        return ($chemin === self::PATH ||
            (isset($_GET['page']) && $_GET['page'] === 'reset-password'))
            && $method === "POST";
    }
}
