<?php

namespace MisterChameleon\Statamic;

use Statamic\Providers\AddonServiceProvider;
use MisterChameleon\Statamic\Tags\Slot;
use MisterChameleon\Statamic\Tags\Snippet;
use MisterChameleon\Statamic\Console\SyncCommand;
use MisterChameleon\Statamic\Support\PlatformClient;

class ServiceProvider extends AddonServiceProvider
{
    /**
     * Antlers tags shipped by the addon.
     *
     *   {{ mc:slot type="hero" }}      — render a personalised context slot
     *   {{ mc:snippet }}               — inject the edge/client swap snippet
     */
    protected $tags = [
        Slot::class,
        Snippet::class,
    ];

    protected $commands = [
        SyncCommand::class,
    ];

    /**
     * Front-end routes shipped by the addon. Statamic registers these with the
     * `web` middleware at the site root (before its content catch-all), so the
     * Live Preview bridge is available at /mc-live-preview without each tenant
     * needing to define it in their own routes/web.php.
     */
    protected $routes = [
        'web' => __DIR__ . '/../routes/web.php',
    ];

    /**
     * Block render partials + fieldsets are published into the host site so the
     * platform's build pipeline (mc:sync) can keep them in lockstep.
     */
    public function bootAddon()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mister_chameleon.php', 'mister_chameleon');

        $this->publishes([
            __DIR__ . '/../config/mister_chameleon.php' => config_path('mister_chameleon.php'),
        ], 'mister-chameleon-config');

        $this->publishes([
            __DIR__ . '/../resources/fieldsets' => resource_path('fieldsets'),
            __DIR__ . '/../resources/views/blocks' => resource_path('views/vendor/mister-chameleon/blocks'),
            __DIR__ . '/../resources/css' => resource_path('css'),
        ], 'mister-chameleon-blocks');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'mister-chameleon');
    }

    public function register()
    {
        parent::register();

        // Single shared platform client (decision engine API) per request.
        $this->app->singleton(PlatformClient::class, function () {
            return new PlatformClient(
                baseUrl: config('mister_chameleon.api_url'),
                tenantKey: config('mister_chameleon.tenant_key'),
                timeout: (float) config('mister_chameleon.timeout', 1.5),
                cacheTtl: (int) config('mister_chameleon.cache_ttl', 60),
            );
        });
    }
}
