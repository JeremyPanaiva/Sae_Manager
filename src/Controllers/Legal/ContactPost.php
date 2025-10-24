<?php

namespace Controllers\Legal;

use Controllers\ControllerInterface;
use Models\User\EmailService;

class ContactPost implements ControllerInterface
{
    public const PATH = '/contact';

    public static function support(string $path, string $method): bool
    {
        return $path === self::PATH && $method === 'POST';
    }

    public function control(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . self::PATH);
            exit();
        }

        $email   = trim($_POST['email']   ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($email === '' || $subject === '' || $message === '') {
            header('Location: ' . self::PATH . '?error=missing_fields');
            exit();
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . self::PATH . '?error=invalid_email');
            exit();
        }

        try {
            $mailer = new EmailService();
            $ok = $mailer->sendContactEmail($email, $subject, $message);
            if ($ok) {
                header('Location: ' . self::PATH . '?success=message_sent');
            } else {
                header('Location: ' . self::PATH . '?error=mail_failed');
            }
        } catch (\Throwable $e) {
            error_log('ContactPost error: ' . $e->getMessage());
            header('Location: ' . self::PATH . '?error=mail_failed');
        }
        exit();
    }
}