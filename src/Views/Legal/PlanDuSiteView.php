<?php

namespace Views\Legal;

use Views\Base\BaseView;

class PlanDuSiteView extends BaseView
{
    private const TEMPLATE_HTML = __DIR__ . '/plan-du-site.html';

    public function __construct()
    {
        parent::__construct();
    }

    public function templatePath(): string
    {
        return self::TEMPLATE_HTML;
    }

    public function templateKeys(): array
    {
        return [];
    }
}
