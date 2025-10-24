<?php

namespace Models\User;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Models\Database;
use Shared\Exceptions\DataBaseException;

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
            $this->mailer->Port = (int)(Database::parseEnvVar('SMTP_PORT') ?: 587);
            $this->mailer->CharSet = 'UTF-8';

            $this->mailer->SMTPAutoTLS = ($smtpSecure === 'tls');

            $smtpDebug = Database::parseEnvVar('SMTP_DEBUG');
            if ($smtpDebug !== false && ($smtpDebug === '1' || $smtpDebug === 'true')) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;

                $smtpDebugFile = Database::parseEnvVar('SMTP_DEBUG_FILE');
                if (!empty($smtpDebugFile)) {
                    // Resolve relative path to project root
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

            // Set from and reply-to
            $this->mailer->setFrom($fromEmail, $fromName);
            $this->mailer->addReplyTo($fromEmail, $fromName);

            // Support pour certificats autosignés si nécessaire (débogage)
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

            // Log des paramètres SMTP (ne pas inclure mot de passe complet en prod)
            error_log(sprintf("EmailService SMTP config: host=%s port=%s user=%s secure=%s auth=%s",
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
     *
     * @throws DataBaseException
     */
    public function sendPasswordResetEmail(string $email, string $token): bool
    {
        // Préparer le message
        $this->mailer->addAddress($email);
        $this->mailer->isHTML(true);
        $this->mailer->Subject = 'Réinitialisation de votre mot de passe - SAE Manager';

        $resetLink = $this->getBaseUrl() . "/user/reset-password?token=" . $token;

        $this->mailer->Body = $this->getPasswordResetEmailBody($resetLink);
        $this->mailer->AltBody = $this->getPasswordResetEmailTextBody($resetLink);

        try {
            // Première tentative : SMTP (configuration faite dans le constructeur)
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            // Logger l'erreur PHPMailer pour faciliter le diagnostic (inclut ErrorInfo)
            $phpmailerError = $this->mailer->ErrorInfo ?? 'no additional info';
            error_log('PHPMailer SMTP exception: ' . $e->getMessage() . ' | PHPMailer ErrorInfo: ' . $phpmailerError);

            // Solution simple et rapide : retenter via le MTA local (mail()) en fallback
            try {
                $mail = new PHPMailer(true);
                $mail->isMail();
                $mail->CharSet = 'UTF-8';
                // conserver l'expéditeur configuré
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
                // Échec du fallback : logger et renvoyer une exception gérée
                $phpmailerError2 = $mail->ErrorInfo ?? 'no additional info';
                error_log('PHPMailer fallback exception: ' . $e2->getMessage() . ' | PHPMailer ErrorInfo: ' . $phpmailerError2);
                throw new DataBaseException("Erreur d'envoi d'email (SMTP et fallback): " . $e2->getMessage());
            }
        }
    }

    /**
     * Envoie un email de notification au responsable lors de la création d'une SAE
     *
     * @param string $responsableEmail Email du responsable
     * @param string $responsableNom Nom du responsable
     * @param string $clientNom Nom complet du client
     * @param string $saeTitle Titre de la SAE créée
     * @param string $saeDescription Description de la SAE
     * @return bool
     * @throws DataBaseException
     */
    public function sendSaeCreationNotification(
        string $responsableEmail,
        string $responsableNom,
        string $clientNom,
        string $saeTitle,
        string $saeDescription
    ): bool {
        try {
            // Réinitialiser le mailer pour un nouvel envoi
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();

            $this->mailer->addAddress($responsableEmail);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Nouvelle SAE créée - ' . $saeTitle;

            $this->mailer->Body = $this->getSaeCreationEmailBody(
                $responsableNom,
                $clientNom,
                $saeTitle,
                $saeDescription
            );
            $this->mailer->AltBody = $this->getSaeCreationEmailTextBody(
                $responsableNom,
                $clientNom,
                $saeTitle,
                $saeDescription
            );

            $this->mailer->send();
            error_log("Email de notification SAE envoyé à {$responsableEmail}");
            return true;

        } catch (Exception $e) {
            $phpmailerError = $this->mailer->ErrorInfo ?? 'no additional info';
            error_log('PHPMailer SMTP exception (SAE notification): ' . $e->getMessage() . ' | PHPMailer ErrorInfo: ' . $phpmailerError);

            // Fallback avec mail()
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

            $this->mailer->Body = $this->getStudentAssignmentEmailBody(
                $studentNom,
                $saeTitre,
                $saeDescription,
                $responsableNom,
                $clientNom,
                $dateRenduFormatted
            );
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

            $this->mailer->Body = $this->getClientAssignmentEmailBody(
                $clientNom,
                $saeTitre,
                $studentNom,
                $responsableNom
            );
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
        $appUrl = Database::parseEnvVar('APP_URL');
        if (!empty($appUrl)) {
            return rtrim($appUrl, '/');
        }

        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['SCRIPT_NAME']);
        return $protocol . '://' . $host . $path;
    }

    private function getPasswordResetEmailBody(string $resetLink): string
    {
        return "
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Réinitialisation de mot de passe</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>Réinitialisation de votre mot de passe</h2>
                
                <p>Bonjour,</p>
                
                <p>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte SAE Manager.</p>
                
                <p>Pour réinitialiser votre mot de passe, cliquez sur le lien ci-dessous :</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$resetLink}' 
                       style='background-color: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>
                        Réinitialiser mon mot de passe
                    </a>
                </div>
                
                <p><strong>Ce lien est valide pendant 1 heure.</strong></p>
                
                <p>Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.</p>
                
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                
                <p style='font-size: 12px; color: #666;'>
                    Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :<br>
                    <a href='{$resetLink}'>{$resetLink}</a>
                </p>
            </div>
        </body>
        </html>";
    }

    private function getPasswordResetEmailTextBody(string $resetLink): string
    {
        return "
Réinitialisation de votre mot de passe - SAE Manager

Bonjour,

Vous avez demandé la réinitialisation de votre mot de passe pour votre compte SAE Manager.

Pour réinitialiser votre mot de passe, cliquez sur le lien suivant :
{$resetLink}

Ce lien est valide pendant 1 heure.

Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.

Cordialement,
L'équipe SAE Manager
        ";
    }

    private function getSaeCreationEmailBody(
        string $responsableNom,
        string $clientNom,
        string $saeTitle,
        string $saeDescription
    ): string {
        $saeUrl = $this->getBaseUrl() . '/sae';

        return "
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Nouvelle SAE créée</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
                <div style='background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                    <h2 style='color: #2c3e50; margin-top: 0;'>Bonjour {$responsableNom},</h2>
                    
                    <p>Une nouvelle SAE a été créée par <strong>{$clientNom}</strong> et nécessite votre attention.</p>
                    
                    <div style='background-color: #ecf0f1; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <h3 style='color: #34495e; margin-top: 0;'>📋 {$saeTitle}</h3>
                        <p style='color: #555;'><strong>Description :</strong></p>
                        <p style='color: #555;'>{$saeDescription}</p>
                        <p style='color: #555;'><strong>Client :</strong> {$clientNom}</p>
                    </div>
                    
                    <p>Vous pouvez consulter cette SAE et l'attribuer à des étudiants en vous connectant à la plateforme.</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$saeUrl}' style='display: inline-block; padding: 12px 30px; background-color: #3498db; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                            Voir les SAE
                        </a>
                    </div>
                    
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    
                    <p style='font-size: 0.9em; color: #7f8c8d;'>
                        Cordialement,<br>
                        L'équipe SAE Manager
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getSaeCreationEmailTextBody(
        string $responsableNom,
        string $clientNom,
        string $saeTitle,
        string $saeDescription
    ): string {
        $saeUrl = $this->getBaseUrl() . '/sae';

        return "
Bonjour {$responsableNom},

Une nouvelle SAE a été créée par {$clientNom} et nécessite votre attention.

TITRE : {$saeTitle}

DESCRIPTION :
{$saeDescription}

CLIENT : {$clientNom}

Vous pouvez consulter cette SAE et l'attribuer à des étudiants en vous connectant à la plateforme :
{$saeUrl}

Cordialement,
L'équipe SAE Manager
        ";
    }

    private function getStudentAssignmentEmailBody(
        string $studentNom,
        string $saeTitre,
        string $saeDescription,
        string $responsableNom,
        string $clientNom,
        string $dateRendu
    ): string {
        $saeUrl = $this->getBaseUrl() . '/sae';

        return "
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Nouvelle affectation SAE</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
                <div style='background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                    <div style='background-color: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; margin: -30px -30px 20px -30px;'>
                        <h1 style='margin: 0; font-size: 24px;'>🎓 Nouvelle Affectation SAE</h1>
                    </div>
                    
                    <p>Bonjour <strong>{$studentNom}</strong>,</p>
                    
                    <p>Vous avez été affecté(e) à une nouvelle SAE par <strong>{$responsableNom}</strong>.</p>
                    
                    <div style='background-color: #e8f5e9; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #4CAF50;'>
                        <h3 style='color: #2e7d32; margin-top: 0;'>📋 {$saeTitre}</h3>
                        <p style='color: #555; margin: 10px 0;'><strong>Description :</strong></p>
                        <p style='color: #555; margin: 10px 0;'>{$saeDescription}</p>
                        <hr style='border: none; border-top: 1px dashed #4CAF50; margin: 15px 0;'>
                        <p style='color: #555; margin: 5px 0;'><strong>👨‍💼 Client :</strong> {$clientNom}</p>
                        <p style='color: #555; margin: 5px 0;'><strong>👨‍🏫 Responsable :</strong> {$responsableNom}</p>
                        <p style='color: #555; margin: 5px 0;'><strong>📅 Date de rendu :</strong> {$dateRendu}</p>
                    </div>
                    
                    <p>Vous pouvez consulter les détails de cette SAE et suivre votre progression sur la plateforme.</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$saeUrl}' style='display: inline-block; padding: 12px 30px; background-color: #4CAF50; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                            Voir mes SAE
                        </a>
                    </div>
                    
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    
                    <p style='font-size: 0.9em; color: #7f8c8d;'>
                        Bon courage !<br>
                        L'équipe SAE Manager
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getStudentAssignmentEmailTextBody(
        string $studentNom,
        string $saeTitre,
        string $saeDescription,
        string $responsableNom,
        string $clientNom,
        string $dateRendu
    ): string {
        $saeUrl = $this->getBaseUrl() . '/sae';

        return "
Bonjour {$studentNom},

Vous avez été affecté(e) à une nouvelle SAE par {$responsableNom}.

TITRE : {$saeTitre}

DESCRIPTION :
{$saeDescription}

CLIENT : {$clientNom}
RESPONSABLE : {$responsableNom}
DATE DE RENDU : {$dateRendu}

Vous pouvez consulter les détails de cette SAE et suivre votre progression sur la plateforme :
{$saeUrl}

Bon courage !
L'équipe SAE Manager
        ";
    }

    private function getClientAssignmentEmailBody(
        string $clientNom,
        string $saeTitre,
        string $studentNom,
        string $responsableNom
    ): string {
        $saeUrl = $this->getBaseUrl() . '/sae';

        return "
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Affectation d'un étudiant à votre SAE</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9;'>
                <div style='background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                    <div style='background-color: #2196F3; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; margin: -30px -30px 20px -30px;'>
                        <h1 style='margin: 0; font-size: 24px;'>✅ Étudiant Affecté à Votre SAE</h1>
                    </div>
                    
                    <p>Bonjour <strong>{$clientNom}</strong>,</p>
                    
                    <p>Nous vous informons qu'un étudiant a été affecté à votre SAE par le responsable <strong>{$responsableNom}</strong>.</p>
                    
                    <div style='background-color: #e3f2fd; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #2196F3;'>
                        <h3 style='color: #1565c0; margin-top: 0;'>📋 {$saeTitre}</h3>
                        <p style='color: #555; margin: 10px 0;'><strong>🎓 Étudiant affecté :</strong> {$studentNom}</p>
                        <p style='color: #555; margin: 10px 0;'><strong>👨‍🏫 Responsable :</strong> {$responsableNom}</p>
                    </div>
                    
                    <p>L'étudiant commencera bientôt à travailler sur votre projet. N'hésitez pas à vous connecter à la plateforme pour suivre l'avancement.</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$saeUrl}' style='display: inline-block; padding: 12px 30px; background-color: #2196F3; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                            Voir mes SAE
                        </a>
                    </div>
                    
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                    
                    <p style='font-size: 0.9em; color: #7f8c8d;'>
                        Cordialement,<br>
                        L'équipe SAE Manager
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getClientAssignmentEmailTextBody(
        string $clientNom,
        string $saeTitre,
        string $studentNom,
        string $responsableNom
    ): string {
        $saeUrl = $this->getBaseUrl() . '/sae';

        return "
Bonjour {$clientNom},

Nous vous informons qu'un étudiant a été affecté à votre SAE par le responsable {$responsableNom}.

SAE : {$saeTitre}
ÉTUDIANT AFFECTÉ : {$studentNom}
RESPONSABLE : {$responsableNom}

L'étudiant commencera bientôt à travailler sur votre projet. N'hésitez pas à vous connecter à la plateforme pour suivre l'avancement :
{$saeUrl}

Cordialement,
L'équipe SAE Manager
        ";
    }
}