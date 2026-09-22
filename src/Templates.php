<?php

namespace QuebecStudioMods\ConsentKit\Core;

/**
 * Variables for the shipped templates, prepared so the templates hold no
 * logic: the same output has to come from Twig on 2.x and Blade on main.
 *
 * Each method takes the variables a template has always received — the
 * public contract a site's override relies on — and adds the derived ones,
 * prefixed `qsm-ck` so none can clash with a site's own template variables.
 */
final class Templates
{
    public static function banner(array $variables): array
    {
        $texts = $variables['texts'];

        return [

            'qsmConfigJson' => json_encode(
                $variables['config'],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR
            ),
            'categories' => self::withProviderLabels($variables['categories'], $texts),
        ] + $variables;
    }

    public static function cookieTable(array $variables): array
    {
        $level = $variables['headingLevel'] ?? null;

        return [
            'qsmLevel' => min(max(in_array($level, [null, '', false], true) ? 3 : $level, 2), 6),
            'categories' => array_map(
                static fn (array $category) => ['qsmTitleId' => 'qsm-ck-inventory-' . $category['handle']] + $category,
                self::withProviderLabels($variables['categories'], $variables['texts'])
            ),
        ] + $variables;
    }

    public static function videoFacade(array $variables): array
    {
        return [
            'qsmPosterUrl' => ($variables['poster'] ?? null) ?: ($variables['thumbnail'] ?? null),
        ] + $variables;
    }

    public static function videoEmbed(array $variables): array
    {
        return [
            'qsmIframeTitle' => $variables['title'] ?? 'YouTube',
        ] + $variables;
    }

    /** A cookie with no provider is set by the site itself. */
    private static function withProviderLabels(array $categories, array $texts): array
    {
        return array_map(static fn (array $category) => [
            'cookies' => array_map(
                static fn (array $cookie) => ['qsmProviderLabel' => $cookie['provider'] ?? $texts['firstParty']] + $cookie,
                $category['cookies'] ?? []
            ),
        ] + $category, $categories);
    }
}
