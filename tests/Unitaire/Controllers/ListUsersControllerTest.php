<?php

namespace Tests\Unit\Controllers\User;

use PHPUnit\Framework\TestCase;
use Controllers\User\ListUsers;

class ListUsersControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_URI'] = '/user/list';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = 'off';
    }

    public function testSupportsUserListPath(): void
    {
        $this->assertTrue(ListUsers::support('/user/list', 'GET'));
    }

    public function testDoesNotSupportOtherPaths(): void
    {
        $this->assertFalse(ListUsers::support('/user/login', 'GET'));
        $this->assertFalse(ListUsers::support('/users', 'GET'));
    }

    public function testDoesNotSupportPostMethod(): void
    {
        $this->assertFalse(ListUsers::support('/user/list', 'POST'));
    }

    public function testPathConstantIsCorrect(): void
    {
        $this->assertEquals('/user/list', ListUsers::PATH);
    }
}