<?php

namespace Tests\Unit\Controllers\Sae;

use PHPUnit\Framework\TestCase;
use Controllers\Sae\UpdateSaeDateController;

class UpdateSaeDateControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_URI'] = '/sae/update_date';
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
        parent:: tearDown();
    }

    public function testSupportsPostMethod(): void
    {
        $this->assertTrue(UpdateSaeDateController::support('/sae/update_date', 'POST'));
    }

    public function testDoesNotSupportGetMethod(): void
    {
        $this->assertFalse(UpdateSaeDateController::support('/sae/update_date', 'GET'));
    }

    public function testPathConstantIsCorrect(): void
    {
        $this->assertEquals('/sae/update_date', UpdateSaeDateController::PATH);
    }
}
