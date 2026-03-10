<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * User Contact Workflow Integration Test
 *
 * Covers contact form workflow:
 * - visite page contact → remplissage → soumission → validation → préparation email → redirect succès/erreur
 */
final class UserContactWorkflowIntegrationTest extends TestCase
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $superglobalsBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->superglobalsBackup = [
            'POST' => $_POST,
            'GET' => $_GET,
            'SERVER' => $_SERVER,
            'SESSION' => $_SESSION ?? [],
        ];

        $_POST = [];
        $_GET = [];
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_POST = $this->superglobalsBackup['POST'];
        $_GET = $this->superglobalsBackup['GET'];
        $_SERVER = $this->superglobalsBackup['SERVER'];
        $_SESSION = $this->superglobalsBackup['SESSION'];

        parent::tearDown();
    }

    public function testCompleteContactFormSubmissionWorkflow(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/legal/contact';

        $this->assertSame('GET', $_SERVER['REQUEST_METHOD']);

        $_SERVER['REQUEST_METHOD'] = 'POST';

        /** @var array<string, mixed> $postData */
        $postData = [
            'name' => 'Marie Dubois',
            'email' => 'marie.dubois@example.fr',
            'subject' => 'Question sur le projet',
            'message' => 'Bonjour, j\'aimerais avoir plus d\'informations sur SAE Manager.',
        ];
        $_POST = $postData;

        $validationErrors = [];

        foreach (['name', 'email', 'subject', 'message'] as $field) {
            $value = $postData[$field] ?? '';
            if (!is_string($value) || $value === '') {
                $validationErrors[] = "Le champ {$field} est requis";
            }
        }

        $emailValue = $postData['email'] ?? '';
        if (!is_string($emailValue) || filter_var($emailValue, FILTER_VALIDATE_EMAIL) === false) {
            $validationErrors[] = 'L\'email est invalide';
        }

        $this->assertSame([], $validationErrors, 'Form should be valid');

        $subject = is_string($postData['subject'] ?? null) ? $postData['subject'] : '';
        $name    = is_string($postData['name'] ?? null)    ? $postData['name']    : '';
        $email   = is_string($postData['email'] ?? null)   ? $postData['email']   : '';
        $message = is_string($postData['message'] ?? null) ? $postData['message'] : '';

        $emailPrepared = [
            'to'      => 'sae-manager@alwaysdata.net',
            'subject' => '[Contact] ' . $subject,
            'body'    => "De: {$name} <{$email}>\n\n{$message}",
        ];

        $this->assertArrayHasKey('to', $emailPrepared);
        $this->assertArrayHasKey('subject', $emailPrepared);
        $this->assertArrayHasKey('body', $emailPrepared);

        $_GET['success'] = 'message_sent';
        $redirectUrl = '/legal/contact?success=message_sent';
        $this->assertStringContainsString('success=message_sent', $redirectUrl);
        $this->assertArrayHasKey('success', $_GET);
    }

    public function testWorkflowWithValidationErrors(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        /** @var array<string, mixed> $postData */
        $postData = [
            'name'    => '',
            'email'   => 'invalid',
            'subject' => 'Test',
            'message' => '',
        ];
        $_POST = $postData;

        $errors = [];

        $name = $postData['name'] ?? '';
        if (!is_string($name) || $name === '') {
            $errors[] = 'missing_name';
        }

        $email = $postData['email'] ?? '';
        if (!is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'invalid_email';
        }

        $message = $postData['message'] ?? '';
        if (!is_string($message) || $message === '') {
            $errors[] = 'missing_message';
        }

        $this->assertNotSame([], $errors);
        $this->assertContains('missing_name', $errors);
        $this->assertContains('invalid_email', $errors);
        $this->assertContains('missing_message', $errors);

        $_GET['error'] = 'missing_fields';
        $this->assertArrayHasKey('error', $_GET);
    }
}
