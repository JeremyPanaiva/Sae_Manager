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
}
