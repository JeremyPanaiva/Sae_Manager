<?php

namespace Tests\Unitaire\Models\User;

use Models\User\UserList;
use PHPUnit\Framework\TestCase;

class UserListHeaderDataTest extends TestCase
{
    public function testGetHeaderDataWithSimpleArray(): void
    {
        $headerData = ['title' => 'Liste des utilisateurs', 'count' => 10];
        $userList = new UserList([], '', $headerData);

        $this->assertEquals($headerData, $userList->getHeaderData());
    }

    public function testGetHeaderDataWithTitle(): void
    {
        $headerData = ['title' => 'Liste des utilisateurs'];
        $userList = new UserList([], '', $headerData);

        $this->assertEquals('Liste des utilisateurs', $userList->getHeaderData()['title']);
    }

    public function testGetHeaderDataWithCount(): void
    {
        $headerData = ['count' => 25];
        $userList = new UserList([], '', $headerData);

        $this->assertEquals(25, $userList->getHeaderData()['count']);
    }

    public function testGetHeaderDataWithEmptyArray(): void
    {
        $userList = new UserList([]);

        $this->assertEquals([], $userList->getHeaderData());
        $this->assertIsArray($userList->getHeaderData());
        $this->assertEmpty($userList->getHeaderData());
    }

    public function testGetHeaderDataWithComplexData(): void
    {
        $headerData = [
            'title' => 'Utilisateurs',
            'count' => 25,
            'filters' => ['role' => 'etudiant'],
            'sort' => 'nom'
        ];
        $userList = new UserList([], '', $headerData);

        $result = $userList->getHeaderData();

        $this->assertEquals($headerData, $result);
        $this->assertArrayHasKey('filters', $result);
        $this->assertArrayHasKey('sort', $result);
    }

    public function testGetHeaderDataWithNestedArrays(): void
    {
        $headerData = [
            'filters' => [
                'role' => 'etudiant',
                'active' => true
            ],
            'pagination' => [
                'page' => 1,
                'perPage' => 20
            ]
        ];
        $userList = new UserList([], '', $headerData);

        $result = $userList->getHeaderData();

        $this->assertIsArray($result['filters']);
        $this->assertIsArray($result['pagination']);
        $this->assertEquals('etudiant', $result['filters']['role']);
    }

    public function testGetHeaderDataPreservesDataTypes(): void
    {
        $headerData = [
            'string' => 'test',
            'int' => 42,
            'bool' => true,
            'float' => 3.14,
            'null' => null
        ];
        $userList = new UserList([], '', $headerData);

        $result = $userList->getHeaderData();

        $this->assertIsString($result['string']);
        $this->assertIsInt($result['int']);
        $this->assertIsBool($result['bool']);
        $this->assertIsFloat($result['float']);
        $this->assertNull($result['null']);
    }
}