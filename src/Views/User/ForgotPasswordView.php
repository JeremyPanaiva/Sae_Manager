<?php

namespace Views\User;

use Views\Base\BaseView;

/**
 * Forgot Password View
 *
 * Renders the forgot password form where users can request a password reset link.
 * Displays success or error messages based on the request status.
 *
 * Features:
 * - Email input form for password reset requests
 * - Success message when reset email is sent
 * - Error messages for validation failures and system errors
 * - Confirmation message after successful password reset
 *
 * Messages are passed via URL query parameters and rendered as styled alerts.
 *
 * @package Views\User
 */
class ForgotPasswordView extends BaseView
{
    /**
     * Template data key for email address
     */
    public const EMAIL_KEY = 'EMAIL_KEY';
    /**
     * Template data key for success message HTML
     */
    public const SUCCESS_MESSAGE_KEY = 'SUCCESS_MESSAGE';
    /**
     * Template data key for error message HTML
     */
    public const ERROR_MESSAGE_KEY = 'ERROR_MESSAGE';

    /**
     * Path to the forgot password template file
     */
    private const TEMPLATE_PATH = __DIR__ . '/forgot-password.php';

    /**
     * Returns the path to the forgot password template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return self::TEMPLATE_PATH;
    }

    /**
     * Renders the complete view with header, body, and footer
     *
     * Processes URL parameters to generate appropriate messages before rendering.
     *
     * @return string Complete HTML output
     */
    public function render(): string
    {
        $this->handleMessages();
        return parent::render();
    }

    /**
     * Renders the forgot password form body with messages
     *
     * Extracts success and error messages from data and includes the template.
     *
     * @return string Rendered HTML body content
     */
    public function renderBody(): string
    {
        ob_start();
        $SUCCESS_MESSAGE = $this->data['SUCCESS_MESSAGE'] ?? '';
        $ERROR_MESSAGE = $this->data['ERROR_MESSAGE'] ?? '';
        include $this->templatePath();
        $output = ob_get_clean();

        return $output !== false ? $output : '';
    }

    /**
     * Processes URL query parameters and generates appropriate messages
     *
     * Handles the following states:
     *
     * Success states:
     * - email_sent: Password reset email successfully sent
     * - password_reset:  Password successfully reset
     *
     * Error states:
     * - email_required: Email address not provided
     * - invalid_token: Reset token is invalid or expired
     * - database_error: Database operation failed
     * - general_error: Generic error occurred
     *
     * Generated messages are stored in the data array for template rendering.
     *
     * @return void
     */
    private function handleMessages(): void
    {
        $successMessage = '';
        $errorMessage = '';

        if (isset($_GET['success'])) {
            switch ($_GET['success']) {
                case 'email_sent':
                    $successMessage = '<div style="color:  green; margin: 10px 0; padding: 10px; background: #d4edda; 
                    border: 1px solid #c3e6cb; border-radius: 4px;">
                    Un email de réinitialisation a été envoyé à votre adresse email.</div>';
                    break;
                case 'password_reset':
                    $successMessage = '<div style="color:  green; margin: 10px 0; padding: 10px; background: #d4edda; 
                    border: 1px solid #c3e6cb; border-radius: 4px;">
                    Votre mot de passe a été réinitialisé avec succès.  Vous pouvez maintenant vous connecter.</div>';
                    break;
            }
        }

        if (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'email_required':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background:  #f8d7da; 
                    border: 1px solid #f5c6cb; border-radius: 4px;">
                    Veuillez saisir votre adresse email.</div>';
                    break;
                case 'invalid_token':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background:  #f8d7da; 
                    border: 1px solid #f5c6cb; border-radius: 4px;">
                    Le lien de réinitialisation est invalide ou a expiré.</div>';
                    break;
                case 'database_error':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; 
                    border: 1px solid #f5c6cb; border-radius:  4px;">
                    Une erreur est survenue. Veuillez réessayer plus tard. </div>';
                    break;
                case 'general_error':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; 
                    border: 1px solid #f5c6cb; border-radius: 4px;">
                    Une erreur est survenue. Veuillez réessayer plus tard.</div>';
                    break;
            }
        }

        $this->setData([
            'SUCCESS_MESSAGE' => $successMessage,
            'ERROR_MESSAGE' => $errorMessage
        ]);
    }
}