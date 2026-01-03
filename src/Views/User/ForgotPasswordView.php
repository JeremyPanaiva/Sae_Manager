<?php

namespace Views\User;
use Views\Base\BaseView;

class ForgotPasswordView extends BaseView
{

    public const EMAIL_KEY = 'EMAIL_KEY';
    public const SUCCESS_MESSAGE_KEY = 'SUCCESS_MESSAGE';
    public const ERROR_MESSAGE_KEY = 'ERROR_MESSAGE';

    private const TEMPLATE_PATH = __DIR__ . '/forgot-password.php';

    public function templatePath(): string
    {
        return self::TEMPLATE_PATH;
    }


    public function render(): string
    {
        $this->handleMessages();
        return parent::render();
    }

    public function renderBody(): string
    {
        ob_start();
        $SUCCESS_MESSAGE = $this->data['SUCCESS_MESSAGE'] ?? '';
        $ERROR_MESSAGE = $this->data['ERROR_MESSAGE'] ?? '';
        include $this->templatePath();
        return ob_get_clean();
    }

    private function handleMessages(): void
    {
        $successMessage = '';
        $errorMessage = '';

        if (isset($_GET['success'])) {
            switch ($_GET['success']) {
                case 'email_sent':
                    $successMessage = '<div style="color: green; margin: 10px 0; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">Un email de réinitialisation a été envoyé à votre adresse email.</div>';
                    break;
                case 'password_reset':
                    $successMessage = '<div style="color: green; margin: 10px 0; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.</div>';
                    break;
            }
        }

        if (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'email_required':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">Veuillez saisir votre adresse email.</div>';
                    break;
                case 'invalid_token':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">Le lien de réinitialisation est invalide ou a expiré.</div>';
                    break;
                case 'database_error':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">Une erreur est survenue. Veuillez réessayer plus tard.</div>';
                    break;
                case 'general_error':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">Une erreur est survenue. Veuillez réessayer plus tard.</div>';
                    break;
            }
        }

        $this->setData([
            'SUCCESS_MESSAGE' => $successMessage,
            'ERROR_MESSAGE' => $errorMessage
        ]);
    }
}