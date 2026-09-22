<?php

namespace QuebecStudioMods\ConsentKit\Core;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Folds a settings submission back into what is stored.
 *
 * The control panel edits one site at a time, so a form carries that site
 * alone: every per-site value is merged over the stored ones, or saving one
 * site would erase the others. Category handles are common to the install
 * and permanent, since the consent cookie stores them.
 */
final class SettingsMerger
{
    /**
     * What to store after a settings pane is submitted.
     *
     * `$stored` is what the settings store holds, never the effective
     * settings: values coming from a config file must not be written back.
     * `$keys` are the setting names; anything else posted (the edited site,
     * the add/delete category controls) steers the merge and is dropped.
     * `$site` is the site the pane edits, or null when it edits none.
     * `$defaultCategories` is the shipped inventory worded for the install's
     * sites, used when nothing is stored yet.
     */
    public static function applySubmission(
        array $stored,
        array $posted,
        array $keys,
        ?SiteContext $site,
        array $defaultCategories,
    ): array {
        $merged = Arr::only(array_merge($stored, $posted), $keys);

        if (isset($posted['categories'])) {
            $merged['categories'] = self::applyCategoryChange(
                $merged['categories'] ?? [],
                $posted['addCategory'] ?? null,
                $posted['newCategory'] ?? null,
                $posted['deleteCategory'] ?? null,
                ($stored['categories'] ?? []) ?: Defaults::categories()
            );
        }

        if ($site === null) {
            return $merged;
        }

        if (isset($posted['texts'])) {
            $merged['texts'] = self::mergeTexts($stored['texts'] ?? [], $merged['texts'] ?? [], $site->handle);
        }

        if (isset($posted['categories'])) {
            $merged['categories'] = self::mergeCategories(
                ($stored['categories'] ?? []) ?: $defaultCategories,
                $merged['categories'] ?? [],
                $site->handle
            );
        }

        if (isset($posted['policySource']) || isset($posted['policyEntry']) || isset($posted['policyUrl'])) {
            foreach (['policySource', 'policyEntry', 'policyUrl'] as $key) {
                $merged[$key] = self::mergePerSite($stored[$key] ?? [], $merged[$key] ?? [], $site->id);
            }

            if (($merged['policySource'][$site->id] ?? null) === 'entry') {
                unset($merged['policyUrl'][$site->id]);
            } elseif (($merged['policySource'][$site->id] ?? null) === 'url') {
                unset($merged['policyEntry'][$site->id]);
            }
        }

        return $merged;
    }

    /**
     * Overwrites one site's value, keeping the others. A cleared value is
     * removed, so a policy can be unset once chosen.
     */
    public static function mergePerSite(mixed $stored, mixed $posted, int $siteId): array
    {
        $result = is_array($stored) ? $stored : [];
        $value = is_array($posted) ? ($posted[$siteId] ?? null) : null;

        if ($value === null || $value === '' || $value === []) {
            unset($result[$siteId]);

            return $result;
        }

        $result[$siteId] = $value;

        return $result;
    }

    /**
     * One site's wording replaces what is stored for that site; the other
     * sites are untouched. A cleared field drops out of the submission, so
     * the posted set is taken whole rather than merged key by key.
     */
    public static function mergeTexts(mixed $stored, mixed $posted, string $siteHandle): array
    {
        $result = is_array($stored) ? $stored : [];
        $values = is_array($posted) ? ($posted[$siteHandle] ?? []) : [];
        $values = is_array($values)
            ? array_filter($values, static fn (mixed $v): bool => trim((string)$v) !== '')
            : [];

        if ($values === []) {

            unset($result[$siteHandle]);

            return $result;
        }

        $result[$siteHandle] = $values;

        return $result;
    }

    /**
     * Folds the posted tables back into the category structure. Only the
     * edited site's wording is written.
     */
    public static function mergeCategories(mixed $stored, mixed $posted, string $siteHandle): array
    {
        $stored = is_array($stored) ? $stored : [];
        $posted = is_array($posted) ? $posted : [];
        $result = [];

        foreach ($posted as $handle => $edits) {
            if (!is_array($edits)) {
                continue;
            }

            $before = is_array($stored[$handle] ?? null) ? $stored[$handle] : [];

            $result[$handle] = [
                'required' => (bool)($before['required'] ?? $edits['required'] ?? false),
                'label' => self::writeForSite($before['label'] ?? [], $edits['label'] ?? '', $siteHandle),
                'description' => self::writeForSite($before['description'] ?? [], $edits['description'] ?? '', $siteHandle),
                'cookies' => self::mergeCookies($before['cookies'] ?? [], $edits['cookies'] ?? [], $siteHandle),
            ];
        }

        return $result;
    }

    /**
     * Adds or removes a category as part of a save.
     *
     * Creating one is the only moment a handle is chosen: renaming it later
     * would leave every stored consent pointing at a category that no longer
     * exists. A required category cannot be removed — `necessary` is what the
     * banner shows when a visitor has accepted nothing.
     *
     * `$delete` is one handle, or a map of handles to checkbox values.
     */
    public static function applyCategoryChange(mixed $categories, mixed $add, mixed $new, mixed $delete, mixed $stored): array
    {
        $categories = is_array($categories) ? $categories : [];
        $stored = is_array($stored) ? $stored : [];
        $deleted = is_array($delete) ? array_keys(array_filter($delete)) : [(string)$delete];

        foreach (array_filter($deleted, static fn (mixed $handle): bool => $handle !== '') as $handle) {

            if (($stored[$handle]['required'] ?? false) !== true) {
                unset($categories[$handle]);
            }
        }

        if (!$add) {
            return $categories;
        }

        $new = is_array($new) ? $new : [];
        $handle = self::slug(trim((string)($new['handle'] ?? '')) ?: (string)($new['label'] ?? ''));

        if ($handle === '' || isset($categories[$handle])) {
            return $categories;
        }

        $categories[$handle] = [
            'required' => false,
            'label' => trim((string)($new['label'] ?? '')) ?: $handle,
            'description' => '',
            'cookies' => [],
        ];

        return $categories;
    }

    /**
     * Adds the shipped categories and cookies that are missing, by handle, and
     * touches nothing else. Wording is derived per site from each site's
     * language, so a French site starts with French labels.
     *
     * @param SiteContext[] $sites
     */
    public static function seedDefaults(
        array $categories,
        array $sites,
        string $defaultLanguage,
        Languages $languages = new Languages(),
    ): array {
        foreach (Defaults::categories($languages) as $handle => $default) {
            $default['label'] = self::perSiteFromLanguage($default['label'] ?? [], $sites, $defaultLanguage);
            $default['description'] = self::perSiteFromLanguage($default['description'] ?? [], $sites, $defaultLanguage);

            $cookies = [];

            foreach ($default['cookies'] ?? [] as $cookieHandle => $cookie) {
                $cookie['purpose'] = self::perSiteFromLanguage($cookie['purpose'] ?? [], $sites, $defaultLanguage);
                $cookie['duration'] = self::perSiteFromLanguage($cookie['duration'] ?? [], $sites, $defaultLanguage);
                $cookies[$cookieHandle] = $cookie;
            }

            $default['cookies'] = $cookies;

            if (!isset($categories[$handle]) || !is_array($categories[$handle])) {
                $categories[$handle] = $default;
                continue;
            }

            $existing = is_array($categories[$handle]['cookies'] ?? null)
                ? $categories[$handle]['cookies']
                : [];

            foreach ($cookies as $cookieHandle => $cookie) {
                if (!isset($existing[$cookieHandle])) {
                    $existing[$cookieHandle] = $cookie;
                }
            }

            $categories[$handle]['cookies'] = $existing;
        }

        return $categories;
    }

    /**
     * One category's table. A row keeps the wording the other sites gave the
     * cookie it names; a cookie no longer listed is dropped for every site.
     */
    private static function mergeCookies(mixed $stored, mixed $posted, string $siteHandle): array
    {
        $previous = is_array($stored) ? $stored : [];
        $rows = is_array($posted) ? $posted : [];
        $cookies = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $handle = trim((string)($row['handle'] ?? '')) ?: self::slug((string)($row['name'] ?? ''));

            if ($handle === '' || trim((string)($row['name'] ?? '')) === '') {
                continue;
            }

            $old = is_array($previous[$handle] ?? null) ? $previous[$handle] : [];

            $cookies[$handle] = [
                'name' => trim((string)$row['name']),
                'provider' => trim((string)($row['provider'] ?? '')) ?: null,
                'purpose' => self::writeForSite($old['purpose'] ?? [], $row['purpose'] ?? '', $siteHandle),
                'duration' => self::writeForSite($old['duration'] ?? [], $row['duration'] ?? '', $siteHandle),
            ];
        }

        return $cookies;
    }

    /** Writes one site's value into a per-site map, leaving the others alone. */
    private static function writeForSite(mixed $stored, mixed $value, string $siteHandle): array
    {
        $result = is_array($stored) ? $stored : [];
        $value = trim((string)$value);

        if ($value === '') {
            unset($result[$siteHandle]);

            return $result;
        }

        $result[$siteHandle] = $value;

        return $result;
    }

    /**
     * Wording for every site, each taking the value written for its own
     * language.
     *
     * @param SiteContext[] $sites
     */
    private static function perSiteFromLanguage(array $byLanguage, array $sites, string $defaultLanguage): array
    {
        $values = [];

        foreach ($sites as $site) {
            $base = explode('-', $site->language)[0];

            $value = $byLanguage[$site->language]
                ?? $byLanguage[$base]
                ?? $byLanguage[$defaultLanguage]
                ?? reset($byLanguage);

            if (is_string($value) && $value !== '') {
                $values[$site->handle] = $value;
            }
        }

        return $values;
    }

    /**
     * A handle from a label or a cookie name: `Médias sociaux` becomes
     * `medias-sociaux`, `_pk_id.10` becomes `pk-id-10`. Not Str::slug(), which
     * drops the dots of a cookie name instead of separating on them.
     */
    private static function slug(string $value): string
    {
        return trim(strtolower((string)preg_replace('/[^A-Za-z0-9]+/', '-', Str::ascii(trim($value)))), '-');
    }
}
