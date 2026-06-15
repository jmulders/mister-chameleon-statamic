<?php

namespace MisterChameleon\Statamic\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

/**
 * php please mc:sync
 *
 * Pulls the platform-managed build artifacts — block templates, context-slot
 * fieldsets and design tokens (CSS) — from the platform and writes them into
 * the host site. This is what keeps the CMS in lockstep with the platform as
 * the single source of truth, so blocks never drift between the two.
 */
class SyncCommand extends Command
{
    protected $signature = 'mc:sync {--dry-run : Show what would change without writing}';

    protected $description = 'Sync Mister Chameleon blocks, fieldsets and design tokens from the platform';

    public function handle(): int
    {
        $base = rtrim((string) config('mister_chameleon.api_url'), '/');
        $key = (string) config('mister_chameleon.tenant_key', '');

        if ($key === '') {
            $this->error('MISTER_CHAMELEON_TENANT_KEY is not set. Add it to .env first.');
            return self::FAILURE;
        }

        $this->info('Fetching build manifest from ' . $base . ' …');

        try {
            $res = Http::withToken($key)->acceptJson()->timeout(20)
                ->get($base . '/api/v1/provision/manifest');
        } catch (\Throwable $e) {
            $this->error('Could not reach the platform: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (! $res->ok()) {
            $this->error('Platform returned HTTP ' . $res->status());
            return self::FAILURE;
        }

        $manifest = $res->json('artifacts') ?? [];
        $dry = (bool) $this->option('dry-run');
        $written = 0;

        foreach ($manifest as $artifact) {
            $path = base_path($artifact['path'] ?? '');
            $contents = (string) ($artifact['contents'] ?? '');
            if ($path === base_path('') || $contents === '') {
                continue;
            }

            $this->line(($dry ? '[dry] ' : '') . 'write ' . ($artifact['path'] ?? ''));

            if (! $dry) {
                File::ensureDirectoryExists(dirname($path));
                File::put($path, $contents);
                $written++;
            }
        }

        $this->info($dry
            ? 'Dry run complete — nothing written.'
            : "Synced {$written} artifact(s). Run `php please stache:clear` if templates changed.");

        return self::SUCCESS;
    }
}
