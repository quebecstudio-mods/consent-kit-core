<?php

namespace QuebecStudioMods\ConsentKit\Core;

/**
 * Swaps `[cookie-table]` markers written inside content for the declared
 * inventory, rendered by the caller.
 *
 * Every wrapper exposes this as its own filter or helper; the matching and
 * the attributes an author may write are the same everywhere.
 */
final class CookieTableMarkers
{
    /**
     * `[cookie-table]`, and everything the author may add to it:
     *
     *     [cookie-table:statistics]
     *     [cookie-table category="statistics" heading="false"]
     *     [cookie-table heading=false level=2]
     *
     * Written by hand, so it is matched loosely: spaces, case and quoting all
     * vary. Group 1 is the shorthand category, group 2 the attributes.
     */
    private const MARKER = '/\[\s*cookie-table\b\s*(?::\s*([a-z0-9_\-]+))?\s*([^\]]*)\]/i';

    /** Attributes an author may write, and the option each one sets. */
    private const ATTRIBUTES = [
        'category' => 'category',
        'heading' => 'heading',
        'level' => 'headingLevel',
        'headinglevel' => 'headingLevel',
    ];

    public static function contains(string $html): bool
    {
        return (bool)preg_match(self::MARKER, $html);
    }

    /**
     * `$html` with every marker replaced by `$render($options)`. `$options`
     * are the page's defaults; a marker's own attributes win over them, since
     * the marker is what this spot asks for.
     *
     * @param callable(array): string $render
     */
    public static function replace(string $html, callable $render, array $options = []): string
    {
        $replaced = preg_replace_callback(
            self::MARKER,
            static function (array $match) use ($render, $options): string {
                $category = trim($match[1]);

                if ($category !== '') {
                    $options['category'] = $category;
                }

                return (string)$render(self::parseAttributes($match[2]) + $options);
            },
            $html
        );

        return $replaced ?? $html;
    }

    /**
     * `key="value"` pairs from one marker, as render options.
     *
     * Quotes are optional and an unknown attribute is ignored: this is typed
     * into a rich text editor, where a smart quote or a stray word is likelier
     * than a syntax error the author could act on.
     *
     * Classes are deliberately not among them. They belong to the site's
     * appearance, set once, not to a sentence in a policy.
     */
    private static function parseAttributes(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $raw = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        preg_match_all('/([a-z]+)\s*=\s*["\'\x{201C}\x{201D}\x{2018}\x{2019}]?([a-z0-9_\-]+)/iu', $raw, $pairs, PREG_SET_ORDER);

        $options = [];

        foreach ($pairs as $pair) {
            $option = self::ATTRIBUTES[strtolower($pair[1])] ?? null;

            if ($option === null) {
                continue;
            }

            $options[$option] = match ($option) {
                'heading' => !in_array(strtolower($pair[2]), ['false', '0', 'no', 'non'], true),
                'headingLevel' => (int)$pair[2],
                default => $pair[2],
            };
        }

        return $options;
    }
}
