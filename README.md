# Mister Chameleon for Statamic

Per-visitor website personalisation for Statamic, powered by the [Mister Chameleon](https://www.misterchameleon.nl) platform.

The CMS keeps doing what it's good at — modelling and editing content. The platform owns the **build pipeline** (slots, templates, blocks, styling, design tokens) and the **decision engine** (which variant a given visitor sees, experiments, analytics). This addon is the thin runtime that renders platform-managed slots **natively in Antlers** and resolves variants from the platform per request.

Because rendering happens inside Statamic, the CP's native **Live Preview just works** — no headless cross-origin bridge.

## Install

```bash
composer require mister-chameleon/statamic
php artisan vendor:publish --tag=mister-chameleon-config
php artisan vendor:publish --tag=mister-chameleon-blocks
```

Add your credentials to `.env`:

```dotenv
MISTER_CHAMELEON_API_URL=https://www.misterchameleon.nl
MISTER_CHAMELEON_TENANT_KEY=your-tenant-key
MISTER_CHAMELEON_MODE=edge   # edge | client | hybrid
```

Pull the platform-managed blocks, fieldsets and design tokens:

```bash
php please mc:sync
php please stache:clear
```

## Usage

Add a personalised slot anywhere in a template:

```antlers
{{ mc:slot type="hero" default="hero_default" }}
```

Include the runtime once, before `</body>` (needed for `client`/`hybrid` modes and impression tracking):

```antlers
{{ mc:snippet }}
```

Author the fallback content in the entry using the **Mister Chameleon — Context Slot** fieldset. That fallback is what bots and offline-fallback renders receive, so the page is always complete and SEO-safe.

## Rendering modes

| Mode     | Where the decision happens         | Best for |
|----------|------------------------------------|----------|
| `edge`   | Server-side, in Antlers, per render | SEO, no flash of default content |
| `client` | In the browser via the snippet      | Full-page caches / CDNs |
| `hybrid` | Server default + client refine      | Cached pages that still want first-paint personalisation |

In every mode, if the platform is slow or unreachable the addon renders the CMS-authored default. **Personalisation never blocks or breaks a page.**

## How it fits together

```
┌─────────────────────────┐         ┌──────────────────────────────┐
│  Statamic (this addon)   │         │  Mister Chameleon platform   │
│  • content + editing     │  POST   │  • decision engine           │
│  • native Antlers render │ ──────► │  • experiments + analytics   │
│  • Live Preview          │  /slot  │  • build pipeline (mc:sync)  │
└─────────────────────────┘ ◄────── └──────────────────────────────┘
        renders blocks        variant      single source of truth
```

The platform is the **single source of truth** for blocks and styling: `php please mc:sync` writes the platform's templates, fieldsets and tokens into the site, so the two never drift.

## Privacy

The addon sends only coarse, first-party signals (anonymous rotating fingerprint, referrer, UTM, device class, bot flag). No PII, no cross-site identifiers. Detected bots always receive the default variant.

## Requirements

- PHP 8.2+
- Statamic 5 or 6
- A Mister Chameleon platform tenant
