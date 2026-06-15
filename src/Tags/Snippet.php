<?php

namespace MisterChameleon\Statamic\Tags;

use Statamic\Tags\Tags;

/**
 * {{ mc:snippet }}  — place once before </body>.
 *
 * Emits the small client runtime that powers `client` and `hybrid` modes:
 * it finds [data-mc-slot] elements, asks the platform for the visitor's
 * variant, and swaps the markup in-place. In `edge` mode it is a no-op beyond
 * reporting impressions/experiment exposure. Loaded from the platform CDN so
 * updates ship without re-deploying the site.
 */
class Snippet extends Tags
{
    public function index(): string
    {
        $apiUrl = rtrim((string) config('mister_chameleon.api_url'), '/');
        $tenant = (string) config('mister_chameleon.tenant_key', '');
        $mode = (string) config('mister_chameleon.mode', 'edge');

        if ($tenant === '') {
            return '';
        }

        return sprintf(
            '<script async src="%s/snippet/v1.js" data-mc-tenant="%s" data-mc-api="%s" data-mc-mode="%s"></script>',
            e($apiUrl),
            e($tenant),
            e($apiUrl),
            e($mode),
        );
    }
}
