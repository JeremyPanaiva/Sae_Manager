<?php
namespace Views\User;

use Views\Base\BaseView;
use Views\Base\ErrorsView;

class ProfileView extends BaseView {

    private const TEMPLATE_HTML = __DIR__ . '/profile.html';

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

    public function templatePath(): string {
        return self::TEMPLATE_HTML;
    }

    public function templateKeys(): array {
        return [
            self::NOM_KEY => $this->userData['nom'] ?? '',
            self::PRENOM_KEY => $this->userData['prenom'] ?? '',
            self::MAIL_KEY => $this->userData['mail'] ?? '',
            self::DATE_CREATION_KEY => $this->userData['date_creation'] ?? '',
            self::ERRORS_KEY => (new ErrorsView($this->errors))->renderBody(),
            self::SUCCESS_KEY => $this->success ? "<div class='success-message'>{$this->success}</div>" : '',
        ];
    }
}
