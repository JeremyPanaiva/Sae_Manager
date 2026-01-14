<?php

namespace Tests\Unitaire\Models\User;

use Models\User\UserDTO;
use PHPUnit\Framework\TestCase;

class UserDTOConstructorTest extends TestCase
{
    public function testConstructorInitializesUsernameAndPassword(): void
    {
        $dto = new UserDTO('testuser', 'password123');

        $this->assertEquals('testuser', $dto->getUsername());
        $this->assertEquals('password123', $dto->getPassword());
    }

    public function testConstructorWithEmptyUsername(): void
    {
        $dto = new UserDTO('', 'password');
        $this->assertEquals('', $dto->getUsername());
        $this->assertEquals('password', $dto->getPassword());
    }

    public function testConstructorWithEmptyPassword(): void
    {
        $dto = new UserDTO('user', '');
        $this->assertEquals('user', $dto->getUsername());
        $this->assertEquals('', $dto->getPassword());
    }

    public function testConstructorWithBothEmpty(): void
    {
        $dto = new UserDTO('', '');
        $this->assertEquals('', $dto->getUsername());
        $this->assertEquals('', $dto->getPassword());
    }

    public function testConstructorWithSpecialCharacters(): void
    {
        $dto = new UserDTO('user@domain.com', 'p@$$w0rd!#');
        $this->assertEquals('user@domain.com', $dto->getUsername());
        $this->assertEquals('p@$$w0rd!#', $dto->getPassword());
    }
}
