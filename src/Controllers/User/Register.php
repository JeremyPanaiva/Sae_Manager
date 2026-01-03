<?php
namespace Controllers\User;
use Controllers\ControllerInterface;

class Register implements ControllerInterface
{
    function control()
    {
        $view = new \Views\User\RegisterView();
        echo $view->render();
    }

    static function support(string $chemin, string $method): bool
    {
        return $chemin === "/user/register" && $method === "GET";
    }

}