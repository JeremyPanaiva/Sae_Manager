<?php

namespace Tests\Unit\Controllers\Sae;

use PHPUnit\Framework\TestCase;
use Controllers\Sae\SaeController;

class SaeControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_URI'] = '/sae';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = 'off';

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        parent::tearDown();
    }

    public function testPathConstantIsCorrect(): void
    {
        $this->assertEquals('/sae', SaeController:: PATH);
    }

    public function testRequiresAuthentication(): void
    {
        unset($_SESSION['user']);

        $this->assertArrayNotHasKey('user', $_SESSION);
    }
}
