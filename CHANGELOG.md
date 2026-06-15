# Changelog

All notable changes to `mister-chameleon/statamic` are documented here.
This project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Initial addon scaffold.
- `{{ mc:slot }}` Antlers tag — per-visitor context-slot resolution (edge / client / hybrid).
- `{{ mc:snippet }}` Antlers tag — client runtime for client/hybrid modes.
- `PlatformClient` — decision-engine API client (`POST /api/v1/slot`).
- `VisitorContext` — privacy-first first-party signal builder.
- `php please mc:sync` — pulls platform-managed fieldsets, block templates and design tokens (`/api/v1/provision/manifest`).
- Context-slot fieldset, starter block template and design tokens (offline fallbacks until first sync).
