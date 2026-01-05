<?php

namespace Views\Legal;

use Views\Base\BaseView;

class SitemapView extends BaseView
{
    private const TEMPLATE_HTML = __DIR__ . '/sitemap.php';

    public function __construct()
    {
        parent::__construct();
    }

    public function templatePath(): string
    {
        return self::TEMPLATE_HTML;
    }

}
