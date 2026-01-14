<?php

namespace Tests\Unit\Controllers\User;

use PHPUnit\Framework\TestCase;
use Controllers\User\Login;

class LoginControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_URI'] = '/login';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = 'off';
    }

    public function testDisplaysRegistrationSuccessMessage(): void
    {
        $_GET['success'] = 'registered';

        $controller = new Login();
        ob_start();
        $controller->control();
        $output = ob_get_clean();

        $this->assertStringContainsString('Inscription réussie', (string) $output);
    }

    public function testDisplaysPasswordResetSuccessMessage(): void
    {
        $_GET['success'] = 'password_reset';

        $controller = new Login();
        ob_start();
        $controller->control();
        $output = ob_get_clean();

        $this->assertStringContainsString('mot de passe a été réinitialisé', (string) $output);
    }

    public function testDisplaysInvalidTokenError(): void
    {
        $_GET['error'] = 'invalid_token';

        $controller = new Login();
        ob_start();
        $controller->control();
        $output = ob_get_clean();

        $this->assertStringContainsString('lien de vérification est invalide', (string) $output);
    }

    public function testLoginFormContainsRequiredFields(): void
    {
        $controller = new Login();
        ob_start();
        $controller->control();
        $output = ob_get_clean();

        $this->assertStringContainsString('type="email"', (string) $output);
        $this->assertStringContainsString('type="password"', (string) $output);
        $this->assertStringContainsString('type="submit"', (string) $output);
    }
}
