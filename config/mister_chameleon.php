<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform API
    |--------------------------------------------------------------------------
    |
    | Base URL of the Mister Chameleon platform that resolves per-visitor slot
    | variants and owns the build pipeline (slots, templates, blocks, tokens).
    | The tenant key authenticates this Statamic site to the platform.
    |
    */

    'api_url' => env('MISTER_CHAMELEON_API_URL', 'https://www.misterchameleon.nl'),

    'tenant_key' => env('MISTER_CHAMELEON_TENANT_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Rendering mode
    |--------------------------------------------------------------------------
    |
    | 'edge'    — server-side resolve variants in Antlers (best SEO, no flash).
    | 'client'  — render the CMS fallback, then swap client-side via the JS
    |             snippet (fastest TTFB, works behind full-page caches/CDNs).
    | 'hybrid'  — server-side resolve for bots/first paint, client refine.
    |
    */

    'mode' => env('MISTER_CHAMELEON_MODE', 'edge'),

    /*
    |--------------------------------------------------------------------------
    | Resolution
    |--------------------------------------------------------------------------
    |
    | timeout       — max seconds to wait for the platform before falling back
    |                 to the CMS-authored default variant (never block a render).
    | cache_ttl     — seconds to cache a resolved variant per visitor+slot.
    | bot_default   — always serve the default variant to detected bots so
    |                 crawlers index stable content.
    |
    */

    'timeout' => env('MISTER_CHAMELEON_TIMEOUT', 1.5),

    'cache_ttl' => env('MISTER_CHAMELEON_CACHE_TTL', 60),

    'bot_default' => env('MISTER_CHAMELEON_BOT_DEFAULT', true),

    /*
    |--------------------------------------------------------------------------
    | Provisioning
    |--------------------------------------------------------------------------
    |
    | When true, the addon pulls platform-managed fieldsets, block templates and
    | design tokens from the platform on `php please mc:sync`, so the CMS stays
    | in lockstep with the platform (single source of truth, no manual drift).
    |
    */

    'provisioning' => [
        'enabled' => env('MISTER_CHAMELEON_PROVISIONING', true),
        'tokens_path' => 'resources/css/mister-chameleon-tokens.css',
    ],

];
