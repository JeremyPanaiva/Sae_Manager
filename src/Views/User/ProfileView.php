<?php

namespace Views\User;

use Views\Base\BaseView;
use Views\Base\ErrorsView;

/**
 * Profile View
 *
 * Renders the user profile page displaying user information and account details.
 * Allows users to view their personal information and account creation date.
 *
 * Features:
 * - Display user's first name, last name, and email
 * - Show account creation date
 * - Error message display for update failures
 * - Success message display for successful updates
 * - Integration with ErrorsView for consistent error formatting
 *
 * @package Views\User
 */
class ProfileView extends BaseView
{
    /**
     * Path to the profile template file
     */
    private const TEMPLATE_PATH = __DIR__ . '/profile.php';

    /**
     * Template data key for last name
     */
    public const NOM_KEY = 'NOM_KEY';

    /**
     * Template data key for first name
     */
    public const PRENOM_KEY = 'PRENOM_KEY';

    /**
     * Template data key for email address
     */
    public const MAIL_KEY = 'MAIL_KEY';

    /**
     * Template data key for account creation date
     */
    public const DATE_CREATION_KEY = 'DATE_CREATION_KEY';

    /**
     * Template data key for errors HTML content
     */
    public const ERRORS_KEY = 'ERRORS_KEY';

    /**
     * Template data key for success message HTML
     */
    public const SUCCESS_KEY = 'SUCCESS_KEY';

    /**
     * User data (nom, prenom, mail, date_creation)
     *
     * @var array
     */
    private array $userData;

    /**
     * Array of error exceptions
     *
     * @var array
     */
    private array $errors;

    /**
     * Success message text
     *
     * @var string
     */
    private string $success;

    /**
     * Constructor
     *
     * @param array $userData Associative array containing user information (nom, prenom, mail, date_creation)
     * @param array $errors Array of Throwable exceptions representing profile errors
     * @param string $success Success message to display after profile update
     */
    public function __construct(array $userData, array $errors = [], string $success = '')
    {
        $this->userData = $userData;
        $this->errors = $errors;
        $this->success = $success;
    }

    /**
     * Returns the path to the profile template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return self:: TEMPLATE_PATH;
    }

    /**
     * Renders the profile page body with user data and messages
     *
     * Extracts user information from userData array and generates HTML for:
     * - User's personal information (name, email, account date)
     * - Error messages using ErrorsView component
     * - Success messages in styled div
     *
     * @return string Rendered HTML body content
     */
    public function renderBody(): string
    {
        ob_start();

        $nom = $this->userData['nom'] ?? '';
        $prenom = $this->userData['prenom'] ?? '';
        $mail = $this->userData['mail'] ?? '';
        $date_creation = $this->userData['date_creation'] ?? '';

        $ERRORS_KEY = (new ErrorsView($this->errors))->renderBody();
        $SUCCESS_KEY = $this->success ? "<div class='success-message'>{$this->success}</div>" : '';

        include $this->templatePath();
        return ob_get_clean();
    }
}