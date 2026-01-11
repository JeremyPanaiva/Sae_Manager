<?php

namespace Views\User;

use Views\Base\BaseView;

/**
 * Reset Password View
 *
 * Renders the password reset form accessed via email reset link.
 * Allows users to set a new password after requesting a password reset.
 *
 * Features:
 * - New password and confirmation password fields
 * - Token validation (passed via URL parameter)
 * - Comprehensive password validation error messages
 * - Password complexity requirement feedback
 *
 * Messages are passed via URL query parameters and rendered as styled alerts.
 *
 * @package Views\User
 */
class ResetPasswordView extends BaseView
{
    /**
     * Path to the reset password template file
     */
    private const TEMPLATE_FILE = __DIR__ . '/reset-password.php';

    /**
     * Returns the path to the reset password template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return self::TEMPLATE_FILE;
    }

    /**
     * Renders the password reset form body
     *
     * Extracts data variables and includes the template file using output buffering.
     *
     * @return string Rendered HTML body content
     */
    public function renderBody(): string
    {
        ob_start();
        extract($this->data);

        include $this->templatePath();

        $output = ob_get_clean();

        return $output !== false ? $output : '';
    }

    /**
     * Renders the complete view with header, body, and footer
     *
     * Processes URL parameters to generate appropriate error messages before rendering.
     *
     * @return string Complete HTML output
     */
    public function render(): string
    {
        $this->handleMessages();
        return parent::render();
    }

    /**
     * Processes URL query parameters and generates appropriate error messages
     *
     * Handles the following error states:
     * - missing_fields: Required fields not provided
     * - passwords_dont_match: Password and confirmation don't match
     * - password_too_short: Password must be at least 8 characters
     * - password_no_uppercase: Password must contain uppercase letter
     * - password_no_lowercase: Password must contain lowercase letter
     * - password_no_digit: Password must contain digit
     * - invalid_token:  Reset token is invalid or expired
     * - database_error: Database operation failed
     * - general_error: Generic error occurred
     * - same_password: New password same as current password
     *
     * Generated error messages are stored in the data array for template rendering.
     *
     * @return void
     */
    private function handleMessages(): void
    {
        $errorMessage = '';

        if (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'missing_fields':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background:  #f8d7da; 
border: 1px solid #f5c6cb; border-radius:  4px;">
                    Tous les champs sont obligatoires.</div>';
                    break;
                case 'passwords_dont_match':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; 
border: 1px solid #f5c6cb; border-radius:  4px;">
                    Les mots de passe ne correspondent pas.</div>';
                    break;
                case 'password_too_short':
                    $errorMessage = '<div style="color:  red; margin: 10px 0; padding: 10px; background: #f8d7da; 
                    border: 1px solid #f5c6cb; border-radius: 4px;">
                    Le mot de passe doit contenir au moins 8 caractères.</div>';
                    break;
                case 'password_no_uppercase':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; 
                    border:  1px solid #f5c6cb; border-radius: 4px;">
                    Le mot de passe doit contenir au moins une lettre majuscule. </div>';
                    break;
                case 'password_no_lowercase':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; 
                    border: 1px solid #f5c6cb; border-radius: 4px;"> 
                    Le mot de passe doit contenir au moins une lettre minuscule.</div>';
                    break;
                case 'password_no_digit':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background:  #f8d7da; 
                    border: 1px solid #f5c6cb; border-radius: 4px;">
                    Le mot de passe doit contenir au moins un chiffre.</div>';
                    break;
                case 'invalid_token':
                    $errorMessage = '<div style="color:  red; margin: 10px 0; padding: 10px; background: #f8d7da; 
                    border: 1px solid #f5c6cb; border-radius: 4px;">
                    Le lien de réinitialisation est invalide ou a expiré.</div>';
                    break;
                case 'database_error':
                    $errorMessage = '<div style="color: red; margin:  10px 0; padding:  10px; background: #f8d7da; 
                    border: 1px solid #f5c6cb; border-radius: 4px;">
                    Une erreur est survenue.  Veuillez réessayer plus tard.</div>';
                    break;
                case 'general_error':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; 
                    border: 1px solid #f5c6cb; border-radius: 4px;">
                    Une erreur est survenue. Veuillez réessayer plus tard.</div>';
                    break;
                case 'same_password':
                    $errorMessage = '<div style="color: red; margin: 10px 0; padding: 10px; background: #f8d7da; 
                    border: 1px solid #f5c6cb; border-radius:  4px;">
                    Le nouveau mot de passe doit être différent de l\'actuel. </div>';
                    break;
            }
        }

        $this->setData([
            'ERROR_MESSAGE' => $errorMessage
        ]);
    }
}