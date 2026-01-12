<?php

namespace Tests\Unit\Controllers\Home;

use PHPUnit\Framework\TestCase;
use Controllers\Home\HomeController;

class HomeControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = 'off';
    }

    public function testSupportsRootPath(): void
    {
        $this->assertTrue(HomeController::support('/', 'GET'));
    }

    public function testDoesNotSupportOtherPaths(): void
    {
        $this->assertFalse(HomeController::support('/home', 'GET'));
        $this->assertFalse(HomeController::support('/login', 'GET'));
    }

    public function testDoesNotSupportPostMethod(): void
    {
        $this->assertFalse(HomeController::support('/', 'POST'));
    }

    public function testHomePageRendersSuccessfully(): void
    {
        $controller = new HomeController();
        ob_start();
        $controller->control();
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }
}