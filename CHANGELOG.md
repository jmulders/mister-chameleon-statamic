# Changelog

All notable changes to `mister-chameleon/statamic` are documented here.
This project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

_Nothing yet._

## [1.1.0] — 2026-09-04

### Added
- **Control-panel page.** A native "Mister Chameleon" screen in the Statamic CP
  (`/cp/mister-chameleon`, with its own nav item) showing whether the add-on is
  connected and the effective configuration — tenant key (masked), API URL, mode,
  timeout, cache TTL, provisioning — plus links to the platform dashboard, docs
  and support. Read-only and env-driven; it makes no network call on load, so it
  is always fast and safe to open.
- **Adaptive form blocks.** `{{ mc:slot type="form:<key>" }}` now emits a
  `data-mc-block` container for the browser snippet to fill and wire. Forms are
  interactive — submit, validation, thank-you — so they have no server-side path
  and render client-side in every mode, `edge` included. Requires
  `{{ mc:snippet }}` on the page.

## [1.0.0] — 2026-07-23

### Added
- `{{ mc:slot }}` now sends the page's editor-set meta keywords (from the
  `keywords` / `seo_keywords` / `meta_keywords` field, or SEO Pro's `seo.keywords`)
  to the platform as `page.keywords`, so interest-profile scoring works in
  edge mode — matching what the JS snippet reads from `<meta name="keywords">`.

### Changed
- `VisitorContext` now mints and persists a stable first-party `mc_vid` cookie
  (random UUID, 1 year, SameSite=Lax) and sends it in `tokens.mc_vid` as the
  platform's primary session key. This replaces the daily-rotating fingerprint as
  the identity, so behavioural context (funnel stage, interest, returning
  visitor) accumulates across pageviews and days, and no longer collides between
  people behind the same IP/User-Agent. The fingerprint remains a coarse fallback.

### Fixed
- `{{ mc:snippet }}` in `client`/`hybrid` mode now loads the universal snippet at
  `/api/snippet.js` (with `data-site-key`) — the same runtime as the WordPress
  plugin — instead of the non-existent `/snippet/v1.js`. In `edge` mode the tag
  is now correctly a no-op (variants are resolved server-side).

### Added (initial release)
- Initial addon scaffold.
- `{{ mc:slot }}` Antlers tag — per-visitor context-slot resolution (edge / client / hybrid).
- `{{ mc:snippet }}` Antlers tag — client runtime for client/hybrid modes.
- `PlatformClient` — decision-engine API client (`POST /api/v1/slot`).
- `VisitorContext` — privacy-first first-party signal builder.
- `php please mc:sync` — pulls platform-managed fieldsets, block templates and design tokens (`/api/v1/provision/manifest`).
- Context-slot fieldset, starter block template and design tokens (offline fallbacks until first sync).

[Unreleased]: https://github.com/jmulders/mister-chameleon-statamic/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/jmulders/mister-chameleon-statamic/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/jmulders/mister-chameleon-statamic/releases/tag/v1.0.0
