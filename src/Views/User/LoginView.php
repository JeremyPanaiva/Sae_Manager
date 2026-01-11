<?php

namespace Views\User;

use Views\Base\BaseView;
use Views\Base\ErrorsView;

/**
 * Login View
 *
 * @package Views\User
 */
class LoginView extends BaseView
{
    public const USERNAME_KEY = 'USERNAME_KEY';
    public const PASSWORD_KEY = 'PASSWORD_KEY';
    public const ERRORS_KEY = 'ERRORS_KEY';
    public const SUCCESS_MESSAGE_KEY = 'SUCCESS_MESSAGE_KEY';

    private const TEMPLATE_PATH = __DIR__ . '/login.php';

    /**
     * Constructor
     *
     * @param array<int, \Throwable> $errors
     * @param string $successMessage
     */
    public function __construct(
        private array $errors = [],
        private string $successMessage = ''
    ) {
    }

    /**
     * Returns template path
     *
     * @return string
     */
    public function templatePath(): string
    {
        return self::TEMPLATE_PATH;
    }

    /**
     * Renders login body
     *
     * @return string
     */
    public function renderBody(): string
    {
        ob_start();
        $SUCCESS_MESSAGE_KEY = $this->successMessage ?  '<div style="color: green; margin:  10px 0; padding: 10px; 
        background:  #d4edda; border:  1px solid #c3e6cb; border-radius: 4px;">
        ' . $this->successMessage . '</div>' : '';
        $ERRORS_KEY = (new ErrorsView($this->errors))->renderBody();
        $uname = '';

        include $this->templatePath();
        $output = ob_get_clean();

        return $output !== false ? $output : '';
    }
}
