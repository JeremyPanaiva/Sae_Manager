<?php

namespace Tests\Unitaire\Models\User;

use Models\User\UserDTO;
use PHPUnit\Framework\TestCase;

class UserDTOEdgeCasesTest extends TestCase
{
    public function testWithUnicodeCharacters(): void
    {
        $dto = new UserDTO('utilisateur', 'motdepasse');
        $this->assertEquals('utilisateur', $dto->getUsername());
        $this->assertEquals('motdepasse', $dto->getPassword());
    }

    public function testWithLongUsername(): void
    {
        $longUsername = str_repeat('a', 500);
        $dto = new UserDTO($longUsername, 'password');

        $this->assertEquals($longUsername, $dto->getUsername());
        $this->assertEquals(500, strlen($dto->getUsername()));
    }

    public function testWithLongPassword(): void
    {
        $longPassword = str_repeat('b', 500);
        $dto = new UserDTO('username', $longPassword);

        $this->assertEquals($longPassword, $dto->getPassword());
        $this->assertEquals(500, strlen($dto->getPassword()));
    }

    public function testMultipleInstancesAreIndependent(): void
    {
        $dto1 = new UserDTO('user1', 'pass1');
        $dto2 = new UserDTO('user2', 'pass2');

        $this->assertEquals('user1', $dto1->getUsername());
        $this->assertEquals('user2', $dto2->getUsername());
        $this->assertNotEquals($dto1->getUsername(), $dto2->getUsername());
        $this->assertNotEquals($dto1->getPassword(), $dto2->getPassword());
    }

    public function testWithWhitespaceInCredentials(): void
    {
        $dto = new UserDTO('user name', 'pass word');
        $this->assertEquals('user name', $dto->getUsername());
        $this->assertEquals('pass word', $dto->getPassword());
    }

    public function testWithNumericStrings(): void
    {
        $dto = new UserDTO('12345', '67890');
        $this->assertEquals('12345', $dto->getUsername());
        $this->assertEquals('67890', $dto->getPassword());
    }

    public function testWithNewlineCharacters(): void
    {
        $dto = new UserDTO("user\nname", "pass\nword");
        $this->assertStringContainsString("\n", $dto->getUsername());
        $this->assertStringContainsString("\n", $dto->getPassword());
    }
}