<?php
namespace Controllers\User;

use Controllers\ControllerInterface;
use Views\Home\HomeView;

class Logout implements ControllerInterface
{
    public const PATH = "/user/logout";

    public function control(): void
    {


        $_SESSION = [];

        session_destroy();


        header("Location: /");
        $view = new HomeView();
        echo $view->render();

    }

    public static function support(string $chemin, string $method): bool
    {
        return $chemin === self::PATH && $method === "GET";
    }
}
