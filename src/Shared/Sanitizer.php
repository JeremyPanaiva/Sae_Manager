<?php

namespace Shared;

/**
 * Sanitizer - Utilitaires d'échappement pour prévenir les attaques XSS.
 * Conforme à OWASP XSS Prevention Cheat Sheet.
 */
class Sanitizer
{
    /**
     * Échappe une chaîne pour un affichage HTML sécurisé.
     */
    public static function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Échappe pour un attribut HTML.
     */
    public static function escapeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Échappe pour du JavaScript inline.
     */
    public static function escapeJs(string $value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}

