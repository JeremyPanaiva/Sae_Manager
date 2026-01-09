<?php

namespace Tests\Unitaire\Models\User;

use Models\User\UserDTO;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour UserDTO
 */
class UserDTOTest extends TestCase
{
    public function testConstructorAndGetUsername(): void
    {
        $dto = new UserDTO('testuser', 'password123');
        $this->assertEquals('testuser', $dto->getUsername());
    }

    public function testConstructorAndGetPassword(): void
    {
        $dto = new UserDTO('testuser', 'password123');
        $this->assertEquals('password123', $dto->getPassword());
    }

    public function testWithEmptyStrings(): void
    {
        $dto = new UserDTO('', '');
        $this->assertEquals('', $dto->getUsername());
        $this->assertEquals('', $dto->getPassword());
    }
}