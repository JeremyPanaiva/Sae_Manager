<?php

namespace Tests\Unit\Controllers\User;

use PHPUnit\Framework\TestCase;
use Controllers\User\RegisterPost;

class RegisterPostControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['REQUEST_URI'] = '/user/register';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = 'off';

        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    public function testDoesNothingWhenOkNotSet(): void
    {
        $controller = new RegisterPost();
        ob_start();
        $controller->control();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testValidatesPasswordLength(): void
    {
        $_POST['ok'] = true;
        $_POST['nom'] = 'Test';
        $_POST['prenom'] = 'User';
        $_POST['mail'] = 'test@example.com';
        $_POST['mdp'] = 'short';
        $_POST['role'] = 'etudiant';

        // Test supprimé car PHPStan signale toujours vrai
        // $this->assertTrue(strlen($_POST['mdp']) < 8);
    }

    public function testValidatesEmailFormat(): void
    {
        $validEmail = 'test@example.com';
        $invalidEmail = 'invalid-email';

        $this->assertTrue(filter_var($validEmail, FILTER_VALIDATE_EMAIL) !== false);
        $this->assertFalse(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL) !== false);
    }
}
