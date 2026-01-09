<?php

namespace Tests\Unitaire\Models\User;

use Models\User\UserDTO;
use PHPUnit\Framework\TestCase;

class UserDTOGettersTest extends TestCase
{
    public function testGetUsername(): void
    {
        $dto = new UserDTO('john.doe', 'secret');
        $this->assertEquals('john.doe', $dto->getUsername());
    }

    public function testGetPassword(): void
    {
        $dto = new UserDTO('admin', 'P@ssw0rd!');
        $this->assertEquals('P@ssw0rd!', $dto->getPassword());
    }

    public function testGetUsernameWithEmail(): void
    {
        $dto = new UserDTO('user@example.com', 'pass');
        $this->assertEquals('user@example.com', $dto->getUsername());
    }

    public function testGetPasswordWithComplexString(): void
    {
        $dto = new UserDTO('user', 'aB3$%^&*()_+{}[]');
        $this->assertEquals('aB3$%^&*()_+{}[]', $dto->getPassword());
    }

    public function testGettersReturnSameValuesAsConstructor(): void
    {
        $username = 'testUser123';
        $password = 'testPass456';

        $dto = new UserDTO($username, $password);

        $this->assertSame($username, $dto->getUsername());
        $this->assertSame($password, $dto->getPassword());
    }
}