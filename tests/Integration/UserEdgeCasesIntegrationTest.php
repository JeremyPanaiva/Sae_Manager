<?php

namespace Integration;

use Models\User\User;
use PHPUnit\Framework\TestCase;
use Shared\Exceptions\DataBaseException;
use Shared\Exceptions\EmailAlreadyExistsException;

class UserEdgeCasesIntegrationTest extends TestCase
{
    private $testUserIds = [];

    protected function tearDown(): void
    {
        foreach ($this->testUserIds as $id) {
            if ($id !== null) {
                User::deleteAccount($id);
            }
        }
        parent::tearDown();
    }

    public function testDuplicateEmailThrowsException(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));

        $user->register('Test', 'User', 'exists@test.com', 'Pass123', 'etudiant', $token);
        $userData = $user->findByEmail('exists@test.com');
        $this->testUserIds[] = $userData['id'];

        $this->expectException(EmailAlreadyExistsException::class);
        $user->emailExists('exists@test.com');
    }

    public function testInvalidTokenDoesNotVerify(): void
    {
        $user = new User();
        $result = $user->verifyAccount('invalidtoken123');

        $this->assertFalse($result);
    }

    public function testNonExistentUserReturnsNull(): void
    {
        $user = new User();

        $this->assertNull(User::getById(999999));
        $this->assertNull($user->findByEmail('notexist@test.com'));
    }

    public function testPasswordIsHashedNotPlainText(): void
    {
        $user = new User();
        $token = bin2hex(random_bytes(32));
        $plainPassword = 'MyPassword123';

        $user->register('Test', 'User', 'hashed@test.com', $plainPassword, 'etudiant', $token);
        $userData = $user->findByEmail('hashed@test.com');
        $this->testUserIds[] = $userData['id'];

        $this->assertNotEquals($plainPassword, $userData['mdp']);
        $this->assertTrue(password_verify($plainPassword, $userData['mdp']));
    }
}