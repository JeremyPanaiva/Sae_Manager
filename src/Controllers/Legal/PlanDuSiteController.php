<?php

namespace Controllers\Legal;

use Controllers\ControllerInterface;
use Views\Legal\PlanDuSiteView;

class PlanDuSiteController implements ControllerInterface
{
    public const PATH = '/plan-du-site';

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }

    public function control(): void
    {
        $view = new PlanDuSiteView();
        echo $view->render();
    }
}
