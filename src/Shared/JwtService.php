<?php

namespace Shared;

/**
 * JwtService
 *
 * Minimal JWT implementation (HS256) without external dependencies.
 * Handles token generation, signing, and validation.
 *
 * @package Shared
 */
class JwtService
{
    /**
     * Secret key used to sign tokens.
     * Override via environment variable JWT_SECRET in production.
     */
    private const SECRET = 'SAE_MANAGER_JWT_SECRET_CHANGE_ME_IN_PROD';

    /**
     * Token lifetime in seconds (1 hour).
     */
    public const TTL = 3600;

    /**
     * Generates a signed JWT for the given user payload.
     *
     * @param array<string, mixed> $payload User data to encode (id, role, etc.)
     * @return string Signed JWT string (header.payload.signature)
     */
    public static function generate(array $payload): string
    {
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]) ?: '');

        $payload['iat'] = time();
        $payload['exp'] = time() + self::TTL;

        $encodedPayload = self::base64UrlEncode(json_encode($payload) ?: '');

        $signature = self::sign($header . '.' . $encodedPayload);

        return $header . '.' . $encodedPayload . '.' . $signature;
    }

    /**
     * Validates a JWT string and returns its decoded payload.
     *
     * @param string $token The JWT to validate
     * @return array<string, mixed>|null Decoded payload, or null if invalid/expired
     */
    public static function validate(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        // Verify signature
        $expectedSignature = self::sign($header . '.' . $payload);
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        // Decode payload
        $decodedJson = self::base64UrlDecode($payload);
        if ($decodedJson === '') {
            return null;
        }

        $data = json_decode($decodedJson, true);
        if (!is_array($data)) {
            return null;
        }

        // Check expiration
        $exp = $data['exp'] ?? 0;
        if (!is_numeric($exp) || time() > (int)$exp) {
            return null; // Token expired
        }

        return $data;
    }

    /**
     * Signs a string using HMAC-SHA256.
     *
     * @param string $data Data to sign
     * @return string Base64Url-encoded signature
     */
    private static function sign(string $data): string
    {
        $secret = defined('JWT_SECRET') ? (string)constant('JWT_SECRET') : self::SECRET;
        return self::base64UrlEncode(hash_hmac('sha256', $data, $secret, true));
    }

    /**
     * Encodes a string using Base64Url (URL-safe Base64 without padding).
     *
     * @param string $data Raw string to encode
     * @return string Base64Url-encoded string
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodes a Base64Url-encoded string.
     *
     * @param string $data Base64Url-encoded string
     * @return string Decoded string (empty string if decoding fails)
     */
    private static function base64UrlDecode(string $data): string
    {
        $padded = str_pad(strtr($data, '-_', '+/'), strlen($data) + (4 - strlen($data) % 4) % 4, '=');
        return base64_decode($padded) ?: '';
    }
}
