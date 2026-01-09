<?php

namespace Tests\Unit\Controllers\User;

use Controllers\User\Login;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour le contrôleur d'affichage du formulaire de connexion
 */
class LoginControllerTest extends TestCase
{
    private Login $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new Login();
    }

    /**
     * Test : Support de la route /user/login en GET
     */
    public function testSupportsLoginRoute(): void
    {
        $this->assertTrue(Login::support('/user/login', 'GET'));
    }

    /**
     * Test : Ne supporte pas POST (géré par LoginPost)
     */
    public function testDoesNotSupportPostMethod(): void
    {
        $this->assertFalse(Login::support('/user/login', 'POST'));
    }

    /**
     * Test :  Affichage du message de succès après inscription
     */
    public function testDisplaysRegistrationSuccessMessage(): void
    {
        $_GET['success'] = 'registered';

        ob_start();
        $this->controller->control();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            'Inscription réussie',
            $output,
            'Le message de succès d\'inscription devrait être affiché'
        );

        unset($_GET['success']);
    }

    /**
     * Test : Affichage du message après réinitialisation du mot de passe
     */
    public function testDisplaysPasswordResetSuccessMessage(): void
    {
        $_GET['success'] = 'password_reset';

        ob_start();
        $this->controller->control();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            'mot de passe a été réinitialisé',
            $output
        );

        unset($_GET['success']);
    }

    /**
     * Test :  Affichage d'erreur pour token invalide
     */
    public function testDisplaysInvalidTokenError(): void
    {
        $_GET['error'] = 'invalid_token';

        ob_start();
        $this->controller->control();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            'invalide ou a expiré',
            $output
        );

        unset($_GET['error']);
    }

    /**
     * Test : Le formulaire contient les champs requis
     */
    public function testLoginFormContainsRequiredFields(): void
    {
        ob_start();
        $this->controller->control();
        $output = ob_get_clean();

        $this->assertStringContainsString('name="uname"', $output, 'Champ email manquant');
        $this->assertStringContainsString('name="psw"', $output, 'Champ mot de passe manquant');
        $this->assertStringContainsString('type="submit"', $output, 'Bouton submit manquant');
    }
}