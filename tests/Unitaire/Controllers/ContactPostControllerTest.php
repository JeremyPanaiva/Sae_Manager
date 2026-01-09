<?php

namespace Tests\Unit\Controllers\Legal;

use PHPUnit\Framework\TestCase;
use Controllers\Legal\ContactPost;

class ContactPostControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_URI'] = '/contact';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    public function testSupportsPostMethod(): void
    {
        $this->assertTrue(ContactPost::support('/contact', 'POST'));
    }

    public function testDoesNotSupportGetMethod(): void
    {
        $this->assertFalse(ContactPost::support('/contact', 'GET'));
    }

    public function testPathConstantIsCorrect(): void
    {
        $this->assertEquals('/contact', ContactPost::PATH);
    }

    public function testValidatesEmailFormat(): void
    {
        $validEmail = 'test@example.com';
        $invalidEmail = 'invalid-email';

        $this->assertTrue(filter_var($validEmail, FILTER_VALIDATE_EMAIL) !== false);
        $this->assertFalse(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL) !== false);
    }
}