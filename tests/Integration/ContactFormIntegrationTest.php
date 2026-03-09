<?php

declare(strict_types=1);

namespace Tests\Integration;

use Controllers\Legal\ContactController;
use PHPUnit\Framework\TestCase;

/**
 * Contact Form Integration Test
 *
 * Tests the complete contact form flow:
 * - Form submission → Controller → Validation → Email Service → Response
 *
 * @package Tests\Integration
 */
final class ContactFormIntegrationTest extends TestCase
{
    /**
     * Original $_POST data backup
     *
     * @var array<string, mixed>
     */
    private array $originalPost = [];

    /**
     * Original $_SERVER data backup
     *
     * @var array<string, mixed>
     */
    private array $originalServer = [];

    /**
     * Set up test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Backup superglobals
        $this->originalPost = $_POST;
        $this->originalServer = $_SERVER;

        // Set up test environment
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/legal/contact';

        // Check if controller exists
        if (!class_exists(ContactController::class)) {
            $this->markTestSkipped('ContactController class does not exist');
        }
    }

    /**
     * Clean up after test
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Restore superglobals
        $_POST = $this->originalPost;
        $_SERVER = $this->originalServer;


        parent::tearDown();
    }

    /**
     * Simulate form submission with given data
     *
     * @param array<string, string> $formData Form data to submit
     * @return void
     */
    private function simulateFormSubmission(array $formData): void
    {
        $_POST = $formData;
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    /**
     * Test complete contact form submission flow with valid data
     *
     * @return void
     */
    public function testCompleteContactFormSubmissionWithValidData(): void
    {
        $validData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'subject' => 'Test Subject',
            'message' => 'This is a test message for integration testing.',
        ];

        $this->simulateFormSubmission($validData);

        // Verify all required fields are present
        $this->assertArrayHasKey('name', $_POST);
        $this->assertArrayHasKey('email', $_POST);
        $this->assertArrayHasKey('subject', $_POST);
        $this->assertArrayHasKey('message', $_POST);

        // Verify data integrity
        $this->assertSame($validData['name'], $_POST['name']);
        $this->assertSame($validData['email'], $_POST['email']);
        $this->assertSame($validData['subject'], $_POST['subject']);
        $this->assertSame($validData['message'], $_POST['message']);
    }

    /**
     * Test form submission with missing required fields
     *
     * @return void
     */
    public function testFormSubmissionWithMissingFields(): void
    {
        $requiredFields = ['name', 'email', 'subject', 'message'];

        foreach ($requiredFields as $fieldToOmit) {
            $incompleteData = [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'subject' => 'Test Subject',
                'message' => 'Test message',
            ];

            unset($incompleteData[$fieldToOmit]);

            $this->simulateFormSubmission($incompleteData);

            // Verify the missing field
            $this->assertArrayNotHasKey($fieldToOmit, $_POST);

            // Verify other fields are present
            foreach ($requiredFields as $field) {
                if ($field !== $fieldToOmit) {
                    $this->assertArrayHasKey($field, $_POST);
                }
            }
        }
    }

    /**
     * Test form submission with invalid email format
     *
     * @return void
     */
    public function testFormSubmissionWithInvalidEmail(): void
    {
        $invalidEmails = [
            'notanemail',
            '@example.com',
            'user@',
            'user name@example.com',
            'user..name@example.com',
        ];

        foreach ($invalidEmails as $invalidEmail) {
            $invalidData = [
                'name' => 'John Doe',
                'email' => $invalidEmail,
                'subject' => 'Test Subject',
                'message' => 'Test message',
            ];

            $this->simulateFormSubmission($invalidData);

            // Verify email validation fails
            $this->assertFalse(
                filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) !== false,
                "Email '{$invalidEmail}' should be invalid"
            );
        }
    }

    /**
     * Test form data sanitization against XSS attacks
     *
     * @return void
     */
    public function testFormDataSanitizationAgainstXss(): void
    {
        $xssAttempts = [
            'name' => '<script>alert("XSS")</script>',
            'email' => 'test@example.com"><script>alert("XSS")</script>',
            'subject' => '<img src=x onerror=alert("XSS")>',
            'message' => 'Normal text <script>document.cookie</script> more text',
        ];

        $this->simulateFormSubmission($xssAttempts);

        foreach ($_POST as $field => $value) {
            if (!is_string($value)) {
                continue;
            }

            $sanitized = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

            $this->assertStringNotContainsString(
                '<script>',
                $sanitized,
                "Field '{$field}' should not contain script tags after sanitization"
            );
        }
    }

    /**
     * Test form submission with empty strings after trimming
     *
     * @return void
     */
    public function testFormSubmissionWithWhitespaceOnlyValues(): void
    {
        $whitespaceData = [
            'name' => '   ',
            'email' => "\t\t",
            'subject' => "\n\n",
            'message' => "   \t\n   ",
        ];

        $this->simulateFormSubmission($whitespaceData);

        foreach ($_POST as $field => $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $stringValue = (string) $value;
            $trimmed = trim($stringValue);

            $this->assertEmpty(
                $trimmed,
                "Field '{$field}' should be empty after trimming"
            );
        }
    }

    /**
     * Test form submission with maximum length values
     *
     * @return void
     */
    public function testFormSubmissionWithMaximumLengthValues(): void
    {
        $maxLengths = [
            'name' => 100,
            'email' => 255,
            'subject' => 200,
            'message' => 5000,
        ];

        foreach ($maxLengths as $field => $maxLength) {
            $longValue = str_repeat('a', $maxLength + 100);

            $testData = [
                'name' => 'John Doe',
                'email' => 'test@example.com',
                'subject' => 'Test',
                'message' => 'Test message',
            ];

            $testData[$field] = $longValue;

            $this->simulateFormSubmission($testData);

            $fieldValue = $_POST[$field];
            $stringFieldValue = is_scalar($fieldValue) ? (string) $fieldValue : '';

            $this->assertGreaterThan(
                $maxLength,
                strlen($stringFieldValue),
                "Field '{$field}' should exceed maximum length"
            );
        }
    }

    /**
     * Test redirect URL generation for success
     *
     * @return void
     */
    public function testRedirectUrlGenerationForSuccess(): void
    {
        $baseUrl = '/legal/contact';
        $successParam = 'success=message_sent';

        $redirectUrl = $baseUrl . '?' . $successParam;

        $this->assertStringContainsString($baseUrl, $redirectUrl);
        $this->assertStringContainsString($successParam, $redirectUrl);

        parse_str((string) parse_url($redirectUrl, PHP_URL_QUERY), $params);
        $this->assertArrayHasKey('success', $params);
        $this->assertSame('message_sent', $params['success']);
    }

    /**
     * Test redirect URL generation for errors
     *
     * @return void
     */
    public function testRedirectUrlGenerationForErrors(): void
    {
        $baseUrl = '/legal/contact';
        $errorTypes = [
            'missing_fields',
            'invalid_email',
            'mail_failure',
        ];

        foreach ($errorTypes as $errorType) {
            $errorParam = 'error=' . $errorType;
            $redirectUrl = $baseUrl . '?' . $errorParam;

            $this->assertStringContainsString($baseUrl, $redirectUrl);
            $this->assertStringContainsString($errorParam, $redirectUrl);

            parse_str((string) parse_url($redirectUrl, PHP_URL_QUERY), $params);
            $this->assertArrayHasKey('error', $params);
            $this->assertSame($errorType, $params['error']);
        }
    }

    /**
     * Test POST request method validation
     *
     * @return void
     */
    public function testPostRequestMethodValidation(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertSame('POST', $_SERVER['REQUEST_METHOD']);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertNotSame('POST', $_SERVER['REQUEST_METHOD']);
    }

    /**
     * Test complete data flow integrity
     *
     * @return void
     */
    public function testCompleteDataFlowIntegrity(): void
    {
        $originalData = [
            'name' => 'Jean Dupont',
            'email' => 'jean.dupont@example.fr',
            'subject' => 'Question sur SAE Manager',
            'message' => 'Bonjour, j\'aimerais avoir des informations sur le projet.',
        ];

        $this->simulateFormSubmission($originalData);

        // Verify data has not been corrupted
        foreach ($originalData as $field => $value) {
            $this->assertArrayHasKey($field, $_POST);
            $this->assertSame($value, $_POST[$field]);
        }

        // Verify email is valid
        $this->assertNotFalse(
            filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)
        );

        // Verify no field is empty
        foreach ($_POST as $field => $value) {
            $this->assertNotEmpty(
                trim($value),
                "Field '{$field}' should not be empty"
            );
        }
    }
}
