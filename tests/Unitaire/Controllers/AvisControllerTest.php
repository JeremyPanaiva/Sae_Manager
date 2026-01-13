<?php

namespace Tests\Unit\Controllers\Sae;

use PHPUnit\Framework\TestCase;
use Controllers\Sae\AvisController;

class AvisControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_URI'] = '/sae/avis/add';
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

    public function testPathConstantsAreCorrect(): void
    {
        $this->assertEquals('/sae/avis/add', AvisController::PATH_ADD);
        $this->assertEquals('/sae/avis/delete', AvisController::PATH_DELETE);
        $this->assertEquals('/sae/avis/update', AvisController::PATH_UPDATE);
    }

    public function testSupportsAddPath(): void
    {
        $this->assertTrue(AvisController::support('/sae/avis/add', 'POST'));
    }

    public function testSupportsDeletePath(): void
    {
        $this->assertTrue(AvisController::support('/sae/avis/delete', 'POST'));
    }

    public function testSupportsUpdatePath(): void
    {
        $this->assertTrue(AvisController::support('/sae/avis/update', 'POST'));
    }
}