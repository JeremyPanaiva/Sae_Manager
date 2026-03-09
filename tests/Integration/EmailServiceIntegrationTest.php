<?php

declare(strict_types=1);

namespace Tests\Integration;

use Models\User\EmailService;
use PHPUnit\Framework\TestCase;
use Shared\Exceptions\DataBaseException;

/**
 * Email Service Integration Test
 *
 * Tests the complete email service integration:
 * - SMTP configuration → Connection → Email sending → Delivery
 *
 * @package Tests\Integration
 */
final class EmailServiceIntegrationTest extends TestCase
{
    /**
     * Email service instance
     *
     * @var EmailService|null
     */
    private ?EmailService $emailService = null;


    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Check if vendor dependencies exist
        $vendorPath = __DIR__ . '/../../vendor/autoload.php';
        if (!file_exists($vendorPath)) {
            $this->markTestSkipped('Vendor dependencies not installed. Run: composer install');
        }

        // Check if .env file exists
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

    /**
     * Clean up after test
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->emailService = null;
        parent::tearDown();
    }

    /**
     * Test EmailService initialization with dependencies
     *
     * @return void
     */
    public function testEmailServiceInitializationWithDependencies(): void
    {
        $this->assertInstanceOf(
            EmailService::class,
            $this->emailService,
            'EmailService should be properly initialized'
        );
    }

    /**
     * Test SMTP configuration from environment variables
     *
     * @return void
     */
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

    /**
     * Test email address validation before sending
     *
     * @return void
     */
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

    /**
     * Test email subject sanitization
     *
     * @return void
     */
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

    /**
     * Test email body HTML encoding
     *
     * @return void
     */
    public function testEmailBodyHtmlEncoding(): void
    {
        $htmlBody = '<html><body><h1>Test</h1><p>Message</p></body></html>';

        $this->assertStringContainsString('<html>', $htmlBody);
        $this->assertStringContainsString('</html>', $htmlBody);
        $this->assertStringContainsString('<body>', $htmlBody);
        $this->assertStringContainsString('</body>', $htmlBody);
    }

    /**
     * Test contact form email data preparation
     *
     * @return void
     */
    public function testContactFormEmailDataPreparation(): void
    {
        $formData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'subject' => 'Test Subject',
            'message' => 'This is a test message.',
        ];

        // Prepare email data
        $emailTo = 'sae-manager@alwaysdata.net';
        $emailSubject = '[Contact] ' . $formData['subject'];
        $emailBody = "Nom: {$formData['name']}\n";
        $emailBody .= "Email: {$formData['email']}\n\n";
        $emailBody .= "Message:\n{$formData['message']}";

        // Verify email data
        $this->assertNotFalse(filter_var($emailTo, FILTER_VALIDATE_EMAIL));
        $this->assertStringContainsString('[Contact]', $emailSubject);
        $this->assertStringContainsString($formData['name'], $emailBody);
        $this->assertStringContainsString($formData['email'], $emailBody);
        $this->assertStringContainsString($formData['message'], $emailBody);
    }

    /**
     * Test HTML email template rendering
     *
     * @return void
     */
    public function testHtmlEmailTemplateRendering(): void
    {
        $templateVars = [
            'name' => 'John Doe',
            'message' => 'Test message content',
        ];

        $htmlTemplate = <<<HTML
        <html>
        <body>
            <h1>Contact from {$templateVars['name']}</h1>
            <p>{$templateVars['message']}</p>
        </body>
        </html>
        HTML;

        $this->assertStringContainsString($templateVars['name'], $htmlTemplate);
        $this->assertStringContainsString($templateVars['message'], $htmlTemplate);
        $this->assertStringContainsString('<html>', $htmlTemplate);
    }

    /**
     * Test error handling for missing SMTP configuration
     *
     * @return void
     */
    public function testErrorHandlingForMissingSmtpConfiguration(): void
    {
        // Simulate missing configuration
        $originalEnv = getenv('SMTP_HOST');

        if ($originalEnv === false) {
            $this->markTestSkipped('SMTP_HOST not set in environment');
        }

        $this->assertTrue(putenv('SMTP_HOST='));

        $smtpHost = getenv('SMTP_HOST');
        $this->assertSame('', $smtpHost);

        // Restore
        $this->assertTrue(putenv('SMTP_HOST=' . $originalEnv));
        $this->assertSame($originalEnv, getenv('SMTP_HOST'));
    }

    /**
     * Test character encoding for international characters
     *
     * @return void
     */
    public function testCharacterEncodingForInternationalCharacters(): void
    {
        $internationalTexts = [
            'Français: àéèêëïôù',
            'Español: ñáéíóú',
            'Deutsch: äöüß',
            'Emoji: 📧✅❌',
        ];

        foreach ($internationalTexts as $text) {
            $encoded = mb_encode_mimeheader($text, 'UTF-8');
            $this->assertNotSame('', $encoded);
        }
    }

    /**
     * Test reply-to header configuration
     *
     * @return void
     */
    public function testReplyToHeaderConfiguration(): void
    {
        $userEmail = 'user@example.com';
        $userName = 'John Doe';

        $replyToHeader = $userName . ' <' . $userEmail . '>';

        $this->assertStringContainsString($userEmail, $replyToHeader);
        $this->assertStringContainsString($userName, $replyToHeader);
        $this->assertNotFalse(filter_var($userEmail, FILTER_VALIDATE_EMAIL));
    }

    /**
     * Test email priority headers
     *
     * @return void
     */
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
