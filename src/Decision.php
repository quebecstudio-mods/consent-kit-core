<?php

namespace QuebecStudioMods\ConsentKit\Core;

/**
 * A reported answer, read against the categories the site actually offers.
 *
 * What a browser sends is a claim. These turn it into something the server
 * stands behind, or reject it.
 */
final class Decision
{
    public const OUTCOME_ALL = 'all';

    public const OUTCOME_SOME = 'some';

    public const OUTCOME_NONE = 'none';

    public const OUTCOMES = [self::OUTCOME_ALL, self::OUTCOME_SOME, self::OUTCOME_NONE];

    /**
     * The answer restated over every offered category, required ones granted.
     * Null when it names a category the site does not offer, which no banner
     * of this site could have produced.
     */
    public static function reconcile(array $answer, array $categories): ?array
    {
        $handles = array_column($categories, 'handle');

        foreach (array_keys($answer) as $handle) {
            if (!in_array($handle, $handles, true)) {
                return null;
            }
        }

        $reconciled = [];

        foreach ($categories as $category) {
            $handle = (string)($category['handle'] ?? '');
            $reconciled[$handle] = ($category['required'] ?? false) || !empty($answer[$handle]);
        }

        return $reconciled;
    }

    /**
     * What the visitor granted, of what they could choose: `all`, `some` or
     * `none`. Required categories are left out — they are granted either way,
     * and counting them would make every refusal look partial.
     */
    public static function outcome(array $answer, array $categories): string
    {
        $optional = array_filter($categories, static fn (array $category): bool => empty($category['required']));
        $offered = count($optional);
        $granted = count(array_filter(
            $optional,
            static fn (array $category): bool => !empty($answer[$category['handle'] ?? '']),
        ));

        return match (true) {
            $offered === 0, $granted === $offered => self::OUTCOME_ALL,
            $granted === 0 => self::OUTCOME_NONE,
            default => self::OUTCOME_SOME,
        };
    }
}
