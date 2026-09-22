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

    /** A front-end asset: `consent.js`, `consent.css`. */
    public static function asset(string $file): string
    {
        return dirname(__DIR__) . '/resources/' . $file;
    }
}
