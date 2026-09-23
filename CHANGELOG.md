# Changelog

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
