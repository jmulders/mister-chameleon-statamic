# Mister Chameleon for Statamic

Per-visitor website personalisation for Statamic, powered by the [Mister Chameleon](https://www.misterchameleon.nl) platform.

The CMS keeps doing what it's good at — modelling and editing content. The platform owns the **build pipeline** (slots, templates, blocks, styling, design tokens) and the **decision engine** (which variant a given visitor sees, experiments, analytics). This addon is the thin runtime that renders platform-managed slots **natively in Antlers** and resolves variants from the platform per request.

Because rendering happens inside Statamic, the CP's native **Live Preview just works** — no headless cross-origin bridge.

## Install

This is a **private, licensed package** distributed via Private Packagist. Your
Mister Chameleon onboarding e-mail contains your Composer repository URL and
access token.

**1. Add the private repository** to your project's `composer.json`:

```json
{
  "repositories": [
    { "type": "composer", "url": "https://repo.packagist.com/<your-org>/" }
  ]
}
```

**2. Authenticate** (do this once; the token is in your onboarding e-mail — never
commit it):

```bash
composer config --global --auth http-basic.repo.packagist.com token <your-token>
```

**3. Require the package** (pin to a version range so updates are predictable):

```bash
composer require mister-chameleon/statamic:^1.0
php artisan vendor:publish --tag=mister-chameleon-config
php artisan vendor:publish --tag=mister-chameleon-blocks
```

**4. Add your credentials to `.env`:**

```dotenv
MISTER_CHAMELEON_API_URL=https://www.misterchameleon.nl
MISTER_CHAMELEON_TENANT_KEY=your-tenant-key
# edge = server-side render (best SEO, no flash). client = swap in the browser
# after your cache/CDN (recommended for an existing, cached site). hybrid = both.
MISTER_CHAMELEON_MODE=client
```

> **Existing site behind a cache/CDN?** Use `client`. Server-side `edge` rendering
> conflicts with full-page caching (a cached page is not re-resolved per visitor).
> Platform-provisioned sites we build for you default to `edge`.

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

The addon sends only coarse, first-party signals: a first-party `mc_vid`
identifier (a random UUID stored in a 1-year cookie on **your** domain — not
shared across sites), referrer, UTM, device class and a bot flag. No PII, no
cross-site tracking, no third-party cookies. Detected bots always receive the
default variant. In `client` mode the runtime is only loaded after your consent
tooling allows it.

## Requirements

- PHP 8.2+
- Statamic 5 or 6
- A Mister Chameleon platform tenant
