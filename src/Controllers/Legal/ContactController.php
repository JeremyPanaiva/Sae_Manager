<?php

namespace Controllers\Legal;

use Controllers\ControllerInterface;
use Views\Legal\ContactView;

class ContactController implements ControllerInterface
{
    public const PATH = '/contact';

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }

    public function control(): void
    {
        $view = new ContactView();
        echo $view->render();
    }
}


