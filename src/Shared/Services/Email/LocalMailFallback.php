<?php

namespace Shared\Services\Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Shared\Exceptions\DataBaseException;

/**
 * Fallback Mailer
 *
 * Single responsibility: send an email using PHP's native mail() function
 * when the primary SMTP transport fails.
 *
 * This class is used exclusively as a fallback by EmailService,
 * keeping error-recovery logic separate from business logic.
 *
 * @package Shared\Services\Email
 */
class LocalMailFallback
{
    /**
     * Sends an email via the native mail() function,
     * copying Subject, Body and AltBody from the SMTP mailer.
     *
     * @param PHPMailer $smtpMailer The SMTP mailer whose content is reused
     * @param string    $recipient  Recipient email address
     * @return bool True if the email was sent successfully
     * @throws DataBaseException If the fallback also fails
     */
    public static function send(PHPMailer $smtpMailer, string $recipient): bool
    {
        try {
            $mail = new PHPMailer(true);
            $mail->isMail();
            $mail->CharSet = 'UTF-8';

            $from     = SmtpConfiguration::getFromEmail($smtpMailer);
            $fromName = SmtpConfiguration::getFromName($smtpMailer);

            if (!empty($from)) {
                $mail->setFrom($from, $fromName);
                $mail->addReplyTo($from, $fromName);
            }

            $mail->addAddress($recipient);
            $mail->isHTML($smtpMailer->ContentType === 'text/html');
            $mail->Subject = $smtpMailer->Subject;
            $mail->Body    = $smtpMailer->Body;
            $mail->AltBody = $smtpMailer->AltBody;

            $mail->send();
            error_log('LocalMailFallback: email sent via mail() to ' . $recipient);
            return true;
        } catch (Exception $e) {
            error_log('LocalMailFallback: fallback failed — ' . $e->getMessage());
            throw new DataBaseException('Email sending failed (SMTP and fallback): ' . $e->getMessage());
        }
    }

    /**
     * Sends a contact form email via the native mail() function.
     *
     * This is a special case: the email body is built by the caller
     * rather than copied from the SMTP mailer, because the contact email
     * has a custom Reply-To header pointing to the user's address.
     *
     * @param string $from      Sender email address (FROM)
     * @param string $fromName  Sender display name
     * @param string $replyTo   Reply-To address (the user's email)
     * @param string $to        Recipient email address
     * @param string $subject   Email subject (already sanitised)
     * @param string $body      Plain-text email body
     * @return bool True if the email was sent successfully
     * @throws DataBaseException If the fallback fails
     */
    public static function sendContact(
        string $from,
        string $fromName,
        string $replyTo,
        string $to,
        string $subject,
        string $body
    ): bool {
        try {
            $mail = new PHPMailer(true);
            $mail->isMail();
            $mail->CharSet = 'UTF-8';

            if (!empty($from)) {
                $mail->setFrom($from, $fromName);
                $mail->addReplyTo($replyTo ?: $from, $fromName);
            }

            $mail->addAddress($to);
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            error_log('LocalMailFallback: contact email sent via mail()');
            return true;
        } catch (Exception $e) {
            error_log('LocalMailFallback: contact fallback failed — ' . $e->getMessage());
            throw new DataBaseException('Contact email sending failed: ' . $e->getMessage());
        }
    }
}