<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Models\User\PasswordResetToken;
use Models\User\EmailService;
use Shared\Exceptions\DataBaseException;
use Shared\Exceptions\EmailNotFoundException;

class ForgotPasswordPost implements ControllerInterface
{
    public const PATH = "/user/forgot-password";

    public function control()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=forgot-password');
            exit;
        }

        $email = $_POST['email'] ?? '';

        if (empty($email)) {
            header('Location: ?page=forgot-password&error=email_required');
            exit;
        }

        try {
            $userModel = new User();
            $user = $userModel->findByEmail($email);

            if (!$user) {
                header('Location: ?page=forgot-password&success=email_sent');
                exit;
            }

            // Générer et sauvegarder le token
            $tokenModel = new PasswordResetToken();
            $token = $tokenModel->createToken($email);

            try {
                $emailService = new EmailService();
                $emailService->sendPasswordResetEmail($email, $token);
            } catch (\Exception $e) {
                error_log("Erreur SMTP lors de l'envoi de l'email de réinitialisation: " . $e->getMessage());
                if (method_exists($e, 'getTraceAsString')) {
                    error_log($e->getTraceAsString());
                }
            }

            header('Location: ?page=forgot-password&success=email_sent');
            exit;

        } catch (DataBaseException $e) {
            error_log("Erreur base de données dans ForgotPasswordPost: " . $e->getMessage());
            header('Location: ?page=forgot-password&error=database_error');
            exit;
        } catch (\Exception $e) {
            error_log("Erreur générale dans ForgotPasswordPost: " . $e->getMessage());
            header('Location: ?page=forgot-password&error=general_error');
            exit;
        }
    }

    static function support(string $chemin, string $method): bool
    {
        return ($chemin === self::PATH || 
                (isset($_GET['page']) && $_GET['page'] === 'forgot-password'))
            && $method === "POST";
    }
}
