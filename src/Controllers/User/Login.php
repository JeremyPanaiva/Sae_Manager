<?php
namespace Controllers\User;
use Controllers\ControllerInterface ; 
use Models\User\UserDTO;
use Views\User\ConnectionView;

class Login implements ControllerInterface
{
    public const PATH = "/user/login";
    function control(){
        $view = new ConnectionView();
        echo $view->render();

    }

    static function support(string $chemin, string $method) : bool{
        return $chemin === self::PATH && $method === "GET";
    }
}