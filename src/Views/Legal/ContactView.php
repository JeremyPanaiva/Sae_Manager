<?php

namespace Views\Legal;

use Views\Base\BaseView;

/**
 * Contact View
 *
 * Renders the contact page with a contact form.
 * Displays success or error messages based on form submission results.
 *
 * Handles different message states:
 * - Success: Message sent confirmation
 * - Error: Validation errors (missing fields, invalid email, mail failure)
 *
 * Messages are passed via URL query parameters and rendered as styled notices.
 *
 * @package Views\Legal
 */
class ContactView extends BaseView
{
    /**
     * Path to the contact template file
     */
    private const TEMPLATE_HTML = __DIR__ . '/contact. php';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns the path to the contact template file
     *
     * @return string Absolute path to the template file
     */
    public function templatePath(): string
    {
        return self::TEMPLATE_HTML;
    }

    /**
     * Renders the contact page with status messages
     *
     * Checks URL query parameters for success/error states and generates
     * appropriate message HTML to display to the user.
     *
     * Possible states:
     * - success=message_sent:  Displays success message
     * - error=missing_fields: All fields are required
     * - error=invalid_email: Email format is invalid
     * - error=mail_failed: Email sending failed
     * - error=*: Generic error message
     *
     * @return string Rendered HTML output with messages
     */
    public function render(): string
    {
        $messageHtml = '';

        if (isset($_GET['success']) && $_GET['success'] === 'message_sent') {
            $messageHtml = "<div class='legal-notice success'>Votre message a bien été envoyé. Merci !</div>";
        } elseif (isset($_GET['error'])) {
            switch ($_GET['error']) {
                case 'missing_fields':
                    $messageHtml = "<div class='legal-notice error'>Veuillez remplir tous les champs.</div>";
                    break;
                case 'invalid_email':
                    $messageHtml = "<div class='legal-notice error'>Adresse email invalide.</div>";
                    break;
                case 'mail_failed':
                    $messageHtml = "<div class='legal-notice error'>
                    L'envoi de l'email a échoué. Réessayez plus tard.</div>";
                    break;
                default:
                    $messageHtml = "<div class='legal-notice error'>Une erreur est survenue. </div>";
                    break;
            }
        }

        $this->setData(['MESSAGE_BLOCK' => $messageHtml]);
        return parent::render();
    }
}
