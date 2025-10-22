<?php

namespace Views\Base;

use Controllers\Legal\MentionsLegalesController;
use Controllers\Legal\PlanDuSiteController;
use Views\AbstractView;

class FooterView extends AbstractView
{
    public const LEGAL_LINK_KEY = 'LEGAL_LINK_KEY';
    public const PLAN_LINK_KEY = 'PLAN_LINK_KEY';

    function templatePath(): string
    {
        return __DIR__ . '/footer.html';
    }

    function templateKeys(): array
    {
        return [
            self::PLAN_LINK_KEY => PlanDuSiteController::PATH,
            self::LEGAL_LINK_KEY => MentionsLegalesController::PATH,
        ];
    }
}
