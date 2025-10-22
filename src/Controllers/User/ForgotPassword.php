<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Views\User\ForgotPasswordView;

class ForgotPassword implements ControllerInterface {

    public const PATH = "/user/forgot-password";

    public function control() {
        $view = new ForgotPasswordView();
        echo $view->render();
    }

    static function support(string $chemin, string $method): bool {
        return ($chemin === self::PATH ||
                (isset($_GET['page']) && $_GET['page'] === 'forgot-password'))
            && $method === "GET";
    }
}