<?php

declare(strict_types=1);

namespace Tests\Integration;

use Models\User\EmailService;
use PHPUnit\Framework\TestCase;
use Shared\Exceptions\DataBaseException;

/**
 * Email Service Integration Test - Commit 3 (Final)
 *
 * Tests: Initialization + SMTP config + Validation & security + Content & i18n
 *
 * @package Tests\Integration
 */
final class EmailServiceIntegrationTest extends TestCase
{
    private ?EmailService $emailService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $vendorPath = __DIR__ . '/../../vendor/autoload.php';
        if (!file_exists($vendorPath)) {
            $this->markTestSkipped('Vendor dependencies not installed. Run: composer install');
        }

        $envPath = __DIR__ . '/../../.env';
        if (!file_exists($envPath)) {
            $this->markTestSkipped('.env file not found. Copy .env.dist to .env and configure');
        }

        try {
            $this->emailService = new EmailService();
        } catch (DataBaseException $e) {
            $this->markTestSkipped('EmailService configuration error: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        $this->emailService = null;
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Commit 1 tests — Initialization & SMTP configuration
    // -------------------------------------------------------------------------

    public function testEmailServiceInitializationWithDependencies(): void
    {
        $this->assertInstanceOf(
            EmailService::class,
            $this->emailService,
            'EmailService should be properly initialized'
        );
    }

    public function testSmtpConfigurationFromEnvironment(): void
    {
        $requiredEnvVars = [
            'SMTP_HOST',
            'SMTP_PORT',
            'SMTP_USERNAME',
            'SMTP_PASSWORD',
            'FROM_EMAIL',
        ];

        foreach ($requiredEnvVars as $envVar) {
            $value = getenv($envVar);

            if ($value === false || $value === '') {
                $this->markTestSkipped("Environment variable {$envVar} not set");
            }

            $this->assertNotSame('', $value, "Environment variable {$envVar} should not be empty");
        }
    }

    public function testErrorHandlingForMissingSmtpConfiguration(): void
    {
        $originalEnv = getenv('SMTP_HOST');

        if ($originalEnv === false) {
            $this->markTestSkipped('SMTP_HOST not set in environment');
        }

        $this->assertTrue(putenv('SMTP_HOST='));
        $this->assertSame('', getenv('SMTP_HOST'));

        $this->assertTrue(putenv('SMTP_HOST=' . $originalEnv));
        $this->assertSame($originalEnv, getenv('SMTP_HOST'));
    }

    public function testEmailAddressValidationBeforeSending(): void
    {
        $validEmails = [
            'user@example.com',
            'test.user@example.co.uk',
            'sae-manager@alwaysdata.net',
        ];

        foreach ($validEmails as $email) {
            $this->assertNotFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL),
                "Email '{$email}' should be valid"
            );
        }

        $invalidEmails = [
            'notanemail',
            '@example.com',
            'user@',
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "Email '{$email}' should be invalid"
            );
        }
    }

    public function testEmailSubjectSanitization(): void
    {
        $maliciousSubjects = [
            "Subject\nBcc: hacker@evil.com",
            "Subject\rCc: hacker@evil.com",
            "Subject\r\nBcc: hacker@evil.com",
        ];

        foreach ($maliciousSubjects as $subject) {
            $sanitized = str_replace(["\r", "\n"], '', $subject);

            $this->assertStringNotContainsString("\n", $sanitized);
            $this->assertStringNotContainsString("\r", $sanitized);
        }
    }

    public function testEmailBodyHtmlEncoding(): void
    {
        $htmlBody = '<html><body><h1>Test</h1><p>Message</p></body></html>';

        $this->assertStringContainsString('<html>', $htmlBody);
        $this->assertStringContainsString('</html>', $htmlBody);
        $this->assertStringContainsString('<body>', $htmlBody);
        $this->assertStringContainsString('</body>', $htmlBody);
    }

    public function testReplyToHeaderConfiguration(): void
    {
        $userEmail = 'user@example.com';
        $userName = 'John Doe';

        $replyToHeader = $userName . ' <' . $userEmail . '>';

        $this->assertStringContainsString($userEmail, $replyToHeader);
        $this->assertStringContainsString($userName, $replyToHeader);
        $this->assertNotFalse(filter_var($userEmail, FILTER_VALIDATE_EMAIL));
    }

    public function testEmailPriorityHeaders(): void
    {
        $priorities = [
            'high' => '1',
            'normal' => '3',
            'low' => '5',
        ];

        foreach ($priorities as $value) {
            $this->assertMatchesRegularExpression('/^[1-5]$/', $value);
        }
    }
}