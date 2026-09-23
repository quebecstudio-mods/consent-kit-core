# Cookie Consent Kit core — `quebecstudio-mods/consent-kit-core`

The framework-free part of Cookie Consent Kit, shared by its integrations:

| Integration | Package |
|---|---|
| Craft CMS 6 | `quebecstudio-mods/craftcms-consent-kit` 6.x |
| Craft CMS 5 | `quebecstudio-mods/craftcms-consent-kit` 5.x |
| Laravel | `quebecstudio-mods/laravel-consent-kit` |

Requires PHP 8.2, `illuminate/collections` and `illuminate/support`. It uses
no framework: an integration passes it settings and site data, and renders its
templates.

## Contents

| Path | What it is |
|---|---|
| `src/Resolver.php` | Settings of one site → language, wording, categories, banner configuration |
| `src/SettingsMerger.php` | Merges a submitted settings form over stored settings; seeds the shipped categories |
| `src/Defaults.php` | Shipped categories, inventory elements and class presets |
| `src/Languages.php` | Reads the wording files, the shipped ones then the project's |
| `src/lang/<language>.php` | Shipped wording: English and French |
| `src/Templates.php` | Derived `qsm-ck*` variables for the templates |
| `src/CookieTableMarkers.php` | `[cookie-table]` markers in content |
| `src/Bootstrap.php` | The inline `<head>` script, with its configuration |
| `src/Attributes.php` | Optional class attributes for the Blade templates |
| `src/Paths.php` | Paths of the templates, wording files and assets |
| `src/Presentation.php` | Fingerprint of what the banner was showing, for a consent register |
| `src/Decision.php` | A reported answer, read against the categories the site offers |
| `src/RecordScript.php` | The inline script that reports a decision to a register endpoint |
| `resources/views/blade/`, `resources/views/twig/` | Shipped templates: `banner`, `cookie-table`, `video-facade`, `video-embed` |
| `resources/consent.js`, `resources/consent.css` | Front-end assets |

## Using it from an integration

```php
use QuebecStudioMods\ConsentKit\Core\Bootstrap;
use QuebecStudioMods\ConsentKit\Core\Languages;
use QuebecStudioMods\ConsentKit\Core\Paths;
use QuebecStudioMods\ConsentKit\Core\Resolver;
use QuebecStudioMods\ConsentKit\Core\SiteContext;
use QuebecStudioMods\ConsentKit\Core\Templates;

$resolver = new Resolver(
    $settings,                                  // settings as a plain array
    new SiteContext($id, $handle, $locale),     // the site being served
    fn(string $value) => resolveEnv($value),    // `$ENV_VAR` references
    new Languages([$projectWordingFolder]),     // the project's wording files
);

$head = Bootstrap::script($resolver->bootstrapConfig());
$config = $resolver->bannerConfig($policyUrl);

$html = render(Paths::views('blade') . '/banner.blade.php', Templates::banner([
    'config' => $config,
    'texts' => $config['texts'],
    'categories' => $config['categories'],
]));
```

The integration publishes `Paths::asset('consent.js')` and
`Paths::asset('consent.css')`, and loads them on the pages that show the banner.

## Template contract

| Template | Variables | Derived by `Templates` |
|---|---|---|
| `banner` | `config`, `texts`, `categories` | `qsmConfigJson` |
| `cookie-table` | `categories`, `texts`, `classes`, `heading`, `headingLevel` | `qsmLevel`; per category `qsmTitleId`; per cookie `qsmProviderLabel` |
| `video-facade` | `youtubeId`, `title`, `poster`, `thumbnail`, `consentCategory`, `texts` | `qsmPosterUrl` |
| `video-embed` | `youtubeId`, `title` | `qsmIframeTitle` |

The Twig and Blade templates render the same markup; the tests check both
against the same references.

## Tests

```bash
composer install
vendor/bin/pest
```

The arch test fails if `src/` uses Craft, Yii, Twig, or any part of Laravel
beyond collections and support.

## Licence

Proprietary. See [LICENSE.md](LICENSE.md).
