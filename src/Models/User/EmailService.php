<?php

namespace Models\User;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Models\Database;
use Shared\Exceptions\DataBaseException;
use Views\Email\EmailView;

class EmailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $autoload = __DIR__ . '/../../../vendor/autoload.php';
        if (!file_exists($autoload)) {
            error_log('vendor/autoload.php manquant — exécuter composer install ou uploader le dossier vendor/');
            throw new DataBaseException('Dépendances manquantes pour l\'envoi d\'emails.');
        }
        require_once $autoload;

        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    private function configureMailer(): void
    {
        try {
            $this->mailer->isSMTP();
            $smtpHost = Database::parseEnvVar('SMTP_HOST') ?: 'smtp.alwaysdata.com';
            $smtpUser = Database::parseEnvVar('SMTP_USERNAME');
            $smtpPass = Database::parseEnvVar('SMTP_PASSWORD');
            $smtpSecure = Database::parseEnvVar('SMTP_SECURE');

            $this->mailer->Host = $smtpHost;
            $this->mailer->Username = $smtpUser;
            $this->mailer->Password = $smtpPass;

            $this->mailer->SMTPAuth = !empty($smtpUser) && !empty($smtpPass);

            $this->mailer->SMTPSecure = $smtpSecure === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $this->mailer->Port = (int) (Database::parseEnvVar('SMTP_PORT') ?: 587);
            $this->mailer->CharSet = 'UTF-8';

            $this->mailer->SMTPAutoTLS = ($smtpSecure === 'tls');

            $smtpDebug = Database::parseEnvVar('SMTP_DEBUG');
            if ($smtpDebug !== false && ($smtpDebug === '1' || $smtpDebug === 'true')) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;

                $smtpDebugFile = Database::parseEnvVar('SMTP_DEBUG_FILE');
                if (!empty($smtpDebugFile)) {
                    $logPath = $smtpDebugFile;
                    if ($logPath[0] !== '/') {
                        $logPath = __DIR__ . '/../../../' . ltrim($logPath, '/');
                    }

                    $dir = dirname($logPath);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }

                    $this->mailer->Debugoutput = function ($str, $level) use ($logPath) {
                        $line = sprintf("%s [level %s] %s\n", date('c'), $level, trim($str));
                        @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
                    };
                } else {
                    $this->mailer->Debugoutput = 'error_log';
                }
            } else {
                $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;
                $this->mailer->Debugoutput = 'error_log';
            }

            $fromEmail = Database::parseEnvVar('FROM_EMAIL');
            $fromName = Database::parseEnvVar('FROM_NAME') ?: 'SAE Manager';

            if (empty($fromEmail)) {
                error_log('EmailService configuration: FROM_EMAIL is not set.');
                throw new DataBaseException('FROM_EMAIL n\'est pas configuré pour l\'envoi d\'emails.');
            }

            $this->mailer->setFrom($fromEmail, $fromName);
            $this->mailer->addReplyTo($fromEmail, $fromName);

            $allowSelfSigned = Database::parseEnvVar('SMTP_ALLOW_SELF_SIGNED');
            if ($allowSelfSigned === '1' || $allowSelfSigned === 'true') {
                $this->mailer->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
            }

            error_log(sprintf(
                "EmailService SMTP config: host=%s port=%s user=%s secure=%s auth=%s",
                $this->mailer->Host,
                $this->mailer->Port,
                $this->mailer->Username ? 'set' : 'not-set',
                $smtpSecure,
                $this->mailer->SMTPAuth ? 'true' : 'false'
            ));
        } catch (Exception $e) {
            throw new DataBaseException("Erreur de configuration email : " . $e->getMessage());
        }
    }

    /**
     * Envoie un email de réinitialisation de mot de passe
     */
    public function sendPasswordResetEmail(string $email, string $token): bool
    {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($email);
        $this->mailer->isHTML(true);
        $this->mailer->Subject = 'Réinitialisation de votre mot de passe - SAE Manager';

        $resetLink = $this->getBaseUrl() . "/user/reset-password?token=" . $token;

        $emailView = new EmailView('password_reset', [
            'RESET_LINK' => $resetLink
        ]);

        $this->mailer->Body = $emailView->render();
        $this->mailer->AltBody = $this->getPasswordResetEmailTextBody($resetLink);

        try {
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            $phpmailerError = $this->mailer->ErrorInfo ?? 'no additional info';
            error_log('PHPMailer SMTP exception: ' . $e->getMessage() . ' | PHPMailer ErrorInfo: ' . $phpmailerError);

            try {
                $mail = new PHPMailer(true);
                $mail->isMail();
                $mail->CharSet = 'UTF-8';
                $from = $this->mailer->From ?? Database::parseEnvVar('FROM_EMAIL');
                $fromName = $this->mailer->FromName ?? Database::parseEnvVar('FROM_NAME') ?: 'SAE Manager';
                if (!empty($from)) {
                    $mail->setFrom($from, $fromName);
                    $mail->addReplyTo($from, $fromName);
                }

                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = $this->mailer->Subject;
                $mail->Body = $this->mailer->Body;
                $mail->AltBody = $this->mailer->AltBody;

                $mail->send();
                error_log('Email sent via local mail() fallback to ' . $email);
                return true;
            } catch (Exception $e2) {
                $phpmailerError2 = $mail->ErrorInfo ?? 'no additional info';
                error_log('PHPMailer fallback exception: ' . $e2->getMessage() . ' | PHPMailer ErrorInfo: ' . $phpmailerError2);
                throw new DataBaseException("Erreur d'envoi d'email (SMTP et fallback): " . $e2->getMessage());
            }
        }
    }

    /**
     * Envoie un email de vérification de compte
     */
    public function sendAccountVerificationEmail(string $email, string $token): bool
    {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($email);
        $this->mailer->isHTML(true);
        $this->mailer->Subject = 'Vérification de votre compte - SAE Manager';

        $verificationLink = $this->getBaseUrl() . "/user/verify-email?token=" . $token;

        $emailView = new EmailView('account_verification', [
            'VERIFICATION_LINK' => $verificationLink
        ]);

        $this->mailer->Body = $emailView->render();
        $this->mailer->AltBody = $this->getAccountVerificationEmailTextBody($verificationLink);

        try {
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            $phpmailerError = $this->mailer->ErrorInfo ?? 'no additional info';
            error_log('PHPMailer SMTP exception (account verification): ' . $e->getMessage() . ' | PHPMailer ErrorInfo: ' . $phpmailerError);

            try {
                $mail = new PHPMailer(true);
                $mail->isMail();
                $mail->CharSet = 'UTF-8';
                $from = $this->mailer->From ?? Database::parseEnvVar('FROM_EMAIL');
                $fromName = $this->mailer->FromName ?? Database::parseEnvVar('FROM_NAME') ?: 'SAE Manager';
                if (!empty($from)) {
                    $mail->setFrom($from, $fromName);
                    $mail->addReplyTo($from, $fromName);
                }

                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = $this->mailer->Subject;
                $mail->Body = $this->mailer->Body;
                $mail->AltBody = $this->mailer->AltBody;

                $mail->send();
                error_log('Email sent via local mail() fallback to ' . $email);
                return true;
            } catch (Exception $e2) {
                $phpmailerError2 = $mail->ErrorInfo ?? 'no additional info';
                error_log('PHPMailer fallback exception (account verification): ' . $e2->getMessage() . ' | PHPMailer ErrorInfo: ' . $phpmailerError2);
                throw new DataBaseException("Erreur d'envoi d'email (SMTP et fallback): " . $e2->getMessage());
            }
        }
    }

    /**
     * Envoie un email de notification au responsable lors de la création d'une SAE
     */
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
                'SAE_URL' => $saeUrl
            ]);

            $this->mailer->Body = $emailView->render();
            $this->mailer->AltBody = $this->getSaeCreationEmailTextBody($responsableNom, $clientNom, $saeTitle, $saeDescription);

            $this->mailer->send();
            error_log("Email de notification SAE envoyé à {$responsableEmail}");
            return true;

        } catch (Exception $e) {
            $phpmailerError = $this->mailer->ErrorInfo ?? 'no additional info';
            error_log('PHPMailer SMTP exception (SAE notification): ' . $e->getMessage() . ' | PHPMailer ErrorInfo: ' . $phpmailerError);

            try {
                $mail = new PHPMailer(true);
                $mail->isMail();
                $mail->CharSet = 'UTF-8';

                $from = $this->mailer->From ?? Database::parseEnvVar('FROM_EMAIL');
                $fromName = $this->mailer->FromName ?? Database::parseEnvVar('FROM_NAME') ?: 'SAE Manager';
                if (!empty($from)) {
                    $mail->setFrom($from, $fromName);
                    $mail->addReplyTo($from, $fromName);
                }

                $mail->addAddress($responsableEmail);
                $mail->isHTML(true);
                $mail->Subject = $this->mailer->Subject;
                $mail->Body = $this->mailer->Body;
                $mail->AltBody = $this->mailer->AltBody;

                $mail->send();
                error_log('Email SAE notification sent via local mail() fallback to ' . $responsableEmail);
                return true;

            } catch (Exception $e2) {
                $phpmailerError2 = $mail->ErrorInfo ?? 'no additional info';
                error_log('PHPMailer fallback exception (SAE notification): ' . $e2->getMessage() . ' | PHPMailer ErrorInfo: ' . $phpmailerError2);
                throw new DataBaseException("Erreur d'envoi d'email de notification SAE: " . $e2->getMessage());
            }
        }
    }

    /**
     * Envoie un email à un étudiant lors de son affectation à une SAE
     */
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

            $dateRenduFormatted = !empty($dateRendu) ? date('d/m/Y', strtotime($dateRendu)) : 'Non définie';
            $saeUrl = $this->getBaseUrl() . '/sae';

            $emailView = new EmailView('student_assignment', [
                'STUDENT_NAME' => $studentNom,
                'SAE_TITLE' => $saeTitre,
                'SAE_DESCRIPTION' => $saeDescription,
                'RESPONSABLE_NAME' => $responsableNom,
                'CLIENT_NAME' => $clientNom,
                'DATE_RENDU' => $dateRenduFormatted,
                'SAE_URL' => $saeUrl
            ]);

            $this->mailer->Body = $emailView->render();
            $this->mailer->AltBody = $this->getStudentAssignmentEmailTextBody(
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
            error_log('PHPMailer SMTP exception (student assignment): ' . $e->getMessage());

            try {
                $mail = new PHPMailer(true);
                $mail->isMail();
                $mail->CharSet = 'UTF-8';

                $from = $this->mailer->From ?? Database::parseEnvVar('FROM_EMAIL');
                $fromName = $this->mailer->FromName ?? Database::parseEnvVar('FROM_NAME') ?: 'SAE Manager';
                if (!empty($from)) {
                    $mail->setFrom($from, $fromName);
                    $mail->addReplyTo($from, $fromName);
                }

                $mail->addAddress($studentEmail);
                $mail->isHTML(true);
                $mail->Subject = $this->mailer->Subject;
                $mail->Body = $this->mailer->Body;
                $mail->AltBody = $this->mailer->AltBody;

                $mail->send();
                return true;
            } catch (Exception $e2) {
                error_log('PHPMailer fallback exception (student assignment): ' . $e2->getMessage());
                throw new DataBaseException("Erreur d'envoi d'email d'affectation: " . $e2->getMessage());
            }
        }
    }

    /**
     * Envoie un email au client lors de l'affectation d'un étudiant à sa SAE
     */
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
                'SAE_URL' => $saeUrl
            ]);

            $this->mailer->Body = $emailView->render();
            $this->mailer->AltBody = $this->getClientAssignmentEmailTextBody(
                $clientNom,
                $saeTitre,
                $studentNom,
                $responsableNom
            );

            $this->mailer->send();
            return true;

        } catch (Exception $e) {
            error_log('PHPMailer SMTP exception (client assignment): ' . $e->getMessage());

            try {
                $mail = new PHPMailer(true);
                $mail->isMail();
                $mail->CharSet = 'UTF-8';

                $from = $this->mailer->From ?? Database::parseEnvVar('FROM_EMAIL');
                $fromName = $this->mailer->FromName ?? Database::parseEnvVar('FROM_NAME') ?: 'SAE Manager';
                if (!empty($from)) {
                    $mail->setFrom($from, $fromName);
                    $mail->addReplyTo($from, $fromName);
                }

                $mail->addAddress($clientEmail);
                $mail->isHTML(true);
                $mail->Subject = $this->mailer->Subject;
                $mail->Body = $this->mailer->Body;
                $mail->AltBody = $this->mailer->AltBody;

                $mail->send();
                return true;
            } catch (Exception $e2) {
                error_log('PHPMailer fallback exception (client assignment): ' . $e2->getMessage());
                throw new DataBaseException("Erreur d'envoi d'email au client: " . $e2->getMessage());
            }
        }
    }

    private function getBaseUrl(): string
    {
        // En local, on utilise toujours l'hôte détecté pour éviter les redirections impromptues vers la prod
        if (isset($_SERVER['HTTP_HOST']) && (str_contains($_SERVER['HTTP_HOST'], 'localhost') || str_contains($_SERVER['HTTP_HOST'], '127.0.0.1'))) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            return $protocol . '://' . $_SERVER['HTTP_HOST'];
        }

        $appUrl = Database::parseEnvVar('APP_URL');
        if (!empty($appUrl)) {
            return rtrim($appUrl, '/');
        }

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['SCRIPT_NAME']);
        return $protocol . '://' . $host . $path;
    }

    // ===== Versions texte (AltBody) =====

    private function getPasswordResetEmailTextBody(string $resetLink): string
    {
        return "Réinitialisation de votre mot de passe - SAE Manager\n\nVous avez demandé la réinitialisation de votre mot de passe.\n\nLien : {$resetLink}\n\nCe lien est valide pendant 1 heure.\n\nCordialement,\nL'équipe SAE Manager";
    }

    private function getAccountVerificationEmailTextBody(string $verificationLink): string
    {
        return "Vérification de votre compte - SAE Manager\n\nMerci de vous être inscrit. Pour activer votre compte, veuillez copier et coller le lien suivant dans votre navigateur :\n\nLien : {$verificationLink}\n\nCordialement,\nL'équipe SAE Manager";
    }

    private function getSaeCreationEmailTextBody(string $responsableNom, string $clientNom, string $saeTitle, string $saeDescription): string
    {
        $saeUrl = $this->getBaseUrl() . '/sae';
        return "Bonjour {$responsableNom},\n\nUne nouvelle SAE a été créée par {$clientNom}.\n\nTITRE : {$saeTitle}\nDESCRIPTION : {$saeDescription}\nCLIENT : {$clientNom}\n\n{$saeUrl}\n\nCordialement,\nL'équipe SAE Manager";
    }

    private function getStudentAssignmentEmailTextBody(string $studentNom, string $saeTitre, string $saeDescription, string $responsableNom, string $clientNom, string $dateRendu): string
    {
        $saeUrl = $this->getBaseUrl() . '/sae';
        return "Bonjour {$studentNom},\n\nVous avez été affecté(e) à une nouvelle SAE par {$responsableNom}.\n\nTITRE : {$saeTitre}\nDESCRIPTION : {$saeDescription}\nCLIENT : {$clientNom}\nRESPONSABLE : {$responsableNom}\nDATE DE RENDU : {$dateRendu}\n\n{$saeUrl}\n\nBon courage !\nL'équipe SAE Manager";
    }

    private function getClientAssignmentEmailTextBody(string $clientNom, string $saeTitre, string $studentNom, string $responsableNom): string
    {
        $saeUrl = $this->getBaseUrl() . '/sae';
        return "Bonjour {$clientNom},\n\nUn étudiant a été affecté à votre SAE par le responsable {$responsableNom}.\n\nSAE : {$saeTitre}\nÉTUDIANT AFFECTÉ : {$studentNom}\nRESPONSABLE : {$responsableNom}\n\n{$saeUrl}\n\nCordialement,\nL'équipe SAE Manager";
    }

    public function sendContactEmail(string $fromUserEmail, string $subject, string $message): bool
    {
        try {
            // Nettoyage
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            // Destinataire de contact
            $to = 'sae-manager@alwaysdata.net';
            $this->mailer->addAddress($to);

            // Reply-To = email de l'utilisateur
            if (filter_var($fromUserEmail, FILTER_VALIDATE_EMAIL)) {
                $this->mailer->addReplyTo($fromUserEmail);
            }

            // Sujet / contenu
            $safeSubject = str_replace(["\r", "\n"], ' ', $subject);
            $this->mailer->isHTML(false);
            $this->mailer->Subject = '[Contact] ' . $safeSubject;
            $this->mailer->Body = "Message envoyé depuis le formulaire de contact SAE Manager\n\n"
                . "De       : {$fromUserEmail}\n"
                . "Sujet    : {$safeSubject}\n"
                . "---------\n\n"
                . "{$message}\n";

            $this->mailer->send();
            error_log("Contact email sent to {$to} (reply-to: {$fromUserEmail})");
            return true;
        } catch (Exception $e) {
            $phpmailerError = $this->mailer->ErrorInfo ?? 'no additional info';
            error_log('PHPMailer SMTP exception (contact): ' . $e->getMessage() . ' | ErrorInfo: ' . $phpmailerError);

            // Fallback mail() local si configuré
            try {
                $mail = new PHPMailer(true);
                $mail->isMail();
                $mail->CharSet = 'UTF-8';

                $from = $this->mailer->From ?? \Models\Database::parseEnvVar('FROM_EMAIL');
                $fromName = $this->mailer->FromName ?? \Models\Database::parseEnvVar('FROM_NAME') ?: 'SAE Manager';
                if (!empty($from)) {
                    $mail->setFrom($from, $fromName);
                    $mail->addReplyTo($fromUserEmail ?: $from, $fromName);
                }

                $mail->addAddress('sae-manager@alwaysdata.net');
                $mail->isHTML(false);
                $mail->Subject = '[Contact] ' . $safeSubject;
                $mail->Body = "Message envoyé depuis le formulaire de contact SAE Manager\n\n"
                    . "De       : {$fromUserEmail}\n"
                    . "Sujet    : {$safeSubject}\n"
                    . "---------\n\n"
                    . "{$message}\n";

                $mail->send();
                error_log('Contact email sent via local mail() fallback');
                return true;
            } catch (Exception $e2) {
                $phpmailerError2 = $mail->ErrorInfo ?? 'no additional info';
                error_log('PHPMailer fallback exception (contact): ' . $e2->getMessage() . ' | ErrorInfo: ' . $phpmailerError2);
                throw new DataBaseException("Erreur d'envoi d'email de contact: " . $e2->getMessage());
            }
        }
    }
}