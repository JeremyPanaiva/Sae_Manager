<?php
namespace Controllers\User;
use Controllers\ControllerInterface;
use Models\User\UserDTO;
use Views\User\ConnectionView;

class Login implements ControllerInterface
{
    public const PATH = "/user/login";
    function control()
    {
        $successMessage = '';
        if (isset($_GET['success'])) {
            if ($_GET['success'] === 'password_reset') {
                $successMessage = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
            } elseif ($_GET['success'] === 'account_verified') {
                $successMessage = "Votre compte a été vérifié avec succès. Vous pouvez maintenant vous connecter.";
            } elseif ($_GET['success'] === 'registered') {
                $successMessage = "Inscription réussie. Veuillez vérifier votre email pour activer votre compte.";
            }
        }

        $errors = [];
        if (isset($_GET['error'])) {
            if ($_GET['error'] === 'invalid_token') {
                $errors[] = new \Shared\Exceptions\ValidationException("Le lien de vérification est invalide ou a expiré.");
            } elseif ($_GET['error'] === 'db_error') {
                $errors[] = new \Shared\Exceptions\DataBaseException("Une erreur est survenue lors de la vérification.");
            }
        }

        $view = new ConnectionView($errors, $successMessage);
        echo $view->render();

    }

    static function support(string $chemin, string $method): bool
    {
        return $chemin === self::PATH && $method === "GET";
    }
}