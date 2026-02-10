<?php

namespace Integration;

use Models\Database;
use Models\User\User;
use PHPUnit\Framework\TestCase;

class LoginIntegrationTest extends TestCase
{
    /** @var int|null */
    private $testUserId;

    protected function setUp(): void
    {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        if ($this->testUserId) {
            User::deleteAccount($this->testUserId);
        }
        $_SESSION = [];
        parent::tearDown();
    }

    public function testSuccessfulLogin(): void
    {
        // Créer un utilisateur vérifié
        $user = new User();
        $token = bin2hex(random_bytes(32));
        $user->register('Test', 'User', 'test@example.com', 'Pass123', 'etudiant', $token);

        $userData = $user->findByEmail('test@example.com');
        $this->assertNotNull($userData);
        $this->assertArrayHasKey('id', $userData);

        $userId = $userData['id'];
        $this->assertIsInt($userId);
        $this->testUserId = $userId;

        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $this->assertNotFalse($stmt);
        $stmt->bind_param("i", $this->testUserId);
        $stmt->execute();
        $stmt->close();

        // Simuler le login
        $userData = $user->findByEmail('test@example.com');
        $this->assertNotNull($userData);
        $this->assertArrayHasKey('mdp', $userData);

        $password = $userData['mdp'];
        $this->assertIsString($password);

        $loginSuccess = false;
        if (password_verify('Pass123', $password) && $userData['is_verified'] == 1) {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['user_role'] = $userData['role'];
            $loginSuccess = true;
        }

        $this->assertTrue($loginSuccess);
        $this->assertArrayHasKey('user_id', $_SESSION);
        $this->assertEquals($this->testUserId, $_SESSION['user_id']);
        $this->assertEquals('etudiant', $_SESSION['user_role']);
    }

    public function testLoginWithWrongPassword(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));
        $user->register('Test', 'User', 'test2@example.com', 'Pass123', 'etudiant', $token);

        $userData = $user->findByEmail('test2@example.com');
        $this->assertNotNull($userData);
        $this->assertArrayHasKey('id', $userData);

        $userId = $userData['id'];
        $this->assertIsInt($userId);
        $this->testUserId = $userId;

        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $this->assertNotFalse($stmt);
        $stmt->bind_param("i", $this->testUserId);
        $stmt->execute();
        $stmt->close();

        // Tentative de login avec mauvais mot de passe
        $userData = $user->findByEmail('test2@example.com');
        $this->assertNotNull($userData);
        $this->assertArrayHasKey('mdp', $userData);

        $password = $userData['mdp'];
        $this->assertIsString($password);

        $loginSuccess = false;
        if (password_verify('WrongPassword', $password) && $userData['is_verified'] == 1) {
            $_SESSION['user_id'] = $userData['id'];
            $loginSuccess = true;
        }

        $this->assertFalse($loginSuccess);
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    public function testLoginNonExistentUser(): void
    {
        $user = new User();
        $userData = $user->findByEmail('nonexistent@example.com');

        $this->assertNull($userData);
    }

    public function testLoginUnverifiedAccount(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));
        $user->register('Test', 'User', 'unverified@example.com', 'Pass123', 'etudiant', $token);

        $userData = $user->findByEmail('unverified@example.com');
        $this->assertNotNull($userData);
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('mdp', $userData);

        $userId = $userData['id'];
        $this->assertIsInt($userId);
        $this->testUserId = $userId;

        $password = $userData['mdp'];
        $this->assertIsString($password);

        // Tentative de login avec compte non vérifié
        $loginSuccess = false;
        if (password_verify('Pass123', $password) && $userData['is_verified'] == 1) {
            $_SESSION['user_id'] = $userData['id'];
            $loginSuccess = true;
        }

        $this->assertFalse($loginSuccess);
        $this->assertArrayNotHasKey('user_id', $_SESSION);
        $this->assertEquals(0, $userData['is_verified']);
    }

    public function testLogoutFlow(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));
        $user->register('Test', 'User', 'test3@example.com', 'Pass123', 'etudiant', $token);

        $userData = $user->findByEmail('test3@example.com');
        $this->assertNotNull($userData);
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('mdp', $userData);

        $userId = $userData['id'];
        $this->assertIsInt($userId);
        $this->testUserId = $userId;

        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $this->assertNotFalse($stmt);
        $stmt->bind_param("i", $this->testUserId);
        $stmt->execute();
        $stmt->close();

        // Login
        $userData = $user->findByEmail('test3@example.com');
        $this->assertNotNull($userData);
        $this->assertArrayHasKey('mdp', $userData);

        $password = $userData['mdp'];
        $this->assertIsString($password);

        if (password_verify('Pass123', $password) && $userData['is_verified'] == 1) {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['user_role'] = $userData['role'];
        }

        $this->assertArrayHasKey('user_id', $_SESSION);

        // Logout
        $_SESSION = [];

        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    public function testLoginDifferentRoles(): void
    {
        $user = new User();
        $conn = Database::getConnection();

        // Créer et vérifier un étudiant
        $token1 = bin2hex(random_bytes(32));
        $user->register('Etudiant', 'Test', 'etudiant@test.com', 'Pass123', 'etudiant', $token1);
        $etudiantData = $user->findByEmail('etudiant@test.com');
        $this->assertNotNull($etudiantData);
        $this->assertArrayHasKey('id', $etudiantData);

        $etudiantId = $etudiantData['id'];
        $this->assertIsInt($etudiantId);

        $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $this->assertNotFalse($stmt);
        $stmt->bind_param("i", $etudiantId);
        $stmt->execute();
        $stmt->close();

        // Vérifier le rôle de l'étudiant
        $this->assertEquals('etudiant', $etudiantData['role']);

        // Créer et vérifier un responsable
        $token2 = bin2hex(random_bytes(32));
        $user->register('Responsable', 'Test', 'responsable@test.com', 'Pass123', 'responsable', $token2);
        $responsableData = $user->findByEmail('responsable@test.com');
        $this->assertNotNull($responsableData);
        $this->assertArrayHasKey('id', $responsableData);

        $responsableId = $responsableData['id'];
        $this->assertIsInt($responsableId);

        $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $this->assertNotFalse($stmt);
        $stmt->bind_param("i", $responsableId);
        $stmt->execute();
        $stmt->close();

        // Vérifier le rôle du responsable
        $this->assertEquals('responsable', $responsableData['role']);

        // Cleanup
        User::deleteAccount($etudiantId);
        User::deleteAccount($responsableId);
        $this->testUserId = null;
    }
}
