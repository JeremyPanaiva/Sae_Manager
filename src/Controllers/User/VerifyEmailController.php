<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Shared\Exceptions\DataBaseException;

/**
 * Email verification controller
 *
 * Handles email address verification for newly registered users or users who
 * changed their email address.  Validates the verification token from the URL
 * and activates the user account if valid.
 *
 * @package Controllers\User
 */
class VerifyEmailController implements ControllerInterface
{
    /**
     * Main controller method
     *
     * Extracts the verification token from the query string, validates it,
     * and activates the user account.  Redirects to login page with appropriate
     * success or error message.
     *
     * @return void
     */
    public function control()
    {
        // Extract verification token from URL parameter
        $tokenRaw = $_GET['token'] ?? '';
        $token = is_string($tokenRaw) ? $tokenRaw : '';

        // Redirect to login if no token provided
        if (empty($token)) {
            header("Location: /user/login");
            exit();
        }

        $user = new User();
        try {
            // Attempt to verify account with the provided token
            if ($user->verifyAccount($token)) {
                // Token is valid - account verified successfully
                header("Location: /user/login?success=account_verified");
            } else {
                // Token is invalid or account already verified
                header("Location: /user/login?error=invalid_token");
            }
        } catch (DataBaseException $e) {
            // Database error during verification
            header("Location: /user/login?error=db_error");
        }
        exit();
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * @param string $path The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path is '/user/verify-email' and method is GET
     */
    public static function support(string $path, string $method): bool
    {
        return $path === "/user/verify-email" && $method === "GET";
    }
}
