# Changelog

## 1.4.0 - 2026-09-24

### Added

- **`Settings\Catalogue`.** Every setting a control panel offers, described
  once: its section, the control that edits it, its options and conditions, and
  the English label and help the wording is keyed by. An integration renders it
  in its own host's form language. The cookie inventory stays out — a
  repeatable field has no shape two hosts share.
- `:tag` joins the declared placeholders, for the help that names a template
  call: every host spells that call its own way.

## 1.3.0 - 2026-09-24

### Added

- **`Wording`.** The control panel strings in the spelling a host asks for:
  Laravel substitutes `:name`, Craft CMS's own translator `{name}`, and
  `braces()` converts keys and values between them. The conversion runs off a
  declared list of placeholders, never a pattern — the wording contains
  `{{ consent:banner }}`, which a pattern would rewrite.

### Changed

- **One spelling in the wording files.** Six strings were held twice, once per
  spelling, and two of the pairs had already drifted apart in French.
- **One casing rule for both panels**, the one Craft CMS and Statamic each
  follow: title case for labels, titles, headings and column names, sentence
  case for permissions, buttons, links, options and help text. `en.json` gains
  the Craft CMS field labels it was missing, and drops the entries that were
  titling an action.

## 1.2.0 - 2026-09-23

### Added

- **`Registry`.** What a consent register is made of, whatever keeps it: the
  settings it answers to, the columns it can be sorted on, and `cutoff()`, the
  date beyond which a record has outlived the consent it attests.
- **`Decision::ACTIONS` and `Decision::ORIGINS`.** What was pressed, and where
  the answer came from, named once for every integration.
- **Control panel wording**, English and French, read through `Paths::cpLang()`
  and `Paths::cpStrings()`. Separate from the banner's: one is read by a
  visitor, the other by whoever administers the site.
- **The suite's mark**, `Paths::icon()`: `icon.svg` in `currentColor` for a
  panel that colours it, `marketplace-icon.svg` in full colour for a listing.

## 1.1.0 - 2026-09-23

### Added

- **Primitives for a consent register.** `Presentation` fingerprints what the
  banner was showing — a SHA-256 over canonical JSON, object keys sorted and
  list order kept — from the configuration the server resolves for itself, so
  nothing is taken from the request. `Decision` reads a reported answer against
  the categories a site offers, rejecting one that names a category the site
  does not have. `RecordScript` builds the inline script that reports a
  decision over `sendBeacon`.
- The canonical form is pinned by a test: a published fingerprint is a promise,
  and it must stay recomputable by a third party.
- The JavaScript is covered by a Vitest suite, which checks that every
  attribute the script reads is one the templates write.

## 1.0.1 - 2026-09-23

### Fixed

- **The banner did nothing when clicked.** The script read the markup as
  `data-qsm-action`, `data-qsm-category` and `data-qsm-video-*`, which the
  templates stopped writing when the front-end surfaces took the `qsm-ck`
  prefix: accepting, refusing, opening the panel and lifting a video facade all
  silently did nothing. A test now checks that every attribute the script reads
  is one the templates write.

## 1.0.0 - 2026-09-22

- The core of Cookie Consent Kit, in `QuebecStudioMods\ConsentKit\Core`: settings
  resolution and merging, shipped categories and wording files, template
  variables, `[cookie-table]` markers, the `<head>` bootstrap.
- Twig and Blade templates, and the front-end assets. The banner is
  `<qsm-consent-kit>`; its classes, variables and attributes carry `qsm-ck`, and
  the JavaScript API is `window.qsmConsentKit`.
- `Paths` locates the templates and assets for an integration.
- The dark scheme's palette applies over a site's `qsm-consent-kit { --qsm-ck-bg: … }`:
  a dark palette is set through `--qsm-ck-dark-*`.
- `displayMode` (`full`, `floating`, `corner-left`, `corner-right`), written as
  `data-display` on `<qsm-consent-kit>`, with `--qsm-ck-box-width`, `--qsm-ck-box-radius`
  and `--qsm-ck-offset`. Every mode is full width under `40rem`.
- `reopenPosition` (`auto`, `left`, `right`) puts the reopen tab on a side,
  written as `data-reopen` on `<qsm-consent-kit>`. `auto` follows the banner: right
  for `corner-right`, left otherwise.
