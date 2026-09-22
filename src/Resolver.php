<?php

namespace QuebecStudioMods\ConsentKit\Core;

use Closure;

/**
 * Turns stored settings into what one site shows: its language, its wording,
 * its categories and cookies. Returns data only; rendering is the wrapper's.
 *
 * `$settings` is the settings as a plain array. `$env` resolves environment
 * references such as `$POLICY_URL`; it defaults to returning the value as is.
 * `$languages` reads the wording files, the site's own included.
 */
final class Resolver
{
    /** Every wording key exists in the source language. */
    private const SOURCE_LANGUAGE = 'en';

    private Closure $env;

    private Languages $languages;

    private ?array $categories = null;

    public function __construct(
        private readonly array $settings,
        private readonly SiteContext $site,
        ?callable $env = null,
        ?Languages $languages = null,
    ) {
        $this->env = $env !== null ? Closure::fromCallable($env) : static fn (string $value): string => $value;
        $this->languages = $languages ?? new Languages();
    }

    /**
     * Languages the banner can speak: those with a wording file, shipped or
     * the site's. The fallback setting is chosen from this list rather than
     * typed, so it can never name a language with nothing behind it.
     */
    public function availableLanguages(): array
    {
        return $this->languages->available();
    }

    /**
     * Configuration as serialised into the `<qsm-consent-kit>` element: single
     * language, empty categories removed, initial states normalised. The
     * policy link is resolved by the wrapper, which can look entries up.
     */
    public function bannerConfig(?string $policyUrl): array
    {
        return [
            'version' => $this->settings['version'] ?? 1,
            'cookieName' => $this->cookieName(),
            'cookieMaxAge' => $this->settings['cookieMaxAge'] ?? null,
            'policyUrl' => $policyUrl,
            'language' => $this->language(),

            'analyticsCategory' => $this->settings['analyticsCategory'] ?? null,
            'marketingCategory' => $this->settings['marketingCategory'] ?? null,
            'reopenButton' => $this->settings['reopenButton'] ?? null,
            'gpcHidesBanner' => $this->settings['gpcHidesBanner'] ?? null,
            'backdropStyle' => $this->settings['backdropStyle'] ?? null,
            'colorScheme' => $this->settings['colorScheme'] ?? null,
            'displayMode' => $this->displayMode(),
            'reopenPosition' => $this->reopenPosition(),

            'categories' => $this->visibleCategories(),
            'texts' => $this->texts(),
        ];
    }

    /** The display mode, `full` for anything unknown. */
    public function displayMode(): string
    {
        $mode = $this->settings['displayMode'] ?? null;

        return in_array($mode, Defaults::DISPLAY_MODES, true) ? $mode : Defaults::DISPLAY_MODES[0];
    }

    /**
     * Side of the reopen tab, `left` or `right`. `auto`, and anything unknown,
     * follows the banner: right for a right-hand corner, left otherwise.
     */
    public function reopenPosition(): string
    {
        $position = $this->settings['reopenPosition'] ?? null;

        if (in_array($position, ['left', 'right'], true)) {
            return $position;
        }

        return $this->displayMode() === 'corner-right' ? 'right' : 'left';
    }

    /** What the `<head>` bootstrap needs; see Bootstrap::script(). */
    public function bootstrapConfig(): array
    {
        return [
            'name' => $this->cookieName(),
            'version' => $this->settings['version'] ?? 1,
            'analytics' => $this->settings['analyticsCategory'] ?? null,
            'marketing' => $this->settings['marketingCategory'] ?? null,
            'gpcHidesBanner' => $this->settings['gpcHidesBanner'] ?? null,
        ];
    }

    public function cookieName(): string
    {
        return ($this->env)((string)($this->settings['cookieName'] ?? ''));
    }

    /**
     * Exact locale (`fr-CA`), then base language (`fr`), then the configured
     * fallback — among the languages that have a wording file.
     */
    public function language(): string
    {
        $locale = $this->site->language;
        $base = explode('-', $locale)[0];
        $available = $this->languages->available();

        foreach ([$locale, $base] as $candidate) {
            if (in_array($candidate, $available, true)) {
                return $candidate;
            }
        }

        return (string)($this->settings['defaultLanguage'] ?? '');
    }

    /**
     * The wording for the site's language. A key its file lacks, as in a
     * language a site adds partly, comes from the fallback language, then
     * from the source language, so every key is present.
     *
     * Wording stored per site handle still overlays it: 2.x edits it in the
     * control panel.
     */
    public function texts(): array
    {
        $texts = array_merge(
            $this->languages->texts(self::SOURCE_LANGUAGE),
            $this->languages->texts((string)($this->settings['defaultLanguage'] ?? '')),
            $this->languages->texts($this->language()),
        );

        $own = $this->settings['texts'][$this->site->handle] ?? [];

        return array_merge($texts, is_array($own) ? array_filter($own, 'is_string') : []);
    }

    /** Categories shown in the banner, in declared order. */
    public function visibleCategories(): array
    {
        return collect($this->categories())->where('visible', true)->values()->all();
    }

    /**
     * Every category with its cookies, its visibility and the reason for it.
     * Same shape for the banner and for the control panel; the panel is the
     * only caller that looks at the hidden ones.
     */
    public function categories(): array
    {
        if ($this->categories !== null) {
            return $this->categories;
        }

        $language = $this->language();
        $shipped = Defaults::categories($this->languages);
        $categories = ($this->settings['categories'] ?? []) ?: $shipped;
        $described = [];

        foreach ($categories as $handle => $category) {
            if (!is_array($category)) {
                continue;
            }

            $required = (bool)($category['required'] ?? false);
            $own = $this->describeCookies($category['cookies'] ?? [], $language, $shipped[$handle]['cookies'] ?? []);

            $described[] = [
                'handle' => $handle,
                'label' => $this->pick($category['label'] ?? $handle, $language, $handle, $shipped[$handle]['label'] ?? null),
                'description' => $this->pick($category['description'] ?? '', $language, '', $shipped[$handle]['description'] ?? null),
                'required' => $required,

                'default' => $required,

                'cookies' => $own,
                'visible' => $required || $own !== [],
                'hiddenReason' => (!$required && $own === []) ? 'no-cookies' : null,
            ];
        }

        return $this->categories = $described;
    }

    /**
     * One class string per element, the call's own overriding the site's.
     * Every key is present, so a template never has to test for one.
     */
    public function inventoryClasses(mixed $overrides = []): array
    {
        $framework = trim((string)($this->settings['inventoryFramework'] ?? ''));
        $custom = $this->settings['inventoryClasses'] ?? [];

        $site = match (true) {
            $framework === 'custom' => is_array($custom) ? $custom : [],
            $framework !== '' => Defaults::inventoryPresets()[$framework]['classes'] ?? [],
            default => [],
        };

        $overrides = is_array($overrides) ? $overrides : [];
        $classes = [];

        foreach (Defaults::inventoryElements() as $element) {
            $classes[$element] = trim((string)($overrides[$element] ?? $site[$element] ?? ''));
        }

        return $classes;
    }

    /**
     * Category whose consent loads the video without a click, or null.
     *
     * A category that is not declared returns null, and the facade stays: the
     * consent cookie can still name a category the inventory has dropped, and
     * safety is the default.
     */
    public function videoConsentCategory(): ?string
    {
        $handle = trim((string)($this->settings['videoConsentCategory'] ?? ''));

        if ($handle === '') {
            return null;
        }

        foreach ($this->categories() as $category) {
            if ($category['handle'] === $handle) {
                return $handle;
            }
        }

        return null;
    }

    /** `entry` or `url`, inferred from what is set when no explicit choice exists. */
    public function policySource(): string
    {
        $source = $this->perSite($this->settings['policySource'] ?? []);

        if ($source === 'entry' || $source === 'url') {
            return $source;
        }

        return $this->policyEntryId() ? 'entry' : 'url';
    }

    /**
     * The typed policy link. Accepts a shared string or a per-site map; the
     * control panel saves one site at a time, so a site missing from the map
     * keeps the shipped link.
     */
    public function policyUrl(): ?string
    {
        $value = $this->settings['policyUrl'] ?? '';

        if (is_array($value)) {
            $value = $this->perSite($value) ?? Defaults::POLICY_URL;
        }

        return ($this->env)((string)$value) ?: null;
    }

    /** Entry id picked for the site, 0 when none. */
    public function policyEntryId(): int
    {
        $ids = $this->perSite($this->settings['policyEntry'] ?? []);

        return (int)(is_array($ids) ? ($ids[0] ?? 0) : 0);
    }

    /**
     * A per-site setting, read by id then by handle. The control panel writes
     * ids, because that is what its site selector carries; a config file is
     * better off with handles, which survive being deployed to an install
     * whose sites were created in another order.
     */
    private function perSite(mixed $map): mixed
    {
        if (!is_array($map)) {
            return null;
        }

        if (array_key_exists($this->site->id, $map)) {
            return $map[$this->site->id];
        }

        return $map[$this->site->handle] ?? null;
    }

    /** One category's inventory: placeholders replaced, provider normalised. */
    private function describeCookies(mixed $cookies, string $language, mixed $shipped = []): array
    {
        if (!is_array($cookies)) {
            return [];
        }

        $shipped = is_array($shipped) ? $shipped : [];
        $cookieName = $this->cookieName();
        $described = [];

        foreach ($cookies as $handle => $cookie) {
            if (!is_array($cookie)) {
                continue;
            }

            $described[] = [
                'handle' => (string)$handle,
                'name' => str_replace('{cookieName}', $cookieName, (string)($cookie['name'] ?? '')),
                'provider' => ($cookie['provider'] ?? null) ?: null,
                'purpose' => $this->pick($cookie['purpose'] ?? '', $language, '', $shipped[$handle]['purpose'] ?? null),
                'duration' => $this->pick($cookie['duration'] ?? '', $language, '', $shipped[$handle]['duration'] ?? null),
            ];
        }

        return $described;
    }

    /**
     * A string applies everywhere; a map is keyed by site handle. The
     * plugin's own defaults are keyed by language instead — the plugin knows
     * the languages it speaks, not the sites of an install — so the language
     * is tried next. A value declared for neither falls back to the first
     * non-empty one rather than to nothing: a site added after seeding shows
     * wording that exists, rather than a blank label.
     */
    private function pick(mixed $value, string $language, string $fallback = '', mixed $shipped = null): string
    {
        if (is_string($value)) {
            return $value;
        }

        $value = is_array($value) ? $value : [];

        foreach ([$this->site->handle, $language] as $key) {
            if (isset($value[$key]) && is_string($value[$key]) && trim($value[$key]) !== '') {
                return $value[$key];
            }
        }

        if (is_array($shipped) && isset($shipped[$language]) && is_string($shipped[$language])) {
            return $shipped[$language];
        }

        foreach ($value as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return $candidate;
            }
        }

        return $fallback;
    }
}
