# Changelog

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
