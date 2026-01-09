<?php

namespace Tests\Models\User;

use Models\User\UserList;
use PHPUnit\Framework\TestCase;

class UserListTest extends TestCase
{
    public function testGetRowsHtmlWithValidUsers(): void
    {
        $users = [
            ['prenom' => 'John', 'nom' => 'Doe', 'mail' => 'john@example.com', 'role' => 'etudiant'],
            ['prenom' => 'Jane', 'nom' => 'Smith', 'mail' => 'jane@example.com', 'role' => 'responsable']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString('John', $html);
        $this->assertStringContainsString('Doe', $html);
        $this->assertStringContainsString('john@example.com', $html);
        $this->assertStringContainsString('Etudiant', $html);
    }

    public function testGetRowsHtmlEscapesHtmlContent(): void
    {
        $users = [
            ['prenom' => '<script>alert("XSS")</script>', 'nom' => 'Test', 'mail' => 'test@test.com', 'role' => 'client']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testGetRowsHtmlWithEmptyArray(): void
    {
        $userList = new UserList([]);
        $html = $userList->getRowsHtml();
        $this->assertEquals('', $html);
    }

    public function testGetPaginationHtml(): void
    {
        $pagination = '<div class="pagination">Page 1</div>';
        $userList = new UserList([], $pagination);
        $this->assertEquals($pagination, $userList->getPaginationHtml());
    }

    public function testGetHeaderData(): void
    {
        $headerData = ['title' => 'Liste des utilisateurs', 'count' => 10];
        $userList = new UserList([], '', $headerData);
        $this->assertEquals($headerData, $userList->getHeaderData());
    }
}