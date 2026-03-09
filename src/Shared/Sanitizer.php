<?php

namespace Shared;

/**
 * Sanitizer - Escaping utilities to prevent XSS attacks.
 * Compliant with OWASP XSS Prevention Cheat Sheet.
 */
class Sanitizer
{
    /**
     * Escapes a string for safe HTML display.
     */
    public static function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Escapes a string for use in an HTML attribute.
     */
    public static function escapeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Escapes a string for inline JavaScript.
     */
    public static function escapeJs(string $value): string
    {
        $encoded = json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        return $encoded !== false ? $encoded : '';
    }
}
