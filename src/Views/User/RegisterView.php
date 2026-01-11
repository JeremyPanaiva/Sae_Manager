<?php

namespace Views\User;

use Views\Base\BaseView;
use Views\Base\ErrorsView;

/**
 * Register View
 *
 * @package Views\User
 */
class RegisterView extends BaseView
{
    public const NOM_KEY = 'NOM_KEY';
    public const PRENOM_KEY = 'PRENOM_KEY';
    public const MAIL_KEY = 'MAIL_KEY';
    public const PASSWORD_KEY = 'PASSWORD_KEY';
    public const ERRORS_KEY = 'ERRORS_KEY';

    private const TEMPLATE_PATH = __DIR__ . '/register.php';

    /**
     * Constructor
     *
     * @param array<int, \Throwable> $errors
     */
    public function __construct(
        private array $errors = [],
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
     * Renders register body
     *
     * @return string
     */
    public function renderBody(): string
    {
        ob_start();
        $ERRORS_KEY = (new ErrorsView($this->errors))->renderBody();
        $nom = $this->data['nom'] ?? '';
        $prenom = $this->data['prenom'] ?? '';
        $mail = $this->data['mail'] ?? '';

        include $this->templatePath();
        $output = ob_get_clean();

        return $output !== false ? $output : '';
    }
}
