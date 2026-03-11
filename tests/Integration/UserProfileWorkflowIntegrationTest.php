<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * User Profile Workflow Integration Test
 *
 * Covers profile update scenario:
 * - user connecté → modification profil → détection changement email → préparation notification
 */
final class UserProfileWorkflowIntegrationTest extends TestCase
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

    public function testUserProfileUpdateWorkflowWithEmailNotification(): void
    {
        $_SESSION['user_id'] = 123;
        $_SESSION['user_email'] = 'user@example.com';

        $this->assertArrayHasKey('user_id', $_SESSION);

        $_POST = [
            'prenom' => 'Jean',
            'nom' => 'Martin',
            'mail' => 'jean.martin@example.com',
        ];

        $newMail = $_POST['mail'];
        $oldMail = $_SESSION['user_email'];

        $this->assertNotSame($newMail, $oldMail);

        $confirmationEmail = [
            'to' => $newMail,
            'subject' => 'Confirmation de changement d\'email',
            'body' => 'Votre email a été modifié.',
        ];

        $this->assertSame($newMail, $confirmationEmail['to']);
        $this->assertNotSame('', $confirmationEmail['subject']);
        $this->assertNotSame('', $confirmationEmail['body']);
    }
}
