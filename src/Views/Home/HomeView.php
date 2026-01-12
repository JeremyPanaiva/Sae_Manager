<?php

namespace Views\Home;

use Controllers\User\Login;
use Models\User\User;
use Views\AbstractView;
use Views\Base\BaseView;

/**
 * Home View
 *
 * Renders the application home page.
 * Displays different content based on user authentication state.
 *
 * The home page serves as the landing page for the application and adapts
 * its content depending on whether a user is logged in or not.
 *
 * @package Views\Home
 */
class HomeView extends BaseView
{
    /**
     * Template data key for username display
     */
    public const USERNAME_KEY = 'USERNAME_KEY';

    /**
     * Template data key for link URL
     */
    public const LINK_KEY = 'LINK_KEY';

    /**
     * Path to the home template file
     */
    private const TEMPLATE_HTML = __DIR__ . '/home.php';

    /**
     * Constructor
     *
     * @param User|null $user The authenticated user or null if not logged in
     */
    public function __construct(?User $user = null)
    {
        parent:: __construct();
        $this->setUser($user);
    }

    /**
     * Returns the path to the home template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return self::TEMPLATE_HTML;
    }
}
