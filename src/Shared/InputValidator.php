<?php

namespace Shared;

/**
 * InputValidator - Centralized user input validation.
 * Compliant with OWASP Input Validation Cheat Sheet.
 */
class InputValidator
{
    public static function email(string $input): ?string
    {
        $email = filter_var(trim($input), FILTER_VALIDATE_EMAIL);
        return $email !== false ? $email : null;
    }

    public static function integer(mixed $input, int $min = 0, int $max = PHP_INT_MAX): ?int
    {
        $val = filter_var($input, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => $min, 'max_range' => $max]
        ]);
        return $val !== false ? $val : null;
    }

    public static function string(string $input, int $maxLength = 255): string
    {
        return mb_substr(trim($input), 0, $maxLength, 'UTF-8');
    }

    public static function safeFilename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename) ?? 'file';
    }
}
