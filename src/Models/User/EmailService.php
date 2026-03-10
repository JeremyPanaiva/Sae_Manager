<?php

namespace Models\User;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Models\Database;
use Shared\Exceptions\DataBaseException;
use Shared\Services\Email\SmtpConfiguration;
use Shared\Services\Email\LocalMailFallback;
use Views\Email\EmailView;

/**
 * Handles all application email sending.
 * Delegates SMTP config to SmtpConfiguration and fallback to LocalMailFallback.
 *
 * @package Models\User
 */
class EmailService
{
    private PHPMailer $mailer;

    /** @throws DataBaseException If SMTP configuration fails */
    public function __construct()
    {
        $this->mailer = SmtpConfiguration::create();
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
            return LocalMailFallback::send($this->mailer, $email);
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
            return LocalMailFallback::send($this->mailer, $email);
        }
    }

    /** @throws DataBaseException */
    public function sendSaeCreationNotification(
        string $responsableEmail,
        string $responsableNom,
        string $clientNom,
        string $saeTitle,
        string $saeDescription
    ): bool {
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
            return LocalMailFallback::send($this->mailer, $responsableEmail);
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
    ): bool {
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
                $studentNom,
                $saeTitre,
                $saeDescription,
                $responsableNom,
                $clientNom,
                $dateRenduFormatted
            );

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (student_assignment): ' . $e->getMessage());
            return LocalMailFallback::send($this->mailer, $studentEmail);
        }
    }

    /** @throws DataBaseException */
    public function sendClientStudentAssignmentNotification(
        string $clientEmail,
        string $clientNom,
        string $saeTitre,
        string $studentNom,
        string $responsableNom
    ): bool {
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
            return LocalMailFallback::send($this->mailer, $clientEmail);
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
            return LocalMailFallback::sendContact(
                SmtpConfiguration::getFromEmail($this->mailer),
                SmtpConfiguration::getFromName($this->mailer),
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
    ): bool {
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
                $studentNom,
                $saeTitre,
                $dateRenduFormatted,
                $heureRendu,
                $responsableNom
            );

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (deadline_reminder): ' . $e->getMessage());
            return LocalMailFallback::send($this->mailer, $studentEmail);
        }
    }

    /** @throws DataBaseException */
    public function sendUrgentDeadlineReminderEmail(
        string $studentEmail,
        string $studentNom,
        string $saeTitre,
        string $dateRendu,
        string $responsableNom
    ): bool {
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
                $studentNom,
                $saeTitre,
                $dateRenduFormatted,
                $responsableNom,
                $heureRendu
            );

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (urgent_deadline_reminder): ' . $e->getMessage());
            return LocalMailFallback::send($this->mailer, $studentEmail);
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
            return LocalMailFallback::send($this->mailer, $email);
        }
    }

    /** @throws DataBaseException */
    public function sendMessageToStudent(
        string $studentEmail,
        string $studentName,
        string $subject,
        string $message,
        string $responsableName
    ): bool {
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
            $this->mailer->AltBody = "Bonjour {$studentName},\n\n{$message}\n\nCordialement,\n
            {$responsableName}\nResponsable SAE Manager";

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (responsable_message): ' . $e->getMessage());
            return LocalMailFallback::send($this->mailer, $studentEmail);
        }
    }

    /**
     * Sends an inactivity warning (GDPR — account deleted in 30 days if no login).
     *
     * @throws DataBaseException
     */
    public function sendInactiveAccountWarningEmail(string $userEmail, string $userName): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($userEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Avis de suppression de compte pour inactivité - SAE Manager';

            $loginLink = $this->getBaseUrl() . '/user/login';
            $emailView = new EmailView('inactive_account_warning', [
                'USER_NAME'  => $userName,
                'LOGIN_LINK' => $loginLink,
            ]);
            $this->mailer->Body    = $emailView->render();
            $this->mailer->AltBody = $this->textInactiveAccountWarning($userName, $loginLink);

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (inactive_account_warning): ' . $e->getMessage());
            return LocalMailFallback::send($this->mailer, $userEmail);
        }
    }

    /**
     * Confirms account deletion after prolonged inactivity (GDPR).
     *
     * @throws DataBaseException
     */
    public function sendAccountDeletedNotificationEmail(string $userEmail, string $userName): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($userEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Confirmation de suppression de votre compte - SAE Manager';

            $emailView = new EmailView('account_deleted', ['USER_NAME' => $userName]);
            $this->mailer->Body    = $emailView->render();
            $this->mailer->AltBody = $this->textAccountDeleted($userName);

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('EmailService SMTP (account_deleted): ' . $e->getMessage());
            return LocalMailFallback::send($this->mailer, $userEmail);
        }
    }


    /**
     * Resolves the base URL (localhost → HTTP_HOST, then APP_URL env, then server config).
     */
    private function getBaseUrl(): string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $httpHost = is_string($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
            if (str_contains($httpHost, 'localhost') || str_contains($httpHost, '127.0.0.1')) {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                return $protocol . '://' . $httpHost;
            }
        }
        $appUrl = Database::parseEnvVar('APP_URL');
        if ($appUrl !== false && !empty($appUrl)) {
            return rtrim($appUrl, '/');
        }
        $protocol   = isset($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host       = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST']) ?
            $_SERVER['HTTP_HOST'] : 'localhost';
        $scriptName = isset($_SERVER['SCRIPT_NAME']) && is_string($_SERVER['SCRIPT_NAME']) ?
            $_SERVER['SCRIPT_NAME'] : '';
        $path       = $scriptName !== '' ? dirname($scriptName) : '';
        return $protocol . '://' . $host . $path;
    }

    private function textPasswordReset(string $resetLink): string
    {
        return "Réinitialisation de votre mot de passe - SAE Manager\n\n"
            . "Vous avez demandé la réinitialisation de votre mot de passe.\n\n"
            . "Lien : {$resetLink}\n\nCe lien est valide pendant 1 heure.\n\nCordialement,\nL'équipe SAE Manager";
    }

    private function textAccountVerification(string $verificationLink): string
    {
        return "Vérification de votre compte - SAE Manager\n\n"
            . "Merci de vous être inscrit. Pour activer votre compte, copiez ce lien :\n\n"
            . "{$verificationLink}\n\nCordialement,\nL'équipe SAE Manager";
    }

    private function textSaeCreation(string $responsableNom,
                                     string $clientNom, string $saeTitle, string $saeDescription): string
    {
        $saeUrl = $this->getBaseUrl() . '/sae';
        return "Bonjour {$responsableNom},\n\nUne nouvelle SAE a été créée par {$clientNom}.\n\n"
            . "TITRE : {$saeTitle}\nDESCRIPTION : {$saeDescription}\nCLIENT : {$clientNom}\n\n"
            . "{$saeUrl}\n\nCordialement,\nL'équipe SAE Manager";
    }

    private function textStudentAssignment(
        string $studentNom,
        string $saeTitre,
        string $saeDescription,
        string $responsableNom,
        string $clientNom,
        string $dateRendu
    ): string {
        $saeUrl = $this->getBaseUrl() . '/sae';
        return "Bonjour {$studentNom},\n\nVous avez été affecté(e) à une nouvelle SAE par {$responsableNom}.\n\n"
            . "TITRE : {$saeTitre}\nDESCRIPTION : {$saeDescription}\nCLIENT : {$clientNom}\n"
            . "RESPONSABLE : {$responsableNom}\nDATE DE RENDU : {$dateRendu}\n\n"
            . "{$saeUrl}\n\nBon courage !\nL'équipe SAE Manager";
    }

    private function textClientAssignment(string $clientNom,
                                          string $saeTitre, string $studentNames, string $responsableNom): string
    {
        $saeUrl = $this->getBaseUrl() . '/sae';
        return "Bonjour {$clientNom},\n\nUn ou plusieurs étudiants ont été affectés à votre SAE par 
        {$responsableNom}.\n\n"
            . "SAE : {$saeTitre}\nÉTUDIANT(S) : {$studentNames}\nRESPONSABLE : {$responsableNom}\n\n"
            . "{$saeUrl}\n\nCordialement,\nL'équipe SAE Manager";
    }

    private function textDeadlineReminder(string $studentNom, string $saeTitre,
                                          string $dateRendu, string $heureRendu, string $responsableNom): string
    {
        $saeUrl    = $this->getBaseUrl() . '/sae';
        $textHeure = $heureRendu ? " à $heureRendu" : "";
        return "Bonjour {$studentNom},\n\nRAPPEL : Il ne vous reste que 3 JOURS avant le rendu !\n\n"
            . "SAE : {$saeTitre}\nDATE DE RENDU : {$dateRendu}{$textHeure}\nRESPONSABLE : {$responsableNom}\n\n"
            . "Accéder à vos SAE : {$saeUrl}\n\nBon courage !\nL'équipe SAE Manager";
    }

    private function textUrgentDeadlineReminder(string $studentNom, string $saeTitre,
                                                string $dateRendu, string $responsableNom, string $heureRendu): string
    {
        $saeUrl    = $this->getBaseUrl() . '/sae';
        $precision = $heureRendu ? " à {$heureRendu}" : "";
        return "Bonjour {$studentNom},\n\n🚨 Il ne vous reste qu'UN SEUL JOUR !\n\n"
            . "SAE : {$saeTitre}\nDATE DE RENDU : DEMAIN ({$dateRendu}){$precision}\nRESPONSABLE : {$responsableNom}\n\n"
            . "Accéder à vos SAE : {$saeUrl}\n\nCordialement,\nL'équipe SAE Manager";
    }

    private function textPasswordChanged(string $loginLink): string
    {
        return "Bonjour,\n\nVotre mot de passe SAE Manager a été modifié avec succès.\n\n"
            . "Si vous n'êtes pas à l'origine de cette modification, réinitialisez votre mot de passe immédiatement.\n\n"
            . "Accéder à mon compte : {$loginLink}\n\nCordialement,\nL'équipe SAE Manager";
    }

    private function textInactiveAccountWarning(string $userName, string $loginLink): string
    {
        return "Bonjour {$userName},\n\nVotre compte SAE Manager est inactif depuis près de 3 ans.\n\n"
            . "Il sera supprimé dans 30 jours. Pour le conserver, connectez-vous :\n{$loginLink}\n\n"
            . "Cordialement,\nL'équipe SAE Manager";
    }

    private function textAccountDeleted(string $userName): string
    {
        return "Bonjour {$userName},\n\nVotre compte SAE Manager a été définitivement supprimé "
            . "en raison d'une inactivité prolongée (plus de 3 ans).\n\n"
            . "Toutes vos données ont été effacées.\n\nCordialement,\nL'équipe SAE Manager";
    }
}
