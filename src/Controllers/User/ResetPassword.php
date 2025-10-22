<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\PasswordResetToken;
use Models\User\User;
use Models\Database;
use Shared\Exceptions\DataBaseException;

class ResetPassword implements ControllerInterface
{
    public const PATH = "/user/reset-password";

    public function control()
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            header('Location: ?page=forgot-password&error=invalid_token');
            exit;
        }

        try {
            $tokenModel = new PasswordResetToken();
            $email = $tokenModel->validateToken($token);

            if (!$email) {
                header('Location: ?page=forgot-password&error=invalid_token');
                exit;
            }

            // Afficher le formulaire de réinitialisation
            $view = new \Views\User\ResetPasswordView();
            $view->setData(['token' => $token, 'email' => $email]);
            echo $view->render();

        } catch (DataBaseException $e) {
            error_log("Erreur base de données dans ResetPassword: " . $e->getMessage());
            header('Location: ?page=forgot-password&error=database_error');
            exit;
        }
    }

    static function support(string $chemin, string $method): bool
    {
        return ($chemin === self::PATH || 
                (isset($_GET['page']) && $_GET['page'] === 'reset-password'))
            && $method === "GET";
    }
}
