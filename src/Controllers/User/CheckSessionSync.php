<?php

namespace Controllers\User;

use Controllers\ControllerInterface;
use Shared\SessionGuard;

/**
 * Check Session Sync Controller
 *
 * Provides a lightweight JSON endpoint to verify if the current session
 * is still valid and has not been overridden by another device.
 * Used by the client-side monitoring script.
 *
 * @package Controllers\User
 */
class CheckSessionSync implements ControllerInterface
{
    /** @var string Route path */
    public const PATH = "/user/check-session-sync";

    /**
     * Executes the session validation.
     *
     * Returns a JSON object: {"valid": true} or {"valid": false}.
     *
     * @return void
     */
    public function control(): void
    {
        header('Content-Type: application/json');

        // We pass 'false' to avoid the PHP redirect, as we want to handle it in JS
        $isValid = SessionGuard::check(false);

        echo json_encode(['valid' => $isValid]);
        exit;
    }

    /**
     * Supports only the GET method for this endpoint.
     *
     * @param string $chemin The requested path
     * @param string $method The HTTP method
     * @return bool
     */
    public static function support(string $chemin, string $method): bool
    {
        return $chemin === self::PATH && $method === "GET";
    }
}
