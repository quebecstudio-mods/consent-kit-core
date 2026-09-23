<?php

namespace QuebecStudioMods\ConsentKit\Core;

/** Where the package's templates and front-end assets live, for the integrations. */
final class Paths
{
    /** Folder of the shipped templates for one engine: `blade` or `twig`. */
    public static function views(string $engine): string
    {
        return dirname(__DIR__) . '/resources/views/' . $engine;
    }

    /** Folder of the shipped wording files, `<language>.php`. */
    public static function lang(): string
    {
        return __DIR__ . '/lang';
    }

    /**
     * Folder of the control panel wording, `<language>.json`, shared by the
     * CMS integrations. Separate from the banner's: one is read by a visitor,
     * the other by whoever administers the site.
     */
    public static function cpLang(): string
    {
        return __DIR__ . '/lang/cp';
    }

    /**
     * The control panel wording for one language, keyed by the English source
     * string. Empty when the language ships none.
     *
     * @return array<string, string>
     */
    public static function cpStrings(string $language): array
    {
        $file = self::cpLang() . "/$language.json";

        if (!is_file($file)) {
            return [];
        }

        return (array)json_decode((string)file_get_contents($file), true);
    }

    /**
     * The suite's mark. `icon` is monochrome, for a panel that colours it;
     * `marketplace-icon` is the full-colour one, for a listing.
     */
    public static function icon(string $name = 'icon'): string
    {
        return dirname(__DIR__) . "/resources/$name.svg";
    }

    /** A front-end asset: `consent.js`, `consent.css`. */
    public static function asset(string $file): string
    {
        return dirname(__DIR__) . '/resources/' . $file;
    }
}
