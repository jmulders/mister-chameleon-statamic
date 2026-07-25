<?php

namespace MisterChameleon\Statamic\Http\Controllers;

use Statamic\Http\Controllers\CP\CpController;

/**
 * Control Panel "Mister Chameleon" page.
 *
 * A native CP home for the add-on — the Statamic equivalent of a WordPress
 * plugin settings/details screen. Surfaces the connection status and the
 * effective configuration (all env-driven, read-only here) plus quick links to
 * the platform dashboard, docs and support. No network call on load, so the
 * page is always fast and safe to open.
 */
class DashboardController extends CpController
{
    public function index()
    {
        $tenantKey = (string) config('mister_chameleon.tenant_key', '');
        $apiUrl    = rtrim((string) config('mister_chameleon.api_url', ''), '/');

        return view('mister-chameleon::cp.index', [
            'configured'      => $tenantKey !== '',
            'tenantKeyMasked' => $this->mask($tenantKey),
            'apiUrl'          => $apiUrl,
            'mode'            => (string) config('mister_chameleon.mode', 'edge'),
            'timeout'         => config('mister_chameleon.timeout', 1.5),
            'cacheTtl'        => (int) config('mister_chameleon.cache_ttl', 60),
            'provisioning'    => (bool) config('mister_chameleon.provisioning.enabled', true),
            'version'         => $this->version(),
            'dashboardUrl'    => $apiUrl !== '' ? $apiUrl : 'https://www.misterchameleon.nl',
            'docsUrl'         => 'https://www.misterchameleon.nl/docs',
            'supportEmail'    => 'support@misterchameleon.nl',
        ]);
    }

    private function mask(string $key): ?string
    {
        if ($key === '') {
            return null;
        }
        return strlen($key) <= 10
            ? str_repeat('•', strlen($key))
            : substr($key, 0, 4) . '…' . substr($key, -4);
    }

    private function version(): string
    {
        try {
            if (class_exists(\Composer\InstalledVersions::class)) {
                return \Composer\InstalledVersions::getPrettyVersion('mister-chameleon/statamic') ?: 'dev';
            }
        } catch (\Throwable $e) {
            // fall through
        }
        return 'dev';
    }
}
