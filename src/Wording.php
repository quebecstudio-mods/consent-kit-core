<?php

namespace QuebecStudioMods\ConsentKit\Core;

/**
 * The control panel wording, in the one spelling the files are written in, and
 * in the one a host asks for.
 *
 * Laravel substitutes `:name`, Craft CMS's own translator `{name}`. The files
 * hold the Laravel spelling; a host that wants the other calls `braces()`.
 */
final class Wording
{
    /**
     * The parameters the wording actually takes. A conversion by pattern would
     * also rewrite `{{ consent:banner }}`, an Antlers tag that appears in the
     * help text, so only these are touched.
     */
    public const PLACEHOLDERS = ['count', 'date', 'id', 'n', 'page', 'pages', 'tag', 'total'];

    /**
     * The same strings with `:name` written `{name}`, keys included: a host
     * looks a string up by the spelling its own code uses.
     *
     * @param  array<string, string>  $strings
     * @return array<string, string>
     */
    public static function braces(array $strings): array
    {
        $converted = [];

        foreach ($strings as $key => $value) {
            $converted[self::convert($key)] = self::convert($value);
        }

        return $converted;
    }

    private static function convert(string $string): string
    {

        $pattern = '/:(' . implode('|', self::PLACEHOLDERS) . ')\b/';

        return (string)preg_replace($pattern, '{$1}', $string);
    }
}
