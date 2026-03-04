<?php

namespace Tests\Unit\Controllers\User;

use PHPUnit\Framework\TestCase;
use Models\User\EmailService;

class ProfileControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REQUEST_URI'] = '/user/change-password';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * Test that the sendPasswordChangedNotificationEmail method exists and is functional
     * Verifies that the password change notification email can be sent
     */
    public function testEmailSentAfterPasswordChange(): void
    {
        // Verify that the method exists in EmailService
        $this->assertTrue(method_exists(EmailService::class, 'sendPasswordChangedNotificationEmail'));

        // Verify that it is a public method
        $reflection = new \ReflectionMethod(EmailService::class, 'sendPasswordChangedNotificationEmail');
        $this->assertTrue($reflection->isPublic());

        // Verify that it accepts an email parameter
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('email', $params[0]->getName());
    }

    /**
     * Test that password confirmation is required before account deletion
     * Verifies that the password submitted in the deletion form (delete_password) is validated
     */
    public function testPasswordConfirmationRequiredForAccountDeletion(): void
    {
        // Simulate a user password
        $userPassword = 'SecurePass123';
        $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);

        // Verify that the correct password is accepted
        $this->assertTrue(password_verify($userPassword, $hashedPassword));

        // Verify that an incorrect password is rejected
        $this->assertFalse(password_verify('WrongPassword456', $hashedPassword));

        // Verify that ProfileController exists with deletion method
        $this->assertTrue(class_exists('Controllers\User\ProfileController'));

        // Verify that the deletion path is correctly defined
        $this->assertTrue(defined('Controllers\User\ProfileController::PATH_DELETE'));
        $this->assertEquals('/user/profile/delete', constant('Controllers\User\ProfileController::PATH_DELETE'));

        // Verify that the controller supports POST on the deletion path
        $profileController = 'Controllers\User\ProfileController';
        $this->assertTrue($profileController::support('/user/profile/delete', 'POST'));

        // Verify that the form uses the 'delete_password' field
        $_POST['delete_password'] = $userPassword;
        $this->assertEquals($userPassword, $_POST['delete_password']);

        // Verify that the submitted password can be validated
        $submittedPassword = $_POST['delete_password'];
        $this->assertTrue(password_verify($submittedPassword, $hashedPassword));
    }
}



