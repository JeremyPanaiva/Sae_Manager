<?php

namespace Views\Legal;

use Views\Base\BaseView;

class ContactView extends BaseView
{
    private const TEMPLATE_HTML = __DIR__ . '/contact.html';

    public function __construct()
    {
        parent::__construct();
    }

    public function templatePath(): string
    {
        return self::TEMPLATE_HTML;
    }

    public function templateKeys(): array
    {
        // Le bloc message est injecté dynamiquement dans render()
        return [
            'MESSAGE_BLOCK' => 'MESSAGE_BLOCK',
        ];
    }

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
                    $messageHtml = "<div class='legal-notice error'>L'envoi de l'email a échoué. Réessayez plus tard.</div>";
                    break;
                default:
                    $messageHtml = "<div class='legal-notice error'>Une erreur est survenue.</div>";
                    break;
            }
        }

        $this->setData(['MESSAGE_BLOCK' => $messageHtml]);
        return parent::render();
    }
}