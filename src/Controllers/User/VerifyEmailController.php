<?php
namespace Controllers\User;

use Controllers\ControllerInterface;
use Models\User\User;
use Shared\Exceptions\DataBaseException;

class VerifyEmailController implements ControllerInterface
{
    public function control()
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            header("Location: /user/login");
            exit();
        }

        $user = new User();
        try {
            if ($user->verifyAccount($token)) {
                header("Location: /user/login?success=account_verified");
            } else {
                // Invalid token or already verified
                header("Location: /user/login?error=invalid_token");
            }
        } catch (DataBaseException $e) {
            header("Location: /user/login?error=db_error");
        }
        exit();
    }

    public static function support(string $path, string $method): bool
    {
        return $path === "/user/verify-email" && $method === "GET";
    }
}
