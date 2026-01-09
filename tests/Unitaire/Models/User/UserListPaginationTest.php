<?php

namespace Tests\Unitaire\Models\User;

use Models\User\UserList;
use PHPUnit\Framework\TestCase;

class UserListPaginationTest extends TestCase
{
    public function testGetPaginationHtmlWithSimpleString(): void
    {
        $pagination = '<div class="pagination">Page 1</div>';
        $userList = new UserList([], $pagination);

        $this->assertEquals($pagination, $userList->getPaginationHtml());
    }

    public function testGetPaginationHtmlWithEmptyString(): void
    {
        $userList = new UserList([]);

        $this->assertEquals('', $userList->getPaginationHtml());
        $this->assertEmpty($userList->getPaginationHtml());
    }

    public function testGetPaginationHtmlWithComplexHtml(): void
    {
        $pagination = '<nav><ul><li><a href="?page=1">1</a></li><li><a href="?page=2">2</a></li></ul></nav>';
        $userList = new UserList([], $pagination);

        $this->assertEquals($pagination, $userList->getPaginationHtml());
    }

    public function testGetPaginationHtmlWithMultiplePages(): void
    {
        $pagination = '<div class="pagination">';
        $pagination .= '<a href="?page=1">1</a>';
        $pagination .= '<a href="?page=2" class="active">2</a>';
        $pagination .= '<a href="? page=3">3</a>';
        $pagination .= '</div>';

        $userList = new UserList([], $pagination);

        $this->assertStringContainsString('page=1', $userList->getPaginationHtml());
        $this->assertStringContainsString('page=2', $userList->getPaginationHtml());
        $this->assertStringContainsString('active', $userList->getPaginationHtml());
    }

    public function testGetPaginationHtmlPreservesHtmlStructure(): void
    {
        $pagination = '<nav aria-label="Page navigation"><ul class="pagination"></ul></nav>';
        $userList = new UserList([], $pagination);

        $result = $userList->getPaginationHtml();

        $this->assertStringContainsString('<nav', $result);
        $this->assertStringContainsString('aria-label', $result);
        $this->assertStringContainsString('</nav>', $result);
    }
}