<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Views\User\ChangePasswordView;

class ChangePassword implements ControllerInterface
{
    public const PATH = '/user/change-password';

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }

    public function control(): void
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();

        if (!isset($_SESSION['user']['id'])) {
            header('Location: /login');
            exit;
        }

        $view = new ChangePasswordView();
        echo $view->render();
    }
}
