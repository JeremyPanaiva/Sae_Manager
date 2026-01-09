<?php

namespace Tests\Unitaire\Models\User;

use Models\User\UserList;
use PHPUnit\Framework\TestCase;

/**
 * Tests de sécurité (XSS) pour UserList
 */
class UserListSecurityTest extends TestCase
{
    public function testGetRowsHtmlEscapesXssInPrenom(): void
    {
        $users = [
            ['prenom' => '<script>alert("XSS")</script>', 'nom' => 'Test', 'mail' => 'test@test.com', 'role' => 'client']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testGetRowsHtmlEscapesXssInNom(): void
    {
        $users = [
            ['prenom' => 'John', 'nom' => '<img src=x onerror=alert(1)>', 'mail' => 'test@test.com', 'role' => 'etudiant']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }

    public function testGetRowsHtmlEscapesXssInEmail(): void
    {
        $users = [
            ['prenom' => 'John', 'nom' => 'Doe', 'mail' => '"><script>alert(1)</script>', 'role' => 'client']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&quot;', $html);
    }

    public function testGetRowsHtmlEscapesXssInRole(): void
    {
        $users = [
            ['prenom' => 'John', 'nom' => 'Doe', 'mail' => 'test@test.com', 'role' => '<script>alert("role")</script>']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testGetRowsHtmlEscapesHtmlEntities(): void
    {
        $users = [
            ['prenom' => 'Test&Copy', 'nom' => 'User<br>', 'mail' => 'test@test.com', 'role' => 'client']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString('&amp;', $html);
        $this->assertStringContainsString('&lt;', $html);
    }

    public function testGetRowsHtmlWithJavascriptProtocol(): void
    {
        $users = [
            ['prenom' => 'John', 'nom' => 'Doe', 'mail' => 'javascript:alert(1)', 'role' => 'client']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        // Le texte "javascript:" reste dans le HTML car htmlspecialchars() ne l'échappe pas
        $this->assertStringContainsString('javascript:alert(1)', $html);

        // Mais il est dans une cellule de tableau, pas dans un attribut href
        $this->assertStringContainsString('<td>javascript:alert(1)</td>', $html);
    }

    public function testGetRowsHtmlEscapesSqlInjectionAttempt(): void
    {
        $users = [
            ['prenom' => "'; DROP TABLE users; --", 'nom' => 'Hacker', 'mail' => 'hack@test.com', 'role' => 'client']
        ];

        $userList = new UserList($users);
        $html = $userList->getRowsHtml();

        $this->assertStringContainsString('&#039;', $html);
        $this->assertStringContainsString('DROP TABLE users', $html);
    }
}