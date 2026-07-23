# Changelog

All notable changes to `mister-chameleon/statamic` are documented here.
This project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

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

### Added
- Initial addon scaffold.
- `{{ mc:slot }}` Antlers tag — per-visitor context-slot resolution (edge / client / hybrid).
- `{{ mc:snippet }}` Antlers tag — client runtime for client/hybrid modes.
- `PlatformClient` — decision-engine API client (`POST /api/v1/slot`).
- `VisitorContext` — privacy-first first-party signal builder.
- `php please mc:sync` — pulls platform-managed fieldsets, block templates and design tokens (`/api/v1/provision/manifest`).
- Context-slot fieldset, starter block template and design tokens (offline fallbacks until first sync).
