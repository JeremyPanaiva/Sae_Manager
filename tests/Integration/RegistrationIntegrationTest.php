<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Models\User\User;
use Models\Database;
use Shared\Exceptions\EmailAlreadyExistsException;

class RegistrationIntegrationTest extends TestCase
{
    private $testUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    protected function tearDown(): void
    {
        foreach ($this->testUserIds as $id) {
            if ($id !== null) {
                User::deleteAccount($id);
            }
        }
        $_SESSION = [];
        parent::tearDown();
    }

    public function testSuccessfulRegistration(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));

        $user->register('John', 'Doe', 'newuser@example.com', 'Pass123', 'etudiant', $token);

        $userData = $user->findByEmail('newuser@example.com');
        $this->testUserIds[] = $userData['id'];

        $this->assertNotNull($userData);
        $this->assertEquals('Doe', $userData['nom']);
        $this->assertEquals('John', $userData['prenom']);
        $this->assertEquals(0, $userData['is_verified']);
    }

    public function testPasswordIsHashed(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));
        $plainPassword = 'MyPassword123';

        $user->register('Test', 'User', 'hashed@test. com', $plainPassword, 'etudiant', $token);
        $userData = $user->findByEmail('hashed@test. com');
        $this->testUserIds[] = $userData['id'];

        $this->assertNotEquals($plainPassword, $userData['mdp']);
        $this->assertTrue(password_verify($plainPassword, $userData['mdp']));
    }

    public function testDuplicateEmailThrowsException(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));

        $user->register('First', 'User', 'duplicate@example. com', 'Pass123', 'etudiant', $token);
        $userData = $user->findByEmail('duplicate@example. com');
        $this->testUserIds[] = $userData['id'];

        $this->expectException(EmailAlreadyExistsException::class);
        $user->emailExists('duplicate@example. com');
    }

    public function testCompleteVerificationFlow(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));

        // Inscription
        $user->register('Test', 'User', 'verify@example.com', 'Pass123', 'etudiant', $token);
        $userData = $user->findByEmail('verify@example.com');
        $this->testUserIds[] = $userData['id'];

        // Compte non vérifié = pas de login
        $this->assertEquals(0, $userData['is_verified']);
        $canLogin = $userData && password_verify('Pass123', $userData['mdp']) && $userData['is_verified'] == 1;
        $this->assertFalse($canLogin);

        // Vérification
        $verifyResult = $user->verifyAccount($token);
        $this->assertTrue($verifyResult);

        // Maintenant peut se connecter
        $verifiedData = $user->findByEmail('verify@example.com');
        $this->assertEquals(1, $verifiedData['is_verified']);

        $canLoginNow = $verifiedData && password_verify('Pass123', $verifiedData['mdp']) && $verifiedData['is_verified'] == 1;
        $this->assertTrue($canLoginNow);
    }

    public function testInvalidTokenDoesNotVerify(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));

        $user->register('Test', 'User', 'invalidtoken@test. com', 'Pass123', 'etudiant', $token);
        $userData = $user->findByEmail('invalidtoken@test.com');
        $this->testUserIds[] = $userData['id'];

        $result = $user->verifyAccount('wrongtoken123');
        $this->assertFalse($result);

        $userData = $user->findByEmail('invalidtoken@test.com');
        $this->assertEquals(0, $userData['is_verified']);
    }
}