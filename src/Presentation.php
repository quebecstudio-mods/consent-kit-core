<?php

namespace QuebecStudioMods\ConsentKit\Core;

/**
 * What the banner was showing, reduced to a fingerprint.
 *
 * The payload is built from the configuration the server resolves for itself,
 * never from the request: a browser cannot claim to have been shown something
 * else, and the page carries no trace of it. Two decisions sharing a
 * fingerprint saw exactly the same wording.
 */
final class Presentation
{
    /**
     * The readable part of a banner configuration. Anything a visitor cannot
     * read is left out, so restyling a site does not invalidate its proofs.
     */
    public static function payload(array $config): array
    {
        return [
            'version' => (int)($config['version'] ?? 1),
            'language' => (string)($config['language'] ?? ''),
            'policyUrl' => $config['policyUrl'] ?? null,
            'categories' => array_map(
                static fn (array $category): array => [
                    'handle' => $category['handle'] ?? '',
                    'label' => $category['label'] ?? '',
                    'description' => $category['description'] ?? '',
                    'required' => (bool)($category['required'] ?? false),
                    'default' => (bool)($category['default'] ?? false),
                    'cookies' => $category['cookies'] ?? [],
                ],
                $config['categories'] ?? [],
            ),
            'texts' => $config['texts'] ?? [],
        ];
    }

    public static function hash(array $payload): string
    {
        return hash('sha256', self::canonical($payload));
    }

    /**
     * Keys sorted, order of lists kept: storage order must not move the hash,
     * but the order categories appear in is part of what was shown.
     *
     * Every byte of this matters. A fingerprint is only worth something if a
     * third party recomputes it exactly, so the flags below are part of the
     * format, not a preference.
     */
    public static function canonical(array $payload): string
    {
        return (string)json_encode(
            self::sortKeys($payload),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }

    private static function sortKeys(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $value = array_map(static fn (mixed $item): mixed => self::sortKeys($item), $value);

        if (!array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
