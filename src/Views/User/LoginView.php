<?php

namespace Views\User;

use Views\Base\BaseView;
use Views\Base\ErrorsView;

/**
 * Login View
 *
 * Renders the user login form with error and success message display.
 * Handles authentication failures and displays validation errors.
 *
 * Features:
 * - Username and password input fields
 * - Error message display for failed login attempts
 * - Success message display (e.g., after password reset)
 * - Integration with ErrorsView for consistent error formatting
 *
 * @package Views\User
 */
class LoginView extends BaseView
{
    /**
     * Template data key for username field
     */
    public const USERNAME_KEY = 'USERNAME_KEY';

    /**
     * Template data key for password field
     */
    public const PASSWORD_KEY = 'PASSWORD_KEY';

    /**
     * Template data key for errors HTML content
     */
    public const ERRORS_KEY = 'ERRORS_KEY';

    /**
     * Template data key for success message HTML
     */
    public const SUCCESS_MESSAGE_KEY = 'SUCCESS_MESSAGE_KEY';

    /**
     * Path to the login template file
     */
    private const TEMPLATE_PATH = __DIR__ . '/login.php';

    /**
     * Constructor
     *
     * @param array $errors Array of Throwable exceptions representing login errors
     * @param string $successMessage Success message to display (e.g., "Password reset successful")
     */
    public function __construct(
        private array $errors = [],
        private string $successMessage = ''
    ) {
    }

    /**
     * Returns the path to the login template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return self::TEMPLATE_PATH;
    }

    /**
     * Renders the login form body with errors and success messages
     *
     * Generates HTML for:
     * - Success messages in styled alert boxes
     * - Error messages using ErrorsView component
     * - Login form with username and password fields
     *
     * @return string Rendered HTML body content
     */
    public function renderBody(): string
    {
        ob_start();
        $SUCCESS_MESSAGE_KEY = $this->successMessage ? '<div style="color: green; margin: 10px 0; padding: 10px; 
        background: #d4edda; border: 1px solid #c3e6cb; border-radius:  4px;">
        ' . $this->successMessage . '</div>' :  '';
        $ERRORS_KEY = (new ErrorsView($this->errors))->renderBody();
        $uname = '';

        include $this->templatePath();
        return ob_get_clean();
    }
}
