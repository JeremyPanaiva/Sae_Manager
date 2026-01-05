<?php

namespace Controllers\Legal;

use Controllers\ControllerInterface;
use Views\Legal\SitemapView;

class PlanDuSiteController implements ControllerInterface
{
    public const PATH = '/plan-du-site';

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }

    public function control(): void
    {
        $view = new SitemapView();
        echo $view->render();
    }
}
