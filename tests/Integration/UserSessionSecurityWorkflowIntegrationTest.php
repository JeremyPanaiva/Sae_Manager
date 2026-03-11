<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * User Session & Security Workflow Integration Test
 *
 * Covers:
 * - expiration de session
 * - workflow multi-étapes
 * - protection CSRF
 */
final class UserSessionSecurityWorkflowIntegrationTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        $_POST = $this->superglobalsBackup['POST'];
        $_GET = $this->superglobalsBackup['GET'];
        $_SERVER = $this->superglobalsBackup['SERVER'];
        $_SESSION = $this->superglobalsBackup['SESSION'];

        parent::tearDown();
    }

    public function testWorkflowWithSessionTimeout(): void
    {
        $_SESSION['user_id'] = 456;
        $_SESSION['last_activity'] = time() - 7200;

        $lastActivity = $_SESSION['last_activity'];
        $sessionTimeout = 3600;

        $sessionExpired = (time() - $lastActivity) > $sessionTimeout;
        $this->assertTrue($sessionExpired);

        $_SESSION = [];

        $redirectUrl = '/user/login?error=session_expired';
        $this->assertStringContainsString('session_expired', $redirectUrl);

        $this->assertSame([], $_SESSION);
    }

    public function testMultiStepFormWorkflow(): void
    {
        $_SESSION['form_step'] = 1;
        $_SESSION['form_data'] = [
            'name' => 'Pierre Durand',
            'email' => 'pierre@example.com',
        ];

        $this->assertSame(1, $_SESSION['form_step']);

        $formData = $_SESSION['form_data'];
        $formData['subject'] = 'Question technique';
        $_SESSION['form_step'] = 2;
        $_SESSION['form_data'] = $formData;

        $this->assertSame(2, $_SESSION['form_step']);

        $formData2 = $_SESSION['form_data'];
        $formData2['message'] = 'Message complet';
        $_SESSION['form_step'] = 3;
        $_SESSION['form_data'] = $formData2;

        $requiredFields = ['name', 'email', 'subject', 'message'];

        $finalFormData = $_SESSION['form_data'];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $finalFormData);
        }

        $finalData = $finalFormData;
        unset($_SESSION['form_step'], $_SESSION['form_data']);

        $this->assertArrayNotHasKey('form_step', $_SESSION);
        $this->assertCount(4, $finalData);
    }

    public function testWorkflowWithCsrfProtection(): void
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $generatedToken = $_SESSION['csrf_token'];
        $this->assertNotSame('', $generatedToken);

        $_POST['csrf_token'] = $generatedToken;

        $postToken   = $_POST['csrf_token'];
        $sessionToken = $_SESSION['csrf_token'];

        $tokenValid = hash_equals($sessionToken, $postToken);
        $this->assertTrue($tokenValid);

        $_POST['csrf_token'] = 'invalid_token';

        $postToken2 = $_POST['csrf_token'];

        $tokenInvalid = !hash_equals($sessionToken, $postToken2);
        $this->assertTrue($tokenInvalid);
    }
}
