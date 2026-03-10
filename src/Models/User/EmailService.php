<?php

namespace Models\User;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Models\Database;
use Shared\Exceptions\DataBaseException;
use Shared\Services\Email\SmtpMailerFactory;
use Shared\Services\Email\FallbackMailer;
use Views\Email\EmailView;

/**
 * Handles all application email sending.
 * Delegates SMTP config to SmtpMailerFactory and fallback to FallbackMailer.
 *
 * @package Models\User
 */
class EmailService
{
    private PHPMailer $mailer;

    /** @throws DataBaseException If SMTP configuration fails */
    public function __construct()
    {
        $this->mailer = SmtpMailerFactory::create();
    }


    /** @throws DataBaseException */
    public function sendPasswordResetEmail(string $email, string $token): bool
    {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($email);
        $this->mailer->isHTML(true);
        $this->mailer->Subject = 'Réinitialisation de votre mot de passe - SAE Manager';

        $resetLink = $this->getBaseUrl() . "/user/reset-password?token=" . $token;

        $emailView = new EmailView('password_reset', ['RESET_LINK' => $resetLink]);
        $this->mailer->Body = $emailView->render();
        $this->mailer->AltBody = $this->textPasswordReset($resetLink);

        try {
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (password_reset): ' . $e->getMessage());
            return FallbackMailer::send($this->mailer, $email);
        }
    }

    /** @throws DataBaseException */
    public function sendAccountVerificationEmail(string $email, string $token): bool
    {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($email);
        $this->mailer->isHTML(true);
        $this->mailer->Subject = 'Vérification de votre compte - SAE Manager';

        $verificationLink = $this->getBaseUrl() . "/user/verify-email?token=" . $token;

        $emailView = new EmailView('account_verification', ['VERIFICATION_LINK' => $verificationLink]);
        $this->mailer->Body = $emailView->render();
        $this->mailer->AltBody = $this->textAccountVerification($verificationLink);

        try {
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (account_verification): ' . $e->getMessage());
            return FallbackMailer::send($this->mailer, $email);
        }
    }

    /** @throws DataBaseException */
    public function sendSaeCreationNotification(
        string $responsableEmail,
        string $responsableNom,
        string $clientNom,
        string $saeTitle,
        string $saeDescription
    ): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($responsableEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Nouvelle proposition de SAE';

            $saeUrl = $this->getBaseUrl() . '/sae';
            $emailView = new EmailView('sae_creation', [
                'RESPONSABLE_NAME' => $responsableNom,
                'CLIENT_NAME' => $clientNom,
                'SAE_TITLE' => $saeTitle,
                'SAE_DESCRIPTION' => $saeDescription,
                'SAE_URL' => $saeUrl,
            ]);
            $this->mailer->Body = $emailView->render();
            $this->mailer->AltBody = $this->textSaeCreation($responsableNom, $clientNom, $saeTitle, $saeDescription);

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (sae_creation): ' . $e->getMessage());
            return FallbackMailer::send($this->mailer, $responsableEmail);
        }
    }

    /** @throws DataBaseException */
    public function sendStudentAssignmentNotification(
        string $studentEmail,
        string $studentNom,
        string $saeTitre,
        string $saeDescription,
        string $responsableNom,
        string $clientNom,
        string $dateRendu = ''
    ): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($studentEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Nouvelle affectation SAE - ' . $saeTitre;

            $dateRenduFormatted = 'Non définie';
            if (!empty($dateRendu)) {
                $ts = strtotime($dateRendu);
                if ($ts !== false) {
                    $dateRenduFormatted = date('d/m/Y', $ts);
                }
            }

            $saeUrl = $this->getBaseUrl() . '/sae';
            $emailView = new EmailView('student_assignment', [
                'STUDENT_NAME' => $studentNom,
                'SAE_TITLE' => $saeTitre,
                'SAE_DESCRIPTION' => $saeDescription,
                'RESPONSABLE_NAME' => $responsableNom,
                'CLIENT_NAME' => $clientNom,
                'DATE_RENDU' => $dateRenduFormatted,
                'SAE_URL' => $saeUrl,
            ]);
            $this->mailer->Body = $emailView->render();
            $this->mailer->AltBody = $this->textStudentAssignment(
                $studentNom, $saeTitre, $saeDescription, $responsableNom, $clientNom, $dateRenduFormatted
            );

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (student_assignment): ' . $e->getMessage());
            return FallbackMailer::send($this->mailer, $studentEmail);
        }
    }

    /** @throws DataBaseException */
    public function sendClientStudentAssignmentNotification(
        string $clientEmail,
        string $clientNom,
        string $saeTitre,
        string $studentNom,
        string $responsableNom
    ): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($clientEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Affectation d\'un étudiant à votre SAE - ' . $saeTitre;

            $saeUrl = $this->getBaseUrl() . '/sae';
            $emailView = new EmailView('client_assignment', [
                'CLIENT_NAME' => $clientNom,
                'SAE_TITLE' => $saeTitre,
                'STUDENT_NAME' => $studentNom,
                'RESPONSABLE_NAME' => $responsableNom,
                'SAE_URL' => $saeUrl,
            ]);
            $this->mailer->Body = $emailView->render();
            $this->mailer->AltBody = $this->textClientAssignment($clientNom, $saeTitre, $studentNom, $responsableNom);

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (client_assignment): ' . $e->getMessage());
            return FallbackMailer::send($this->mailer, $clientEmail);
        }
    }

    /**
     * Sends a contact form email. Reply-To is set to the user's address.
     * Subject is sanitised to prevent header injection.
     *
     * @throws DataBaseException
     */
    public function sendContactEmail(string $fromUserEmail, string $subject, string $message): bool
    {
        $to = 'sae-manager@alwaysdata.net';
        $safeSubject = str_replace(["\r", "\n"], ' ', $subject);
        $body = "Message envoyé depuis le formulaire de contact SAE Manager\n\n"
            . "De    : {$fromUserEmail}\n"
            . "Sujet : {$safeSubject}\n"
            . "---------\n\n"
            . "{$message}\n";

        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($to);
            if (filter_var($fromUserEmail, FILTER_VALIDATE_EMAIL)) {
                $this->mailer->addReplyTo($fromUserEmail);
            }
            $this->mailer->isHTML(false);
            $this->mailer->Subject = '[Contact] ' . $safeSubject;
            $this->mailer->Body = $body;

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (contact): ' . $e->getMessage());
            return FallbackMailer::sendContact(
                SmtpMailerFactory::getFromEmail($this->mailer),
                SmtpMailerFactory::getFromName($this->mailer),
                $fromUserEmail,
                $to,
                '[Contact] ' . $safeSubject,
                $body
            );
        }
    }

    /** @throws DataBaseException */
    public function sendDeadlineReminderEmail(
        string $studentEmail,
        string $studentNom,
        string $saeTitre,
        string $dateRendu,
        string $responsableNom
    ): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($studentEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Rappel : Date de rendu SAE dans 3 jours - ' . $saeTitre;

            $dateRenduFormatted = 'Non définie';
            $heureRendu = '';
            if (!empty($dateRendu)) {
                $ts = strtotime($dateRendu);
                if ($ts !== false) {
                    $dateRenduFormatted = date('d/m/Y', $ts);
                    $heureRendu = date('H:i', $ts);
                }
            }

            $saeUrl = $this->getBaseUrl() . '/sae';
            $emailView = new EmailView('deadline_reminder', [
                'STUDENT_NAME' => $studentNom,
                'SAE_TITLE' => $saeTitre,
                'DATE_RENDU' => $dateRenduFormatted,
                'HEURE_RENDU' => $heureRendu,
                'RESPONSABLE_NAME' => $responsableNom,
                'SAE_URL' => $saeUrl,
            ]);
            $this->mailer->Body = $emailView->render();
            $this->mailer->AltBody = $this->textDeadlineReminder(
                $studentNom, $saeTitre, $dateRenduFormatted, $heureRendu, $responsableNom
            );

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (deadline_reminder): ' . $e->getMessage());
            return FallbackMailer::send($this->mailer, $studentEmail);
        }
    }

    /** @throws DataBaseException */
    public function sendUrgentDeadlineReminderEmail(
        string $studentEmail,
        string $studentNom,
        string $saeTitre,
        string $dateRendu,
        string $responsableNom
    ): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($studentEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = '🚨 URGENT : Rendu SAE DANS 1 JOUR - ' . $saeTitre;

            $dateRenduFormatted = 'Non définie';
            $heureRendu = '';
            if (!empty($dateRendu)) {
                $ts = strtotime($dateRendu);
                if ($ts !== false) {
                    $dateRenduFormatted = date('d/m/Y', $ts);
                    $heureRendu = date('H:i', $ts);
                }
            }

            $saeUrl = $this->getBaseUrl() . '/sae';
            $emailView = new EmailView('urgent_deadline_reminder', [
                'STUDENT_NAME' => $studentNom,
                'SAE_TITLE' => $saeTitre,
                'DATE_RENDU' => $dateRenduFormatted,
                'HEURE_RENDU' => $heureRendu,
                'RESPONSABLE_NAME' => $responsableNom,
                'SAE_URL' => $saeUrl,
            ]);
            $this->mailer->Body = $emailView->render();
            $this->mailer->AltBody = $this->textUrgentDeadlineReminder(
                $studentNom, $saeTitre, $dateRenduFormatted, $responsableNom, $heureRendu
            );

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (urgent_deadline_reminder): ' . $e->getMessage());
            return FallbackMailer::send($this->mailer, $studentEmail);
        }
    }

    /** @throws DataBaseException */
    public function sendPasswordChangedNotificationEmail(string $email): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($email);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Modification de votre mot de passe - SAE Manager';

            $loginLink = $this->getBaseUrl() . '/login';
            $emailView = new EmailView('password_changed', ['LOGIN_LINK' => $loginLink]);
            $this->mailer->Body = $emailView->render();
            $this->mailer->AltBody = $this->textPasswordChanged($loginLink);

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (password_changed): ' . $e->getMessage());
            return FallbackMailer::send($this->mailer, $email);
        }
    }

    /** @throws DataBaseException */
    public function sendMessageToStudent(
        string $studentEmail,
        string $studentName,
        string $subject,
        string $message,
        string $responsableName
    ): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($studentEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;

            $emailView = new EmailView('responsable_message', [
                'STUDENT_NAME' => $studentName,
                'MESSAGE' => nl2br(htmlspecialchars($message)),
                'RESPONSABLE_NAME' => $responsableName,
                'SUBJECT' => $subject,
            ]);
            $this->mailer->Body = $emailView->render();
            $this->mailer->AltBody = "Bonjour {$studentName},\n\n{$message}\n\nCordialement,\n{$responsableName}\nResponsable SAE Manager";

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (responsable_message): ' . $e->getMessage());
            return FallbackMailer::send($this->mailer, $studentEmail);
        }
    }

}