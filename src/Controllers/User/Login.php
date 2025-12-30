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
        if (isset($_GET['success']) && $_GET['success'] === 'password_reset') {
            $successMessage = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
        }
        $view = new ConnectionView([], $successMessage);
        echo $view->render();

    }

    static function support(string $chemin, string $method): bool
    {
        return $chemin === self::PATH && $method === "GET";
    }
}