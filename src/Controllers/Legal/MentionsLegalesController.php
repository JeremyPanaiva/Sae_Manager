<?php

namespace Controllers\Legal;

use Controllers\ControllerInterface;
use Views\Legal\LegalNoticeView;

class MentionsLegalesController implements ControllerInterface
{
    public const PATH = '/mentions-legales';

    /**
     * Vérifie si le contrôleur prend en charge la route demandée
     */
    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'GET';
    }

    /**
     * Exécute la logique du contrôleur
     */
    public function control(): void
    {
        $view = new LegalNoticeView();
        echo $view->render();
    }
}
