<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\PasswordResetToken;
use Models\User\User;
use Models\Database;
use Shared\Exceptions\DataBaseException;

/**
 * Password reset form controller
 *
 * Handles GET requests to display the password reset form.  Validates the reset token
 * from the URL and renders the form if the token is valid.  Redirects to the forgot
 * password page if the token is invalid or expired.
 *
 * @package Controllers\User
 */
class ResetPassword implements ControllerInterface
{
    /**
     * Password reset route path
     *
     * @var string
     */
    public const PATH = "/user/reset-password";

    /**
     * Main controller method
     *
     * Validates the password reset token from the URL query parameter.
     * If valid, displays the password reset form.  If invalid or expired,
     * redirects to the forgot password page with an error message.
     *
     * @return void
     */
    public function control()
    {
        // Extract reset token from URL parameter
        $token = $_GET['token'] ?? '';

        // Redirect if no token provided
        if (empty($token)) {
            header('Location: ?page=forgot-password&error=invalid_token');
            exit;
        }

        try {
            // Validate token and retrieve associated email
            $tokenModel = new PasswordResetToken();
            $email = $tokenModel->validateToken($token);

            if (!$email) {
                // Token is invalid or expired
                header('Location: ?page=forgot-password&error=invalid_token');
                exit;
            }

            // Render password reset form with token and email
            $view = new \Views\User\ResetPasswordView();
            $view->setData(['token' => $token, 'email' => $email]);
            echo $view->render();

        } catch (DataBaseException $e) {
            // Database error during token validation
            error_log("Erreur base de données dans ResetPassword: " . $e->getMessage());
            header('Location: ? page=forgot-password&error=database_error');
            exit;
        }
    }

    /**
     * Checks if this controller supports the given route and HTTP method
     *
     * Supports both standard path and legacy query parameter format.
     *
     * @param string $chemin The requested route path
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return bool True if path matches reset password route and method is GET
     */
    static function support(string $chemin, string $method): bool
    {
        return ($chemin === self::PATH ||
                (isset($_GET['page']) && $_GET['page'] === 'reset-password'))
            && $method === "GET";
    }
}