<?php

namespace Tests\Unitaire\Models\User;

use Models\User\UserList;
use PHPUnit\Framework\TestCase;

class UserListEdgeCasesTest extends TestCase
{
    public function testGetRowsHtmlWithMissingPrenom(): void
    {
        $users = [
            ['nom' => 'Doe', 'mail' => 'test@test.com', 'role' => 'client']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString('Doe', $html);
    }

    public function testGetRowsHtmlWithMissingNom(): void
    {
        $users = [
            ['prenom' => 'John', 'mail' => 'test@test.com', 'role' => 'client']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString('John', $html);
    }

    public function testGetRowsHtmlWithMissingMail(): void
    {
        $users = [
            ['prenom' => 'John', 'nom' => 'Doe', 'role' => 'client']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString('John', $html);
        $this->assertStringContainsString('Doe', $html);
    }

    public function testGetRowsHtmlWithMissingRole(): void
    {
        $users = [
            ['prenom' => 'John', 'nom' => 'Doe', 'mail' => 'test@test.com']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString('John', $html);
    }

    public function testGetRowsHtmlWithSpecialCharactersInName(): void
    {
        $users = [
            ['prenom' => 'Jean-François', 'nom' => "O'Brien", 'mail' => 'test@test.com', 'role' => 'étudiant']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString('Jean-François', $html);
        $this->assertStringContainsString("O&#039;Brien", $html);
    }

    public function testConstructorWithAllParameters(): void
    {
        $users = [['prenom' => 'Test', 'nom' => 'User', 'mail' => 'test@test.com', 'role' => 'client']];
        $pagination = '<div>Pagination</div>';
        $headerData = ['title' => 'Test'];

        $userList = new UserList($users, $pagination, $headerData);

        $this->assertStringContainsString('Test', $userList->getRowsHtml());
        $this->assertEquals($pagination, $userList->getPaginationHtml());
        $this->assertEquals($headerData, $userList->getHeaderData());
    }

    public function testConstructorWithOnlyUsers(): void
    {
        $users = [['prenom' => 'Test', 'nom' => 'User', 'mail' => 'test@test.com', 'role' => 'client']];

        $userList = new UserList($users);

        $this->assertNotEmpty($userList->getRowsHtml());
        $this->assertEquals('', $userList->getPaginationHtml());
        $this->assertEquals([], $userList->getHeaderData());
    }

    public function testGetRowsHtmlWithEmptyStringFields(): void
    {
        $users = [
            ['prenom' => '', 'nom' => '', 'mail' => '', 'role' => '']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString('<tr>', $html);
        $this->assertStringContainsString('<td></td>', $html);
    }

    public function testGetRowsHtmlWithVeryLongFields(): void
    {
        $longString = str_repeat('a', 500);
        $users = [
            ['prenom' => $longString, 'nom' => $longString, 'mail' => 'test@test.com', 'role' => 'client']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString($longString, $html);
    }

    public function testGetRowsHtmlWithNumericValues(): void
    {
        $users = [
            ['prenom' => '123', 'nom' => '456', 'mail' => '789@test.com', 'role' => 'client']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString('123', $html);
        $this->assertStringContainsString('456', $html);
    }
}
