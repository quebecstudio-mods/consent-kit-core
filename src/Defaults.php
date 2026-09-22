<?php

namespace QuebecStudioMods\ConsentKit\Core;

/**
 * What the plugin ships with: categories, wording, and the inventory's class
 * sets. Plain data, identical under every Craft version.
 */
final class Defaults
{
    /** Policy link of a site that has none of its own. */
    public const POLICY_URL = '/politique-de-confidentialite';

    /** Where the banner sits: full width, or a box. The first is the default. */
    public const DISPLAY_MODES = ['full', 'floating', 'corner-left', 'corner-right'];

    /** Side of the reopen tab; `auto` follows the display mode. The first is the default. */
    public const REOPEN_POSITIONS = ['auto', 'left', 'right'];

    /** Elements of the rendered inventory that can carry site classes. */
    public static function inventoryElements(): array
    {
        return ['wrapper', 'section', 'heading', 'description', 'table', 'thead', 'tbody', 'tr', 'th', 'td'];
    }

    /**
     * Class sets the inventory can be rendered with, offered in the control
     * panel next to `custom`.
     *
     * Dated starting points, never a contract: CSS frameworks rename
     * utilities between major versions, and the plugin does not follow them.
     * A site whose framework moves on switches to `custom`, which freezes the
     * classes it had into its own settings.
     */
    public static function inventoryPresets(): array
    {
        return [

            'bootstrap' => [
                'label' => 'Bootstrap 5',
                'classes' => [
                    'table' => 'table table-striped',
                ],
            ],
            'bulma' => [
                'label' => 'Bulma',
                'classes' => [
                    'table' => 'table is-striped is-fullwidth',
                ],
            ],

            'tailwind' => [
                'label' => 'Tailwind CSS',
                'classes' => [
                    'wrapper' => 'space-y-8',
                    'heading' => 'text-lg font-semibold',
                    'description' => 'mt-1 text-sm opacity-80',
                    'table' => 'mt-3 w-full border-collapse text-left text-sm',
                    'thead' => 'border-b',
                    'tbody' => 'divide-y',
                    'th' => 'px-3 py-2 font-semibold',
                    'td' => 'px-3 py-2 align-top',
                ],
            ],
        ];
    }

    /**
     * The categories the plugin ships with, and the only cookies it can
     * declare on a site's behalf: the ones Craft and the plugin set
     * themselves. `{cookieName}` is substituted by the service.
     *
     * Wording comes from the language files, keyed by language rather than
     * by site: the plugin knows the languages it speaks, not the sites of a
     * given install. Installing the plugin seeds it into the settings, keyed
     * by site handle, each site taking the wording of its language.
     *
     * Three categories, not a catalogue: `statistics` and `marketing` ship
     * empty because nearly every site ends up with one or the other, and a
     * category with no declared cookie stays hidden until it is filled in.
     * Anything else — preferences, social media — is created from the Cookie
     * inventory pane, where a site names what it actually uses.
     */
    public static function categories(Languages $languages = new Languages()): array
    {
        $categories = [
            'necessary' => [
                'required' => true,
                'label' => [],
                'description' => [],
                'cookies' => [
                    'craft-session' => ['name' => 'CraftSessionId', 'provider' => null],
                    'craft-csrf' => ['name' => 'CRAFT_CSRF_TOKEN', 'provider' => null],
                    'consent' => ['name' => '{cookieName}', 'provider' => null],
                ],
            ],
            'statistics' => ['required' => false, 'label' => [], 'description' => [], 'cookies' => []],
            'marketing' => ['required' => false, 'label' => [], 'description' => [], 'cookies' => []],
        ];

        foreach ($categories as $handle => &$category) {
            foreach ($languages->available() as $language) {
                $wording = $languages->categories($language)[$handle] ?? [];

                foreach (['label', 'description'] as $key) {
                    if (isset($wording[$key]) && is_string($wording[$key])) {
                        $category[$key][$language] = $wording[$key];
                    }
                }

                foreach ($category['cookies'] as $cookieHandle => &$cookie) {
                    foreach (['purpose', 'duration'] as $key) {
                        $value = $wording['cookies'][$cookieHandle][$key] ?? null;

                        if (is_string($value)) {
                            $cookie[$key][$language] = $value;
                        }
                    }
                }
                unset($cookie);
            }
        }
        unset($category);

        return $categories;
    }
}
