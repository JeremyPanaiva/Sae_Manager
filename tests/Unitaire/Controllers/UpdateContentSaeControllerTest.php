<?php

namespace Tests\Unit\Controllers\Sae;

use PHPUnit\Framework\TestCase;
use Controllers\Sae\UpdateContentSaeController;

class UpdateContentSaeControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_URI'] = '/update_sae';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['REQUEST_METHOD'] = 'POST';

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

    public function testSupportsPostMethod(): void
    {
        $this->assertTrue(UpdateContentSaeController::support('/update_sae', 'POST'));
    }

    public function testDoesNotSupportGetMethod(): void
    {
        $this->assertFalse(UpdateContentSaeController::support('/update_sae', 'GET'));
    }

    public function testPathConstantIsCorrect(): void
    {
        $this->assertEquals('/update_sae', UpdateContentSaeController::PATH);
    }
}