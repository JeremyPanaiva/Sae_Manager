<?php

namespace Shared;

use Shared\Exceptions\ValidationException;

/**
 * PasswordValidator — Password complexity validation service.
 *
 * Validates that a password meets the application's security requirements:
 * - Between 12 and 30 characters
 * - At least one uppercase letter
 * - At least one lowercase letter
 * - At least one digit
 * - At least one special character
 *
 * @package Shared
 */
class PasswordValidator
{
    /** @var int Minimum password length. */
    private const MIN_LENGTH = 12;

    /** @var int Maximum password length. */
    private const MAX_LENGTH = 30;

    /** @var string Regex pattern for allowed special characters. */
    private const SPECIAL_CHARS_REGEX = '/[!@#$%^&*()_+€£µ§?\/\[\]|{}]/';

    /**
     * Validates the given password against all complexity rules.
     *
     * Returns an array of ValidationException instances, one per broken rule.
     * An empty array means the password is valid.
     *
     * @param string $password The plain-text password to validate.
     * @return ValidationException[] List of validation errors (empty if valid).
     */
    public static function validate(string $password): array
    {
        $errors = [];

        if (strlen($password) < self::MIN_LENGTH || strlen($password) > self::MAX_LENGTH) {
            $errors[] = new ValidationException(
                "Le mot de passe doit contenir entre " . self::MIN_LENGTH
                . " et " . self::MAX_LENGTH . " caractères."
            );
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = new ValidationException(
                "Le mot de passe doit contenir au moins une lettre majuscule."
            );
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = new ValidationException(
                "Le mot de passe doit contenir au moins une lettre minuscule."
            );
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = new ValidationException(
                "Le mot de passe doit contenir au moins un chiffre."
            );
        }

        if (!preg_match(self::SPECIAL_CHARS_REGEX, $password)) {
            $errors[] = new ValidationException(
                "Le mot de passe doit contenir au moins un des caractères spéciaux suivants : "
                . "! @ # $ % ^ & * ( ) _ + € £ µ § ? / \\ | { } [ ]"
            );
        }

        return $errors;
    }

    /**
     * Returns true if the password passes all complexity rules.
     *
     * @param string $password The plain-text password to check.
     * @return bool
     */
    public static function isValid(string $password): bool
    {
        return empty(self::validate($password));
    }
}
