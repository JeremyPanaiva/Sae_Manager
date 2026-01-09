<?php

namespace Views\User;

use Views\Base\BaseView;
use Views\Base\ErrorsView;
use Views\Base\ErrorView;

/**
 * Register View
 *
 * Renders the user registration form with validation error display.
 * Allows new users to create an account by providing their personal information.
 *
 * Features:
 * - Registration form with name, email, and password fields
 * - Form field repopulation after validation errors
 * - Error message display for validation failures
 * - Integration with ErrorsView for consistent error formatting
 *
 * @package Views\User
 */
class RegisterView extends BaseView
{
    /**
     * Template data key for last name field
     */
    public const NOM_KEY = 'NOM_KEY';

    /**
     * Template data key for first name field
     */
    public const PRENOM_KEY = 'PRENOM_KEY';

    /**
     * Template data key for email field
     */
    public const MAIL_KEY = 'MAIL_KEY';

    /**
     * Template data key for password field
     */
    public const PASSWORD_KEY = 'PASSWORD_KEY';

    /**
     * Template data key for errors HTML content
     */
    public const ERRORS_KEY = 'ERRORS_KEY';

    /**
     * Path to the register template file
     */
    private const TEMPLATE_PATH = __DIR__ . '/register.php';

    /**
     * Constructor
     *
     * @param array $errors Array of Throwable exceptions representing registration validation errors
     */
    public function __construct(
        private array $errors = [],
    ) {
    }

    /**
     * Returns the path to the register template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return self::TEMPLATE_PATH;
    }

    /**
     * Renders the registration form body with errors and preserved field values
     *
     * Generates HTML for:
     * - Error messages using ErrorsView component
     * - Registration form with repopulated fields (except password)
     * - Form fields for nom, prenom, mail, and password
     *
     * Field values are preserved from $this->data to maintain user input
     * after validation failures.
     *
     * @return string Rendered HTML body content
     */
    public function renderBody(): string
    {
        ob_start();
        $ERRORS_KEY = (new ErrorsView($this->errors))->renderBody();
        $nom = $this->data['nom'] ?? '';
        $prenom = $this->data['prenom'] ?? '';
        $mail = $this->data['mail'] ?? '';

        include $this->templatePath();
        return ob_get_clean();
    }
}
