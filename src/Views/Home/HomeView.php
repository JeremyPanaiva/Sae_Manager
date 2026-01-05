<?php

namespace Views\Home;
use Controllers\User\Login;
use Models\User\User;
use Views\AbstractView;
use Views\Base\BaseView;

class HomeView extends BaseView
{

    public const USERNAME_KEY = 'USERNAME_KEY';
    public const LINK_KEY = 'LINK_KEY';

    private const TEMPLATE_HTML = __DIR__ . '/home.php';

    public function __construct(?User $user = null)
    {
        parent::__construct();
        $this->setUser($user);

    }

    public function templatePath(): string
    {
        return self::TEMPLATE_HTML;
    }


}