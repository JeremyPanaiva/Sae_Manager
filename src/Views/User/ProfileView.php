<?php

namespace Views\User;

use Views\Base\BaseView;
use Views\Base\ErrorsView;

/**
 * Profile View
 *
 * @package Views\User
 */
class ProfileView extends BaseView
{
    private const TEMPLATE_PATH = __DIR__ . '/profile.php';

    public const NOM_KEY = 'NOM_KEY';
    public const PRENOM_KEY = 'PRENOM_KEY';
    public const MAIL_KEY = 'MAIL_KEY';
    public const DATE_CREATION_KEY = 'DATE_CREATION_KEY';
    public const ERRORS_KEY = 'ERRORS_KEY';
    public const SUCCESS_KEY = 'SUCCESS_KEY';

    /**
     * User data
     *
     * @var array<string, mixed>
     */
    private array $userData;

    /**
     * Array of error exceptions
     *
     * @var array<int, \Throwable>
     */
    private array $errors;

    /**
     * Success message
     *
     * @var string
     */
    private string $success;

    /**
     * Constructor
     *
     * @param array<string, mixed> $userData
     * @param array<int, \Throwable> $errors
     * @param string $success
     */
    public function __construct(array $userData, array $errors = [], string $success = '')
    {
        parent::__construct();
        $this->userData = $userData;
        $this->errors = $errors;
        $this->success = $success;
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
     * Renders profile body
     *
     * @return string
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
        $output = ob_get_clean();

        return $output !== false ?  $output : '';
    }
}
