<?php

namespace Tests\Unitaire\Models\User;

use Models\User\UserList;
use PHPUnit\Framework\TestCase;

class UserListHtmlGenerationTest extends TestCase
{
    public function testGetRowsHtmlWithSingleUser(): void
    {
        $users = [
            ['prenom' => 'John', 'nom' => 'Doe', 'mail' => 'john@example.com', 'role' => 'etudiant']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString('John', $html);
        $this->assertStringContainsString('Doe', $html);
        $this->assertStringContainsString('john@example.com', $html);
        $this->assertStringContainsString('Etudiant', $html);
    }

    public function testGetRowsHtmlWithMultipleUsers(): void
    {
        $users = [
            ['prenom' => 'User1', 'nom' => 'Test1', 'mail' => 'user1@test.com', 'role' => 'etudiant'],
            ['prenom' => 'User2', 'nom' => 'Test2', 'mail' => 'user2@test.com', 'role' => 'responsable'],
            ['prenom' => 'User3', 'nom' => 'Test3', 'mail' => 'user3@test.com', 'role' => 'client']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertEquals(3, substr_count($html, '<tr>'));
        $this->assertStringContainsString('User1', $html);
        $this->assertStringContainsString('User2', $html);
        $this->assertStringContainsString('User3', $html);
    }

    public function testGetRowsHtmlContainsTableRows(): void
    {
        $users = [
            ['prenom' => 'John', 'nom' => 'Doe', 'mail' => 'john@test.com', 'role' => 'client']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString('<tr>', $html);
        $this->assertStringContainsString('</tr>', $html);
        $this->assertStringContainsString('<td>', $html);
        $this->assertStringContainsString('</td>', $html);
    }

    public function testGetRowsHtmlWithEmptyArray(): void
    {
        $userList = new UserList([]);
        $html = $userList->getRowsHtml();

        $this->assertEquals('', $html);
        $this->assertEmpty($html);
    }

    public function testGetRowsHtmlCapitalizesRole(): void
    {
        $users = [
            ['prenom' => 'John', 'nom' => 'Doe', 'mail' => 'john@test.com', 'role' => 'etudiant']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString('Etudiant', $html);
        $this->assertStringNotContainsString('etudiant', $html);
    }

    public function testGetRowsHtmlWithAllRoles(): void
    {
        $users = [
            ['prenom' => 'A', 'nom' => 'A', 'mail' => 'a@test.com', 'role' => 'etudiant'],
            ['prenom' => 'B', 'nom' => 'B', 'mail' => 'b@test.com', 'role' => 'responsable'],
            ['prenom' => 'C', 'nom' => 'C', 'mail' => 'c@test.com', 'role' => 'client']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString('Etudiant', $html);
        $this->assertStringContainsString('Responsable', $html);
        $this->assertStringContainsString('Client', $html);
    }
}