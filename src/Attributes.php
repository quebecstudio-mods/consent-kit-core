<?php

namespace QuebecStudioMods\ConsentKit\Core;

/**
 * Optional class attributes for the Blade templates.
 *
 * Blade only compiles a directive that does not follow a letter, so
 * `<thead@if(...)` or `class="qsm-ck-inventory@if(...)"` would be printed as is.
 * These are echoed instead; the output matches the Twig templates.
 */
final class Attributes
{
    /** ` class="…"`, or nothing when the site set no class. */
    public static function classAttribute(mixed $classes): string
    {
        return $classes ? ' class="' . self::escape($classes) . '"' : '';
    }

    /** ` …` to append to a class list, or nothing when the site set no class. */
    public static function classSuffix(mixed $classes): string
    {
        return $classes ? ' ' . self::escape($classes) : '';
    }

    private static function escape(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
