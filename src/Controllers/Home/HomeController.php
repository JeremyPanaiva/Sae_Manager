<?php
namespace Controllers\Home;

use Controllers\ControllerInterface;
use Models\User\User;
use Views\Home\HomeView;

class HomeController implements ControllerInterface
{
    function control(){
        $view = new HomeView();
       echo $view->render();
    }

    static function support(string $chemin, string $method) : bool{
        return $chemin === "/" && $method === "GET";
    }
}