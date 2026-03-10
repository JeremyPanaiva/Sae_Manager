<?php

namespace Shared\Services\Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Models\Database;
use Shared\Exceptions\DataBaseException;

/**
 * SMTP Mailer Factory
 *
 * Single responsibility: configure a PHPMailer instance
 * using SMTP settings loaded from environment variables.
 *
 * This class acts as a plug-in for the EmailService,
 * keeping transport details isolated from business logic.
 *
 * @package Shared\Services\Email
 */
class SmtpConfiguration
{
    /**
     * Creates and returns a fully configured PHPMailer instance.
     *
     * @return PHPMailer Ready-to-use mailer instance
     * @throws DataBaseException If vendor dependencies are missing or configuration fails
     */
    public static function create(): PHPMailer
    {
        $autoload = __DIR__ . '/../../../../vendor/autoload.php';
        if (!file_exists($autoload)) {
            error_log('vendor/autoload.php not found — run composer install or upload the vendor/ folder');
            throw new DataBaseException('Missing dependencies for email sending.');
        }
        require_once $autoload;

        $mailer = new PHPMailer(true);
        self::configure($mailer);
        return $mailer;
    }

    /**
     * Applies SMTP configuration to an existing PHPMailer instance.
     *
     * Loads the following settings from environment variables:
     * - SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD, SMTP_SECURE
     * - SMTP_DEBUG, SMTP_DEBUG_FILE
     * - FROM_EMAIL, FROM_NAME
     * - SMTP_ALLOW_SELF_SIGNED
     *
     * @param PHPMailer $mailer The PHPMailer instance to configure
     * @throws DataBaseException If FROM_EMAIL is not set or configuration fails
     */
    public static function configure(PHPMailer $mailer): void
    {
        try {
            $mailer->isSMTP();

            $smtpHost   = Database::parseEnvVar('SMTP_HOST');
            $smtpUser   = Database::parseEnvVar('SMTP_USERNAME');
            $smtpPass   = Database::parseEnvVar('SMTP_PASSWORD');
            $smtpSecure = Database::parseEnvVar('SMTP_SECURE');

            $mailer->Host     = $smtpHost !== false ? $smtpHost : 'smtp.alwaysdata.com';
            $mailer->Username = $smtpUser !== false ? $smtpUser : '';
            $mailer->Password = $smtpPass !== false ? $smtpPass : '';
            $mailer->SMTPAuth = !empty($smtpUser) && !empty($smtpPass);

            $mailer->SMTPSecure = $smtpSecure === 'tls'
                ? PHPMailer::ENCRYPTION_STARTTLS
                : PHPMailer::ENCRYPTION_SMTPS;

            $smtpPort        = Database::parseEnvVar('SMTP_PORT');
            $mailer->Port    = (int) ($smtpPort !== false ? $smtpPort : 587);
            $mailer->CharSet = 'UTF-8';
            $mailer->SMTPAutoTLS = ($smtpSecure === 'tls');

            // SMTP debug output
            $smtpDebug = Database::parseEnvVar('SMTP_DEBUG');
            if ($smtpDebug !== false && ($smtpDebug === '1' || $smtpDebug === 'true')) {
                $mailer->SMTPDebug = SMTP::DEBUG_SERVER;

                $smtpDebugFile = Database::parseEnvVar('SMTP_DEBUG_FILE');
                if ($smtpDebugFile !== false && !empty($smtpDebugFile)) {
                    $logPath = $smtpDebugFile;
                    if ($logPath[0] !== '/') {
                        $logPath = __DIR__ . '/../../../../' . ltrim($logPath, '/');
                    }
                    $dir = dirname($logPath);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }
                    $mailer->Debugoutput = function ($str, $level) use ($logPath): void {
                        $line = sprintf("%s [level %s] %s\n", date('c'), $level, trim((string) $str));
                        @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
                    };
                } else {
                    $mailer->Debugoutput = 'error_log';
                }
            } else {
                $mailer->SMTPDebug   = SMTP::DEBUG_OFF;
                $mailer->Debugoutput = 'error_log';
            }

            // Sender identity
            $fromEmail   = Database::parseEnvVar('FROM_EMAIL');
            $fromNameRaw = Database::parseEnvVar('FROM_NAME');
            $fromName    = $fromNameRaw !== false ? $fromNameRaw : 'SAE Manager';

            if ($fromEmail === false || empty($fromEmail)) {
                error_log('SmtpConfiguration: FROM_EMAIL is not set.');
                throw new DataBaseException('FROM_EMAIL is not configured for email sending.');
            }

            $mailer->setFrom($fromEmail, $fromName);
            $mailer->addReplyTo($fromEmail, $fromName);

            // Allow self-signed SSL certificates (development only)
            $allowSelfSigned = Database::parseEnvVar('SMTP_ALLOW_SELF_SIGNED');
            if ($allowSelfSigned === '1' || $allowSelfSigned === 'true') {
                $mailer->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true,
                    ],
                ];
            }

            error_log(sprintf(
                'SmtpConfiguration config: host=%s port=%s user=%s secure=%s auth=%s',
                $mailer->Host,
                $mailer->Port,
                $mailer->Username ? 'set' : 'not-set',
                $smtpSecure !== false ? $smtpSecure : 'default',
                $mailer->SMTPAuth ? 'true' : 'false'
            ));
        } catch (Exception $e) {
            throw new DataBaseException('Email configuration error: ' . $e->getMessage());
        }
    }

    /**
     * Returns the FROM email address from the mailer or the environment.
     *
     * @param PHPMailer $mailer The configured mailer instance
     * @return string FROM email address
     */
    public static function getFromEmail(PHPMailer $mailer): string
    {
        $from = trim($mailer->From);
        if ($from !== '') {
            return $from;
        }
        $fromEmail = Database::parseEnvVar('FROM_EMAIL');
        return ($fromEmail !== false && !empty($fromEmail)) ? $fromEmail : 'noreply@sae-manager.com';
    }

    /**
     * Returns the FROM name from the mailer or the environment.
     *
     * @param PHPMailer $mailer The configured mailer instance
     * @return string FROM display name
     */
    public static function getFromName(PHPMailer $mailer): string
    {
        $fromName = trim($mailer->FromName);
        if ($fromName !== '') {
            return $fromName;
        }
        $fromNameEnv = Database::parseEnvVar('FROM_NAME');
        return ($fromNameEnv !== false && $fromNameEnv !== '') ? $fromNameEnv : 'SAE Manager';
    }
}
