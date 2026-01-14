<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Models\User\User;
use Models\Database;
use Shared\Exceptions\EmailAlreadyExistsException;

class RegistrationIntegrationTest extends TestCase
{
    /** @var list<int> */
    private array $testUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->testUserIds as $id) {
            User::deleteAccount($id);
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
        $this->assertNotNull($userData);
        $this->assertArrayHasKey('id', $userData);

        $userId = $userData['id'];
        $this->assertIsInt($userId);
        $this->testUserIds[] = $userId;

        $this->assertEquals('Doe', $userData['nom']);
        $this->assertEquals('John', $userData['prenom']);
        $this->assertEquals(0, $userData['is_verified']);
    }

    public function testPasswordIsHashed(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));
        $plainPassword = 'MyPassword123';

        $user->register('Test', 'User', 'hashed@test.com', $plainPassword, 'etudiant', $token);
        $userData = $user->findByEmail('hashed@test.com');
        $this->assertNotNull($userData);
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('mdp', $userData);

        $userId = $userData['id'];
        $this->assertIsInt($userId);
        $this->testUserIds[] = $userId;

        $password = $userData['mdp'];
        $this->assertIsString($password);
        $this->assertNotEquals($plainPassword, $password);
        $this->assertTrue(password_verify($plainPassword, $password));
    }

    public function testDuplicateEmailThrowsException(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));

        $user->register('First', 'User', 'duplicate@example.com', 'Pass123', 'etudiant', $token);
        $userData = $user->findByEmail('duplicate@example.com');
        $this->assertNotNull($userData);
        $this->assertArrayHasKey('id', $userData);

        $userId = $userData['id'];
        $this->assertIsInt($userId);
        $this->testUserIds[] = $userId;

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
        $this->assertNotNull($userData);
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('mdp', $userData);

        $userId = $userData['id'];
        $this->assertIsInt($userId);
        $this->testUserIds[] = $userId;

        // Compte non vérifié = pas de login
        $this->assertEquals(0, $userData['is_verified']);

        $password = $userData['mdp'];
        $this->assertIsString($password);
        $canLogin = password_verify('Pass123', $password) && $userData['is_verified'] == 1;
        $this->assertFalse($canLogin);

        // Vérification
        $verifyResult = $user->verifyAccount($token);
        $this->assertTrue($verifyResult);

        // Maintenant peut se connecter
        $verifiedData = $user->findByEmail('verify@example.com');
        $this->assertNotNull($verifiedData);
        $this->assertArrayHasKey('mdp', $verifiedData);
        $this->assertEquals(1, $verifiedData['is_verified']);

        $verifiedPassword = $verifiedData['mdp'];
        $this->assertIsString($verifiedPassword);
        $canLoginNow = password_verify('Pass123', $verifiedPassword) && $verifiedData['is_verified'] == 1;
        $this->assertTrue($canLoginNow);
    }

    public function testInvalidTokenDoesNotVerify(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));

        $user->register('Test', 'User', 'invalidtoken@test.com', 'Pass123', 'etudiant', $token);
        $userData = $user->findByEmail('invalidtoken@test. com');
        $this->assertNotNull($userData);
        $this->assertArrayHasKey('id', $userData);

        $userId = $userData['id'];
        $this->assertIsInt($userId);
        $this->testUserIds[] = $userId;

        $result = $user->verifyAccount('wrongtoken123');
        $this->assertFalse($result);

        $userData = $user->findByEmail('invalidtoken@test. com');
        $this->assertNotNull($userData);
        $this->assertEquals(0, $userData['is_verified']);
    }
}
