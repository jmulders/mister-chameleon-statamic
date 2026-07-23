<?php

namespace MisterChameleon\Statamic\Tags;

use Statamic\Tags\Tags;

/**
 * {{ mc:snippet }}  — place once before </body>.
 *
 * In `client` / `hybrid` mode this loads the Mister Chameleon UNIVERSAL snippet
 * from the platform — the exact same runtime the WordPress plugin uses. It
 * mints/reads a first-party visitor id, reports a pageview to the decision
 * engine (POST /api/snippet/decide), and swaps every [data-mc-slot] and
 * [data-mc-block] element in the browser. So client mode gets the full
 * behavioural-context + personalisation pipeline, not a bespoke one.
 *
 * In `edge` mode variants are already resolved server-side by {{ mc:slot }}
 * (which also records context via POST /api/v1/slot), so this tag is a no-op.
 *
 * NOTE: `hybrid` runs BOTH the server-side resolve and this client snippet, so a
 * pageview is recorded twice (once per path). Prefer `edge` or `client` unless
 * you specifically need server-first paint plus client refinement.
 */
class Snippet extends Tags
{
    public function index(): string
    {
        $apiUrl = rtrim((string) config('mister_chameleon.api_url'), '/');
        $tenant = (string) config('mister_chameleon.tenant_key', '');
        $mode = (string) config('mister_chameleon.mode', 'edge');

        // No tenant key, or edge mode (fully resolved server-side) → nothing to load.
        if ($tenant === '' || $mode === 'edge') {
            return '';
        }

        // client / hybrid → load the universal snippet (data-site-key = tenant key).
        return sprintf(
            '<script async src="%s/api/snippet.js" data-site-key="%s"></script>',
            e($apiUrl),
            e($tenant),
        );
    }
}
