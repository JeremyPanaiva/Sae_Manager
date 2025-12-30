<?php
namespace Views\User;

use Views\Base\BaseView;
use Views\Base\ErrorsView;

class ProfileView extends BaseView
{

    private const TEMPLATE_PATH = __DIR__ . '/profile.php';

    public const NOM_KEY = 'NOM_KEY';
    public const PRENOM_KEY = 'PRENOM_KEY';
    public const MAIL_KEY = 'MAIL_KEY';
    public const DATE_CREATION_KEY = 'DATE_CREATION_KEY';
    public const ERRORS_KEY = 'ERRORS_KEY';
    public const SUCCESS_KEY = 'SUCCESS_KEY';

    private array $userData;
    private array $errors;
    private string $success;

    public function __construct(array $userData, array $errors = [], string $success = '')
    {
        $this->userData = $userData;
        $this->errors = $errors;
        $this->success = $success;
    }

    public function templatePath(): string
    {
        return self::TEMPLATE_PATH;
    }

    public function templateKeys(): array
    {
        return []; // Pas utilisé
    }

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
