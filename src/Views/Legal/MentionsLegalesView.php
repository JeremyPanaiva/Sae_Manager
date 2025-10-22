<?php


namespace Views\Legal;

use Views\Base\BaseView;

class MentionsLegalesView extends BaseView
{

    private const TEMPLATE_HTML = __DIR__ . '/mentions-legales.html';

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
